<?php
header('Content-Type: application/json');

include 'inc/conn.php';
require_once 'config/shipment_config.php';

$username = $_SESSION['username'] ?? null;
$userId = $_SESSION['userId'] ?? null;

if (!$username) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$productId = isset($data['productId']) ? (int)$data['productId'] : 0;
$orderId   = isset($data['orderId'])   ? (int)$data['orderId']   : 0;
$rating    = isset($data['rating'])    ? (int)$data['rating']    : 0;
$comment   = isset($data['comment'])   ? trim($data['comment'])  : '';

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'error' => 'Rating must be between 1 and 5.']);
    exit;
}
if (strlen($comment) > 200) {
    echo json_encode(['success' => false, 'error' => 'Comment must be 200 characters or fewer.']);
    exit;
}

$comment = htmlspecialchars(strip_tags($comment), ENT_QUOTES, 'UTF-8');
if (empty($comment)) {
    $comment = null;
}

$stmtCheck = $conn->prepare("SELECT reviewId FROM Reviews WHERE userId = ? AND productId = ?");
$stmtCheck->bind_param("ii", $userId, $productId);
$stmtCheck->execute();
if ($stmtCheck->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'error' => 'You have already reviewed this book.']);
    $stmtCheck->close();
    exit;
}
$stmtCheck->close();

$stmtElig = $conn->prepare("
    SELECT o.orderId
    FROM Orders o
    JOIN OrderItems oi ON o.orderId = oi.orderId
    JOIN OrderShipments s ON o.orderId = s.orderId
    WHERE o.userId = ?
      AND oi.productId = ?
      AND o.orderId = ?
      AND o.paymentStatus = 'paid'
      AND s.currentStatus = 'delivered'
      AND s.delivered_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    LIMIT 1
");
$stmtElig->bind_param("iiii", $userId, $productId, $orderId, $REVIEW_WINDOW_DAYS);
$stmtElig->execute();
$eligible = $stmtElig->get_result()->fetch_assoc();
$stmtElig->close();

if (!$eligible) {
    echo json_encode(['success' => false, 'error' => 'You are not eligible to review this book. You must have purchased and received it within the last ' . $REVIEW_WINDOW_DAYS . ' days.']);
    exit;
}

$stmtInsert = $conn->prepare("
    INSERT INTO Reviews (productId, userId, orderId, rating, comment)
    VALUES (?, ?, ?, ?, ?)
");
$stmtInsert->bind_param("iiiis", $productId, $userId, $orderId, $rating, $comment);

if ($stmtInsert->execute()) {
    $stmtUser = $conn->prepare("SELECT fname, lname FROM Users WHERE username = ?");
    $stmtUser->bind_param("s", $username);
    $stmtUser->execute();
    $user = $stmtUser->get_result()->fetch_assoc();
    $stmtUser->close();

    $displayName = (!empty($user['fname'])) ? $user['fname'] . ' ' . $user['lname'] : $username;

    echo json_encode([
        'success'     => true,
        'review'      => [
            'rating'      => $rating,
            'comment'     => $comment,
            'displayName' => htmlspecialchars($displayName),
            'date'        => date('d M Y'),
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to save review. Please try again.']);
}

$stmtInsert->close();
$conn->close();
?>
