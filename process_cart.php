<?php
include 'inc/conn.php';

header('Content-Type: application/json');

if (!isset($_SESSION['userId'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$userId = (int) $_SESSION['userId'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$productId = (int)($data['productId'] ?? 0);

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

if ($action === 'add') {
    $qty = isset($data['qty']) ? (int)$data['qty'] : 1;

    $stmt = $conn->prepare("
        INSERT INTO Cart (userId, productId, quantity)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
    ");
    $stmt->bind_param("iii", $userId, $productId, $qty);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'update') {
    $qty = (int)($data['qty'] ?? 0);

    $stmt = $conn->prepare("UPDATE Cart SET quantity = ? WHERE userId = ? AND productId = ?");
    $stmt->bind_param("iii", $qty, $userId, $productId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'remove') {
    $stmt = $conn->prepare("DELETE FROM Cart WHERE userId = ? AND productId = ?");
    $stmt->bind_param("ii", $userId, $productId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
exit;
?>