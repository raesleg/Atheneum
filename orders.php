<?php
$pageTitle = "My Orders";
$extraCSS  = ["assets/css/orders.css"];
$extraJS   = [["src" => "assets/js/orders.js", "defer" => true]];

include 'inc/conn.php';
include 'inc/header.php';
include 'inc/nav.php';
require_once 'config/shipment_config.php';

if (!$isLoggedIn) {
    header("Location: login.php");
    exit();
}

$stmt = $conn->prepare("
    SELECT o.orderId, o.totalPrice, o.created_at, o.orderStatus, o.paymentStatus,
           s.currentStatus AS shipmentStatus, s.delivered_at,
           r.status AS refundStatus -- [REFUND-LOGIC] Added refundStatus
    FROM Orders o
    LEFT JOIN OrderShipments s ON o.orderId = s.orderId
    LEFT JOIN Refund r ON o.orderId = r.orderId -- [REFUND-LOGIC] Joined Refund table
    WHERE o.userId = ?
    ORDER BY o.created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<main>
    <div class="page-header">
        <p class="eyebrow">Your Purchases</p>
        <h1>My Orders</h1>
    </div>

    <div class="orders-wrapper">
        <?php if (empty($orders)): ?>
            <div class="empty-orders" role="status">
                <i class="bi bi-bag-x" aria-hidden="true"></i>
                <p>You have no orders yet.</p>
                <a href="index.php" class="browse-btn">Start Browsing</a>
            </div>
        <?php else: ?>
            <div role="list" aria-label="Your orders">
            <?php foreach ($orders as $order): ?>
                <?php
                    // [REFUND-LOGIC] Check if order is fully refunded or rejected
                    $isRefunded  = ($order['paymentStatus'] === 'refunded');
                    $isRejected  = ($order['refundStatus'] === 'rejected');
                    
                    $statusLabel = $isRefunded ? 'Refunded' : ($STATUS_LABELS[$order['shipmentStatus'] ?? 'order_placed'] ?? 'Processing');
                    $statusClass = $isRefunded ? 'refunded' : str_replace('_', '-', $order['shipmentStatus'] ?? 'order-placed');
                    $statusIdx   = array_search($order['shipmentStatus'] ?? 'order_placed', $STATUS_ORDER);
                ?>
                <div class="order-card" role="listitem" aria-label="Order #<?= $order['orderId'] ?>, <?= htmlspecialchars($statusLabel) ?>, total $<?= number_format($order['totalPrice'], 2) ?>">
                    <div class="order-card-header">
                        <div>
                            <span class="order-id">Order #<?= $order['orderId'] ?></span>
                            <time class="order-date" datetime="<?= date('Y-m-d\TH:i', strtotime($order['created_at'])) ?>">
                                <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                            </time>
                        </div>
                        <div class="order-header-right">
                            <span class="order-total" aria-label="Total: $<?= number_format($order['totalPrice'], 2) ?>">$<?= number_format($order['totalPrice'], 2) ?></span>
                            <span class="status-pill <?= $statusClass ?>"><?= htmlspecialchars($statusLabel) ?></span>
                            <?php 
                                // [REFUND-LOGIC] Show additional pills for pending or rejected refunds
                                if ($isRejected): ?>
                                <span class="status-pill refund-rejected" aria-label="Refund request rejected">Refund Rejected</span>
                            <?php elseif ($order['refundStatus'] === 'pending'): ?>
                                <span class="status-pill refund-pending" aria-label="Refund request pending">Refund Pending</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php 
                    // [REFUND-LOGIC] Hide tracker if refunded
                    if (!$isRefunded): ?>
                    <div class="mini-tracker" role="progressbar" 
                         aria-valuenow="<?= $statusIdx + 1 ?>" 
                         aria-valuemin="1" 
                         aria-valuemax="<?= count($STATUS_ORDER) ?>"
                         aria-label="Shipment progress: <?= htmlspecialchars($statusLabel) ?>, step <?= $statusIdx + 1 ?> of <?= count($STATUS_ORDER) ?>">
                        <?php foreach ($STATUS_ORDER as $idx => $step): ?>
                            <div class="mini-step <?= $idx <= $statusIdx ? 'completed' : '' ?> <?= $idx === $statusIdx ? 'current' : '' ?>">
                                <div class="mini-dot" aria-hidden="true"></div>
                                <?php if ($idx < count($STATUS_ORDER) - 1): ?>
                                    <div class="mini-line <?= $idx < $statusIdx ? 'completed' : '' ?>" aria-hidden="true"></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; // [REFUND-LOGIC] Close if (!$isRefunded) ?>

                    <div class="order-card-footer">
                        <a href="order_detail.php?id=<?= $order['orderId'] ?>" class="view-details-btn" aria-label="View details for order #<?= $order['orderId'] ?>">
                            View Details <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'inc/footer.php'; ?>