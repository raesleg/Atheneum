<?php
include 'inc/conn.php';
include 'inc/header.php';

if (!$isLoggedIn) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $action    = $data['action'];
    $productId = (int) $data['productId'];

    if ($action === 'update') {
        $qty  = (int) $data['qty'];
        $stmt = $conn->prepare("UPDATE Cart SET quantity = ? WHERE userId = ? AND productId = ?");
        $stmt->bind_param("iii", $qty, $userId, $productId);
        $stmt->execute();

    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM Cart WHERE userId = ? AND productId = ?");
        $stmt->bind_param("ii", $userId, $productId);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
    exit;
}
?>