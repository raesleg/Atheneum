<?php 
include 'inc/header.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $action    = $data['action'];
    $productId = (int) $data['productId'];
    $sessionId = session_id();

    if ($action === 'update') {
        $qty  = (int) $data['qty'];
        $stmt = $conn->prepare("UPDATE Cart SET quantity = ? WHERE sessionId = ? AND productId = ?");
        $stmt->bind_param("isi", $qty, $sessionId, $productId);
        $stmt->execute();

    } elseif ($action === 'remove') {
        $stmt = $conn->prepare("DELETE FROM Cart WHERE sessionId = ? AND productId = ?");
        $stmt->bind_param("si", $sessionId, $productId);
        $stmt->execute();
    }

    echo json_encode(['success' => true]);
    exit; // ← important, stops the rest of the page from rendering
}

include 'inc/nav.php'; 
// not considering sessionid yet
$stmt = $conn->prepare("
    SELECT c.cartId, c.quantity, c.productId,
           p.title, p.author, p.price, p.cover_image
    FROM Cart c
    JOIN Products p ON c.productId = p.productId
");
// $stmt->bind_param("i", $userId); //replace with session
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
    ];
}

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['qty'], $cart_items));
$shipping = $subtotal > 50 ? 0 : 4.99;
$total    = $subtotal + $shipping;
?>

<!-- ── PAGE HEADER ── -->
<div class="page-header">
    <p class="eyebrow">Your Selection</p>
    <h1>Shopping Cart</h1>
</div>

<!-- ── MAIN CONTENT ── -->
<div class="cart-wrapper">

    <?php if (empty($cart_items)): ?>
    <div class="empty-cart">
        <i class="bi bi-bag"></i>
        <p>Your cart is empty.</p>
        <a href="index.php" class="checkout-btn" style="display:inline-block;width:auto;padding:14px 40px;text-decoration:none;">
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
                <img src="<?= htmlspecialchars($item['cover']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="book-cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
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
                    <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, -1)">−</button>
                    <span class="qty-display" id="qty-<?= $item['id'] ?>"><?= $item['qty'] ?></span>
                    <button class="qty-btn" onclick="changeQty(<?= $item['id'] ?>, 1)">+</button>
                </div>
            </div>

            <!-- Price + Remove -->
            <div class="item-right">
                <span class="item-total" id="total-<?= $item['id'] ?>">$<?= number_format($item['price'] * $item['qty'], 2) ?></span>
                <button class="remove-btn" onclick="removeItem(<?= $item['id'] ?>, <?= $item['price'] ?>)">
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
        <?php if ($subtotal < 50): ?>
        <div class="summary-row">
            <span style="font-size:0.72rem;color:#7aab7a">
                Add $<?= number_format(50 - $subtotal, 2) ?> more for free shipping
            </span>
        </div>
        <?php endif; ?>
        <div class="summary-row total">
            <span>Total</span>
            <span id="summary-total">$<?= number_format($total, 2) ?></span>
        </div>

        <div class="promo-row">
            <input type="text" class="promo-input" placeholder="Promo code">
            <button class="promo-btn">Apply</button>
        </div>

        <button class="checkout-btn">Proceed to Checkout</button>
        <a href="index.php" class="continue-link">← Continue shopping</a>
    </aside>

    <?php endif; ?>
</div>

<?php include 'inc/footer.php'; ?>

<script>
    // Item prices from PHP
    const prices = {
        <?php foreach ($cart_items as $item): ?>
        <?= $item['id'] ?>: <?= $item['price'] ?>,
        <?php endforeach; ?>
    }
    const qtys = {
        <?php foreach ($cart_items as $item): ?>
        <?= $item['id'] ?>: <?= $item['qty'] ?>,
        <?php endforeach; ?>
    }

    function changeQty(id, delta) {
        qtys[id] = Math.max(1, (qtys[id] || 1) + delta);
        document.getElementById('qty-' + id).textContent = qtys[id];
        document.getElementById('total-' + id).textContent = '$' + (prices[id] * qtys[id]).toFixed(2);
        recalc();

        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'update', productId: id, qty: qtys[id] })
        });
    }

    function removeItem(id) {
        const el = document.getElementById('item-' + id);
        el.classList.add('removing');

        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'remove', productId: id })
        }).then(() => {
            setTimeout(() => {
                el.remove();
                delete prices[id];
                delete qtys[id];
                recalc();
                updateCount();
            }, 300);
        });
    }
    function recalc() {
        let subtotal = 0;
        for (const id in prices) subtotal += prices[id] * qtys[id];
        const shipping = subtotal === 0 ? 0 : (subtotal > 50 ? 0 : 4.99);
        const total = subtotal + shipping;

        document.getElementById('summary-subtotal').textContent = '$' + subtotal.toFixed(2);
        document.getElementById('summary-shipping').innerHTML =
            shipping === 0 ? '<span class="free-shipping-note">Free</span>' : '$' + shipping.toFixed(2);
        document.getElementById('summary-total').textContent = '$' + total.toFixed(2);
    }

    function updateCount() {
        const remaining = Object.keys(prices).length;
        document.getElementById('cart-count').textContent = remaining;
    }
</script>
</body>
</html>