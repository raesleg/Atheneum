<?php
include 'inc/conn.php';
require_once __DIR__ . '/config/stripe.php';

$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

try {
    $event = \Stripe\Webhook::constructEvent(
        $payload,
        $sigHeader,
        $stripeWebhookSecret
    );
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

if ($event->type === 'checkout.session.completed') {
    $session = $event->data->object;

    $userId = isset($session->client_reference_id) ? (int)$session->client_reference_id : 0;
    $stripeSessionId = $session->id ?? null;
    $paymentIntentId = $session->payment_intent ?? null;

    if ($userId <= 0 || !$stripeSessionId) {
        http_response_code(400);
        echo 'Missing required session data';
        exit;
    }

    // Prevent duplicate processing if Stripe retries the webhook
    $check = $conn->prepare("SELECT orderId FROM Orders WHERE stripeSessionId = ? LIMIT 1");
    $check->bind_param("s", $stripeSessionId);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();

    if ($existing) {
        http_response_code(200);
        echo 'Already processed';
        exit;
    }

    // Re-read cart from DB
    $stmt = $conn->prepare("
        SELECT c.productId, c.quantity, p.price
        FROM Cart c
        JOIN Products p ON c.productId = p.productId
        WHERE c.userId = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $cartItems = [];
    $totalPrice = 0.00;

    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
        $totalPrice += ((float)$row['price'] * (int)$row['quantity']);
    }

    $shipping = $totalPrice > 50 ? 0.00 : 4.99;
    $grandTotal = $totalPrice + $shipping;

    if (empty($cartItems)) {
        http_response_code(400);
        echo 'Cart is empty';
        exit;
    }

    $conn->begin_transaction();

    try {
        // insert order
        $receiptUrl = null;

        $insertOrder = $conn->prepare("
            INSERT INTO Orders
                (userId, totalPrice, orderStatus, paymentStatus, stripeSessionId, paymentId, receiptUrl, paid_at)
            VALUES
                (?, ?, 'paid', 'paid', ?, ?, ?, NOW())
        ");
        $insertOrder->bind_param(
            "idsss",
            $userId,
            $grandTotal,
            $stripeSessionId,
            $paymentIntentId,
            $receiptUrl
        );
        $insertOrder->execute();

        $orderId = $conn->insert_id;

        // insert order items
        $insertItem = $conn->prepare("
            INSERT INTO OrderItems
                (orderId, productId, quantity, price_at_purchase)
            VALUES
                (?, ?, ?, ?)
        ");

        foreach ($cartItems as $item) {
            $productId = (int)$item['productId'];
            $quantity = (int)$item['quantity'];
            $priceAtPurchase = (float)$item['price'];

            $insertItem->bind_param(
                "iiid",
                $orderId,
                $productId,
                $quantity,
                $priceAtPurchase
            );
            $insertItem->execute();
        }

        // Decrement product stock
        $deductStock = $conn->prepare("
            UPDATE Products 
            SET quantity = GREATEST(0, quantity - ?) 
            WHERE productId = ?
        ");

        foreach ($cartItems as $item) {
            $productId = (int)$item['productId'];
            $quantity  = (int)$item['quantity'];
            $deductStock->bind_param("ii", $quantity, $productId);
            $deductStock->execute();
        }
        $deductStock->close();

        // refresh cart
        $clearCart = $conn->prepare("DELETE FROM Cart WHERE userId = ?");
        $clearCart->bind_param("i", $userId);
        $clearCart->execute();

        $conn->commit();

        http_response_code(200);
        echo 'Success';
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo 'Database error';
        exit;
    }
}

http_response_code(200);
echo 'Event ignored';