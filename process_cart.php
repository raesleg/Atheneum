<?php
include 'inc/header.php';

if (!$isLoggedIn) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $action    = $data['action'];
    $productId = (int) $data['productId'];

    if ($action === 'add') {
        $qty = isset($data['qty']) ? (int) $data['qty'] : 1;
        $stmt = $conn->prepare("
            INSERT INTO Cart (username, productId, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");
        $stmt->bind_param("sii", $username, $productId, $qty);
        $stmt->execute();
        
    } elseif ($action === 'update') {
        $qty  = (int) $data['qty'];
        $stmt = $conn->prepare("UPDATE Cart SET quantity = ? WHERE username = ? AND productId = ?");
        $stmt->bind_param("isi", $qty, $username, $productId);
        $stmt->execute();

    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM Cart WHERE username = ? AND productId = ?");
        $stmt->bind_param("si", $username, $productId);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
    exit;
}
?>