<?php
$pageTitle = "Admin Dashboard";
$extraCSS  = ["admin/css/dashboard.css"];
$extraJS   = [["src" => "../assets/js/dashboard.js", "defer" => true]];
include '../inc/conn.php';
include '../inc/header.php';

$alertMsg="";
if (isset($_SESSION['alert'])) {
    $alertMsg = $_SESSION['alert'];
    unset($_SESSION['alert']);
}
$isLoggedIn = $_SESSION['loggedin'] ?? false;
if (!$isLoggedIn) {
    $_SESSION['alert'] = "Please login.";
    header("Location: ../login.php"); exit();
}

// CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$stmt = $conn->prepare("SELECT role FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$row || $row['role'] !== 'admin') {
    $_SESSION['alert'] = "Invalid access.";
    header("Location: ../index.php"); exit();
}

// Fetch products
$products = $conn->query("SELECT * FROM Products ORDER BY created_at DESC");

// Fetch pending refunds
$refundResult = $conn->query("
    SELECT r.refundId, r.orderId, r.reason, r.status, r.created_at,
           o.totalPrice, u.username
    FROM Refund r
    JOIN Orders o ON r.orderId = o.orderId
    JOIN Users  u ON r.userId  = u.userId
    WHERE r.status = 'pending'
    ORDER BY r.created_at ASC
");
$refundArr = $refundResult ? $refundResult->fetch_all(MYSQLI_ASSOC) : [];

$flash = '';
if (isset($_SESSION['admin_flash'])) {
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
}
?>

<?php include '../inc/nav.php'; ?>
<!-- alert display -->
<?php if ($alertMsg): ?>
    <div class="alert alert-primary alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($alertMsg); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<main class="admin-wrapper">
    <header class="admin-header">
        <h1>Admin Dashboard</h1>
        <span class="badge-admin">Admin</span>
    </header>

    <?php if ($flash): ?>
        <div class="flash-msg <?= strpos($flash, 'success') !== false ? 'flash-success' : 'flash-error' ?>" role="status">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <!--Refund Requests-->
    <div class="section-title">
        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
        Refund Requests
        <?php if (count($refundArr) > 0): ?>
            <span class="count-badge" role="status" aria-label="<?= count($refundArr) ?> pending"><?= count($refundArr) ?></span>
        <?php endif; ?>
    </div>

    <?php if (count($refundArr) === 0): ?>
        <div class="empty-state" role="status">
            <i class="bi bi-check-circle" style="font-size:1.8rem;color:var(--success);display:block;margin-bottom:8px" aria-hidden="true"></i>
            No refund requests at this time.
        </div>
    <?php else: ?>
        <ul class="refund-list-box" aria-label="Pending refund requests">
            <?php foreach ($refundArr as $r): ?>
            <li>
                <button
                    class="refund-row"
                    aria-label="Refund request from <?= htmlspecialchars($r['username']) ?>, order #<?= $r['orderId'] ?>, $<?= number_format($r['totalPrice'], 2) ?>"
                    data-refund='<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>'
                    onclick="openRefundPanel(this)"
                >
                <span class="rr-left">
                    <span class="rr-user"><?= htmlspecialchars($r['username']) ?></span>
                    <span class="rr-meta">Order #<?= $r['orderId'] ?> &middot; <?= date('d M Y', strtotime($r['created_at'])) ?></span>
                </span>
                <span class="rr-right">
                    <span class="rr-amount">$<?= number_format($r['totalPrice'], 2) ?></span>
                    <i class="bi bi-chevron-right rr-arrow" aria-hidden="true"></i>
                </span>
                </button>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <!--Books-->
    <div class="products-header">
        <div class="section-title" style="margin-bottom:0">
            <i class="bi bi-book" aria-hidden="true"></i> Books
        </div>
        <a href="add_book.php" class="btn-add-book">
            <span class="plus" aria-hidden="true">+</span> Add Book
        </a>
    </div>

    <?php if ($products->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-book" style="font-size:1.8rem;color:var(--muted);display:block;margin-bottom:8px" aria-hidden="true"></i>
            No books yet. Click <strong>+ Add Book</strong> to get started.
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php while ($p = $products->fetch_assoc()): ?>
            <div class="product-card">
                <div class="cover">
                    <?php if ($p['cover_image']): ?>
                        <img src="../<?= htmlspecialchars(asset_url($p['cover_image'])) ?>" alt="cover">
                    <?php else: ?>
                        <i class="bi bi-book" aria-hidden="true"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="book-title" title="<?= htmlspecialchars($p['title']) ?>"><?= htmlspecialchars($p['title']) ?></div>
                    <div class="book-author"><?= htmlspecialchars($p['author']) ?></div>
                    <div>
                        <span class="book-price">$<?= number_format($p['price'], 2) ?></span>
                        <span class="book-stock">Stock: <?= htmlspecialchars((string)$p['quantity']) ?></span>
                    </div>
                    <div class="card-actions">
                        <a href="edit_book.php?id=<?= $p['productId'] ?>" class="btn-sm-edit"><i class="bi bi-pencil" aria-hidden="true"></i> Edit</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</main>

<div id="refund-backdrop" class="refund-backdrop" aria-hidden="true" onclick="closeRefundPanel()"></div>

<div
    id="refund-panel"
    class="refund-panel"
    role="dialog"
    aria-modal="true"
    aria-labelledby="rp-title"
    aria-hidden="true"
    tabindex="-1"
>
    <div class="rp-header">
        <h2 id="rp-title">Refund Request</h2>
        <button class="rp-close" onclick="closeRefundPanel()" aria-label="Close refund panel">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
        </button>
    </div>

    <div class="rp-body">
        <div class="rp-meta-grid">
            <div class="rp-meta-item">
                <span class="rp-meta-label">Customer</span>
                <span class="rp-meta-value" id="rp-username">—</span>
            </div>
            <div class="rp-meta-item">
                <span class="rp-meta-label">Order #</span>
                <span class="rp-meta-value" id="rp-orderid">—</span>
            </div>
            <div class="rp-meta-item">
                <span class="rp-meta-label">Amount</span>
                <span class="rp-meta-value" id="rp-amount">—</span>
            </div>
            <div class="rp-meta-item">
                <span class="rp-meta-label">Submitted</span>
                <span class="rp-meta-value" id="rp-date">—</span>
            </div>
        </div>

        <div class="rp-section">
            <p class="rp-section-label">Customer's Reason</p>
            <p class="rp-reason-text" id="rp-reason">—</p>
        </div>

        <div class="rp-section">
            <label for="rp-note" class="rp-section-label">
                Message to Customer <span class="rp-note-hint">(optional — visible on their Order History)</span>
            </label>
            <textarea id="rp-note" class="rp-note-input" rows="3" placeholder="Add a message for the customer, e.g., 'Refund processed.' or 'We need more information.'"></textarea>
        </div>

        <div id="rp-feedback" class="rp-feedback" role="status" aria-live="polite"></div>
    </div>

    <div class="rp-footer">
        <button class="rp-btn rp-btn-reject" id="rp-reject-btn" onclick="submitRefundAction('reject')">
            <i class="bi bi-x-circle" aria-hidden="true"></i> Reject
        </button>
        <button class="rp-btn rp-btn-approve" id="rp-approve-btn" onclick="submitRefundAction('approve')">
            <i class="bi bi-check-circle" aria-hidden="true"></i> Approve Refund
        </button>
    </div>
</div>


<script>let CSRF_TOKEN = <?= json_encode($_SESSION['csrf_token']) ?>;</script>

<?php include '../inc/footer.php'; ?>