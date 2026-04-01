<?php
include 'inc/conn.php';
require_once __DIR__ . '/config/stripe.php';

header('Content-Type: application/json');

$username   = $_SESSION['username'] ?? null;
$userId     = $_SESSION['userId'] ?? null;
$isLoggedIn = isset($username);

if (!$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$data = validate_csrf_json();
$newToken = $_SESSION['csrf_token'];

// Build base URL dynamically
$protocol        = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host            = $_SERVER['HTTP_HOST'];
$hostWithoutPort = explode(':', $host)[0];
$isLocal         = in_array($hostWithoutPort, ['localhost', '127.0.0.1']);
$basePath        = $isLocal ? '/Atheneum' : '';
$baseUrl         = $protocol . $host . $basePath;

$stmt = $conn->prepare("
    SELECT c.quantity, c.productId, p.title, p.author, p.price
    FROM Cart c
    JOIN Products p ON c.productId = p.productId
    WHERE c.userId = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
while ($row = $result->fetch_assoc()) {
    $cartItems[] = $row;
}

if (empty($cartItems)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit;
}

$subtotal = 0;
$lineItems = [];

foreach ($cartItems as $item) {
    $qty = (int)$item['quantity'];
    $price = (float)$item['price'];
    $subtotal += $price * $qty;

    $lineItems[] = [
        'price_data' => [
            'currency' => 'usd',
            'product_data' => [
                'name' => $item['title'],
                'description' => $item['author']
            ],
            'unit_amount' => (int) round($price * 100)
        ],
        'quantity' => $qty
    ];
}

$shipping = $subtotal > 50 ? 0 : 4.99;

if ($shipping > 0) {
    $lineItems[] = [
        'price_data' => [
            'currency' => 'usd',
            'product_data' => [
                'name' => 'Shipping'
            ],
            'unit_amount' => (int) round($shipping * 100)
        ],
        'quantity' => 1
    ];
}

try {
    $session = \Stripe\Checkout\Session::create([
        'mode' => 'payment',
        'line_items' => $lineItems,
        'success_url' => $baseUrl . '/cart.php?paid=1&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'  => $baseUrl . '/cart.php',
        'client_reference_id' => (int)$userId,
    ]);

    echo json_encode([
        'success' => true,
        'url' => $session->url
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
