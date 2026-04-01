<?php 
$pageTitle = "Shopping Cart";
$extraCSS = [
    "assets/css/cart.css"
];
$extraJS = [
    ["src" => "assets/js/cart.js?v=" . time(), "defer" => true]
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
            <?php
                $totalItems = 0;
                foreach ($cart_items as $item) {
                    $totalItems += (int)$item['qty'];
                }
            ?>
            <p id="cart-item-count" class="section-label"><?= $totalItems ?> item<?= $totalItems !== 1 ? 's' : '' ?></p>

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

<!-- ── Address Modal ── -->
<div class="addr-overlay" id="addrOverlay" hidden>
    <div class="addr-modal" role="dialog" aria-modal="true" aria-labelledby="addrModalTitle">
 
        <div class="addr-modal-header">
            <h2 id="addrModalTitle">Select Shipping Address</h2>
            <button class="addr-close" id="closeAddrModal" aria-label="Close">&times;</button>
        </div>
 
        <!-- LIST VIEW -->
        <div id="addrListView">
            <div id="addrCards" class="addr-cards">
                <div class="addr-loading"><i class="bi bi-arrow-repeat addr-spin"></i> Loading…</div>
            </div>
            <button class="addr-add-btn" id="showAddrForm">
                <i class="bi bi-plus-circle"></i> Add a new address
            </button>
            <div id="addrSelectError" class="addr-select-error"></div>
            <button class="addr-confirm-btn" id="confirmAddrBtn">Continue to Payment</button>
        </div>
 
        <!-- FORM VIEW -->
        <div id="addrFormView" style="display:none">
            <button class="addr-back-btn" id="backToList" type="button">
                <i class="bi bi-arrow-left"></i> Back
            </button>
            <form id="addressForm" novalidate>
                <div class="addr-field">
                    <label for="addr_label">Label <span class="req">*</span></label>
                    <input type="text" id="addr_label" name="label" placeholder="Home, Work, Dorm" required>
                </div>
                <div class="addr-field">
                    <label for="addr_line1">Address Line 1 <span class="req">*</span></label>
                    <input type="text" id="addr_line1" name="address_line1" placeholder="123 Main Street" required>
                </div>
                <div class="addr-field">
                    <label for="addr_line2">Address Line 2 <span class="opt">(optional)</span></label>
                    <input type="text" id="addr_line2" name="address_line2" placeholder="Apt, suite, unit, etc.">
                </div>
                <div class="addr-row">
                    <div class="addr-field">
                        <label for="addr_city">City <span class="req">*</span></label>
                        <input type="text" id="addr_city" name="city" placeholder="New York" required>
                    </div>
                    <div class="addr-field">
                        <label for="addr_state">State / Region</label>
                        <input type="text" id="addr_state" name="state" placeholder="NY">
                    </div>
                </div>
                <div class="addr-row">
                    <div class="addr-field">
                        <label for="addr_postal">Postal Code <span class="req">*</span></label>
                        <input type="text" id="addr_postal" name="postal_code" placeholder="10001" required>
                    </div>
                    <div class="addr-field">
                        <label for="addr_country">Country <span class="req">*</span></label>
                        <input type="text" id="addr_country" name="country" placeholder="United States" required>
                    </div>
                </div>
                <div id="addrFormError" class="addr-select-error"></div>
                <div class="addr-form-actions">
                    <button type="button" class="addr-cancel-btn" id="cancelAddrForm">Cancel</button>
                    <button type="submit" class="addr-save-btn" id="saveAddrBtn">Save Address</button>
                </div>
            </form>
        </div>
 
    </div>
</div>

<script>
    const prices = <?= json_encode(array_column($cart_items, 'price', 'id')) ?>;
    const qtys = <?= json_encode(array_column($cart_items, 'qty', 'id')) ?>;
    const stocks = <?= json_encode(array_column($cart_items, 'stock', 'id')) ?>;
</script>

<?php include 'inc/footer.php'; ?>
