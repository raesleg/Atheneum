<?php
header('Content-Type: application/json');
include '../inc/conn.php';
include 'email_reply.php';

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
$refundId = isset($data['refundId']) ? (int) $data['refundId'] : 0;
$action = $data['action'] ?? '';
$adminNote = substr(trim($data['adminNote'] ?? ''), 0, 1000);

$csrfToken = $data['csrf_token'] ?? '';
if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid request. Please reload the page and try again.', 'new_csrf_token' => $_SESSION['csrf_token'] ?? '']);
    exit;
}
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

if ($refundId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters.', 'new_csrf_token' => $_SESSION['csrf_token']]);
    exit;
}


// Get the refund request with user email if can
$stmt = $conn->prepare("
    SELECT r.*, u.fname, u.lname, u.email as reg_email, r.name as contact_name, r.email as contact_email
    FROM Refund r 
    LEFT JOIN Users u ON r.userId = u.userId
    WHERE r.refundId = ? AND r.status = 'pending'
");
$stmt->bind_param("i", $refundId);
$stmt->execute();
$refund = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$refund) {
    echo json_encode(['success' => false, 'message' => 'Request not found or already resolved.', 'new_csrf_token' => $_SESSION['csrf_token']]);
    exit;
}

// 4. Check if rejection is allowed for this type
if ($action === 'reject' && $refund['type'] !== 'Refund') {
    echo json_encode(['success' => false, 'message' => 'Enquiries cannot be rejected. Please provide a reply.', 'new_csrf_token' => $_SESSION['csrf_token']]);
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
    if ($refund['type'] === 'Refund') {
        // Update order status
        $stmt = $conn->prepare("
            UPDATE Orders SET paymentStatus = 'refunded', orderStatus = 'cancelled'
            WHERE orderId = ?
        ");
        $stmt->bind_param("i", $refund['orderId']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Handle Enquiry/Feedback: Send Email
        $recipientEmail = $refund['email'] ?: ($refund['reg_email'] ?? '');
        $recipientName = $refund['name'] ?: (trim(($refund['fname'] ?? '') . ' ' . ($refund['lname'] ?? '')) ?: 'Valued Customer');
        $subject = $refund['subject'] ?: $refund['type'];

        if ($recipientEmail) {
            sendAdminReplyEmail($recipientEmail, $subject, $adminNote, $recipientName);
        }
    }
}

echo json_encode(['success' => true, 'message' => 'Refund request ' . $newStatus . '.', 'new_csrf_token' => $_SESSION['csrf_token']]);
