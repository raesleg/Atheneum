<?php 
$pageTitle = "Shopping Cart";
$extraCSS = [
    "assets/css/cart.css"
];
$extraJS = [
    ["src" => "assets/js/cart.js", "defer" => true]
];
include 'inc/conn.php'; 
include 'inc/header.php';
include 'inc/nav.php'; 

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare("
    SELECT c.cartId, c.quantity, c.productId,
           p.title, p.author, p.price, p.cover_image, p.quantity AS quantity_in_stock
    FROM Cart c
    JOIN Products p ON c.productId = p.productId
    WHERE c.userId = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$cart_items = [];
while ($row = $result->fetch_assoc()) {
    $cart_items[] = [
        'id'    => $row['productId'],
        'title' => $row['title'],
        'author'=> $row['author'],
        'price' => $row['price'],
        'qty'   => $row['quantity'],
        'cover' => $row['cover_image'],
        'stock' => $row['quantity_in_stock'],
    ];
}
$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart_items));
$shipping = $subtotal > 50 ? 0 : 4.99;
$total    = $subtotal + $shipping;
?>

<main id="main-content">
    <div class="page-header">
        <p class="eyebrow">Your Selection</p>
        <h1>Shopping Cart</h1>
    </div>

    <div class="cart-wrapper">
        <?php if (empty($cart_items)): ?>
        <div class="empty-cart">
            <i class="bi bi-bag"></i>
            <p>Your cart is empty.</p>
            <a href="index.php" class="checkout-btn">
                Continue Browsing
            </a>
        </div>

        <?php else: ?>

        <!-- Items Column -->
        <div class="cart-items-col">
            <p class="section-label"><?= count($cart_items) ?> item<?= count($cart_items) !== 1 ? 's' : '' ?></p>

            <?php foreach ($cart_items as $item): ?>
            <div class="cart-item" id="item-<?= $item['id'] ?>">
                <!-- Cover -->
                <?php if (!empty($item['cover'])): ?>
                    <img src="<?= htmlspecialchars(asset_url($item['cover'])) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="book-cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div class="book-cover-placeholder" style="display:none"><i class="bi bi-book"></i></div>
                <?php else: ?>
                    <div class="book-cover-placeholder"><i class="bi bi-book"></i></div>
                <?php endif; ?>

                <!-- Meta -->
                <div class="item-meta">
                    <p class="item-title"><?= htmlspecialchars($item['title']) ?></p>
                    <p class="item-author"><?= htmlspecialchars($item['author']) ?></p>
                    <p class="item-price-unit">$<?= number_format($item['price'], 2) ?> each</p>
                    <div class="qty-controls">
                        <button class="qty-btn" aria-label="Decrease quantity of <?= htmlspecialchars($item['title']) ?>" onclick="changeQty(<?= $item['id'] ?>, -1)">−</button>
                        <span class="qty-display" id="qty-<?= $item['id'] ?>"><?= $item['qty'] ?></span>
                        <button class="qty-btn" aria-label="Increase quantity of <?= htmlspecialchars($item['title']) ?>" onclick="changeQty(<?= $item['id'] ?>, 1)">+</button>
                    </div>
                </div>

                <!-- Price + Remove -->
                <div class="item-right">
                    <span class="item-total" id="total-<?= $item['id'] ?>">$<?= number_format($item['price'] * $item['qty'], 2) ?></span>
                    <button class="remove-btn" aria-label="Remove <?= htmlspecialchars($item['title']) ?> from cart" onclick="removeItem(<?= $item['id'] ?>, <?= $item['price'] ?>)">
                        <i class="bi bi-x"></i> Remove
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Summary Panel -->
        <aside class="summary-panel">
            <p class="summary-title">Order Summary</p>

            <div class="summary-row">
                <span>Subtotal</span>
                <span id="summary-subtotal">$<?= number_format($subtotal, 2) ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span id="summary-shipping">
                    <?php if ($shipping == 0): ?>
                        <span class="free-shipping-note">Free</span>
                    <?php else: ?>
                        $<?= number_format($shipping, 2) ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span id="summary-total">$<?= number_format($total, 2) ?></span>
            </div>

            <button class="checkout-btn" id="checkout-btn">Proceed to Checkout</button>
            <a href="products.php" class="continue-link">← Continue shopping</a>
        </aside>

        <?php endif; ?>
    </div>
</main>

<script>
    const prices = <?= json_encode(array_column($cart_items, 'price', 'id')) ?>;
    const qtys = <?= json_encode(array_column($cart_items, 'qty', 'id')) ?>;
    const stocks = <?= json_encode(array_column($cart_items, 'stock', 'id')) ?>;
</script>

<?php include 'inc/footer.php'; ?>
