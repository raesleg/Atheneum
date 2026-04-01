<?php
header('Content-Type: application/json');
include '../inc/conn.php';

$isLoggedIn = $_SESSION['loggedin'] ?? false;
$adminId = $_SESSION['userId'] ?? null;

if (!$isLoggedIn || !$adminId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$stmt = $conn->prepare("SELECT role FROM Users WHERE userId = ?");
$stmt->bind_param("i", $adminId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || $row['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$refundId = isset($data['refundId']) ? (int)$data['refundId'] : 0;
$action = $data['action'] ?? '';
$adminNote = substr(trim($data['adminNote'] ?? ''), 0, 1000);

$csrfToken = $data['csrf_token'] ?? '';
if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please reload the page and try again.', 'new_csrf_token' => $_SESSION['csrf_token'] ?? '']);
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // rotate after use

if ($refundId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.', 'new_csrf_token' => $_SESSION['csrf_token']]);
    exit;
}

// get the refund request
$stmt = $conn->prepare("SELECT * FROM Refund WHERE refundId = ? AND status = 'pending'");
$stmt->bind_param("i", $refundId);
$stmt->execute();
$refund = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$refund) {
    echo json_encode(['success' => false, 'message' => 'Request not found or already resolved.', 'new_csrf_token' => $_SESSION['csrf_token']]);
    exit;
}

$newStatus = $action === 'approve' ? 'approved' : 'rejected';

$stmt = $conn->prepare("
    UPDATE Refund
    SET status = ?, admin_note = ?, resolved_at = NOW(), resolved_by = ?
    WHERE refundId = ?
");
$stmt->bind_param("ssii", $newStatus, $adminNote, $adminId, $refundId);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.', 'new_csrf_token' => $_SESSION['csrf_token']]);
    $stmt->close();
    exit;
}
$stmt->close();

if ($action === 'approve') {
    $stmt = $conn->prepare("
        UPDATE Orders SET paymentStatus = 'refunded', orderStatus = 'cancelled'
        WHERE orderId = ?
    ");
    $stmt->bind_param("i", $refund['orderId']);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'message' => 'Refund request ' . $newStatus . '.', 'new_csrf_token' => $_SESSION['csrf_token']]);
