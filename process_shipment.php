<?php
header('Content-Type: application/json');

include 'inc/conn.php';
require_once 'config/shipment_config.php';

$username = $_SESSION['username'] ?? null;
if (!$username) {
    echo json_encode(['success' => false, 'error' => 'Not logged in.']);
    exit;
}

$orderId = isset($_GET['orderId']) ? (int)$_GET['orderId'] : 0;
if ($orderId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid order ID.']);
    exit;
}

$stmt = $conn->prepare("
    SELECT o.orderId, o.paymentStatus, s.*
    FROM Orders o
    LEFT JOIN OrderShipments s ON o.orderId = s.orderId
    WHERE o.orderId = ? AND o.username = ?
");
$stmt->bind_param("is", $orderId, $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'error' => 'Order not found.']);
    exit;
}

if ($row['shipmentId'] === null && $row['paymentStatus'] === 'paid') {
    $stmtCreate = $conn->prepare("
        INSERT INTO OrderShipments (orderId, currentStatus, order_placed_at)
        VALUES (?, 'order_placed', NOW())
    ");
    $stmtCreate->bind_param("i", $orderId);
    $stmtCreate->execute();
    $stmtCreate->close();

    $stmt2 = $conn->prepare("SELECT * FROM OrderShipments WHERE orderId = ?");
    $stmt2->bind_param("i", $orderId);
    $stmt2->execute();
    $row = $stmt2->get_result()->fetch_assoc();
    $stmt2->close();
} elseif ($row['shipmentId'] === null) {
    echo json_encode([
        'success' => true,
        'status'  => 'pending',
        'label'   => 'Pending Payment',
        'timestamps' => []
    ]);
    exit;
}

$currentStatus = $row['currentStatus'];
$currentIdx    = array_search($currentStatus, $STATUS_ORDER);

if ($currentStatus !== 'delivered') {
    $currentTimestampCol = $currentStatus . '_at';
    $currentTimestamp     = $row[$currentTimestampCol];

    if ($currentTimestamp) {
        $elapsed = time() - strtotime($currentTimestamp);
        $requiredSeconds = $SHIPMENT_TIMINGS[$currentStatus];

        if ($requiredSeconds !== null && $elapsed >= $requiredSeconds) {
            $nextIdx    = $currentIdx + 1;
            $nextStatus = $STATUS_ORDER[$nextIdx];
            $nextCol    = $nextStatus . '_at';

            $stmtUpdate = $conn->prepare("
                UPDATE OrderShipments 
                SET currentStatus = ?, {$nextCol} = NOW()
                WHERE orderId = ?
            ");
            $stmtUpdate->bind_param("si", $nextStatus, $orderId);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            if ($nextStatus === 'delivered') {
                $stmtOrder = $conn->prepare("UPDATE Orders SET orderStatus = 'completed' WHERE orderId = ?");
                $stmtOrder->bind_param("i", $orderId);
                $stmtOrder->execute();
                $stmtOrder->close();
            }

            $currentStatus = $nextStatus;
        }
    }
}

$stmtFinal = $conn->prepare("SELECT * FROM OrderShipments WHERE orderId = ?");
$stmtFinal->bind_param("i", $orderId);
$stmtFinal->execute();
$shipment = $stmtFinal->get_result()->fetch_assoc();
$stmtFinal->close();

$timestamps = [];
foreach ($STATUS_ORDER as $step) {
    $col = $step . '_at';
    $timestamps[$step] = $shipment[$col] ? date('d M, h:i A', strtotime($shipment[$col])) : null;
}

echo json_encode([
    'success'    => true,
    'status'     => $shipment['currentStatus'],
    'label'      => $STATUS_LABELS[$shipment['currentStatus']] ?? 'Unknown',
    'statusIdx'  => array_search($shipment['currentStatus'], $STATUS_ORDER),
    'timestamps' => $timestamps
]);

$conn->close();
?>
