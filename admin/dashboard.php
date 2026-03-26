<?php
$pageTitle = "Admin Dashboard";
$extraCSS = ["css/dashboard.css"];
include '../inc/conn.php'; 
include '../inc/header.php';

$isLoggedIn = false;
if (isset($_SESSION['loggedin'])) {
    $isLoggedIn = $_SESSION['loggedin'];
    
}
// Admin only
if ($isLoggedIn != true) {
    $_SESSION['alert'] = "Please login.";
    header("Location: ../login.php");
    exit();
}
$stmt = $conn->prepare("SELECT role FROM Users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row || $row['role'] !== 'admin') {
    $_SESSION['alert'] = "Invalid access.";
    header("Location: ../index.php"); 
    exit();
}

// Fetch products
$products = $conn->query("SELECT * FROM Products ORDER BY created_at DESC");

// Refund functionality not implemented yet
$flash = '';
if (isset($_SESSION['admin_flash'])) {
    $flash = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
}
?>

<?php include '../inc/nav.php'; ?>

<div class="admin-wrapper">
    <div class="admin-header">
        <h1>Admin Dashboard</h1>
        <span class="badge-admin">Admin</span>
    </div>

    <?php if ($flash): ?>
        <div class="flash-msg <?= strpos($flash, 'success') !== false ? 'flash-success' : 'flash-error' ?>">
            <?= htmlspecialchars($flash) ?>
        </div>
    <?php endif; ?>

    <div class="section-title">
        <i class="bi bi-arrow-counterclockwise"></i>
        Refund Requests
        <?php
            $refundArr = []; // Fetch refund data to do later
            if (count($refundArr) > 0): ?>
            <span class="count-badge"><?= count($refundArr) ?></span>
        <?php endif; ?>
    </div>

    <?php if (count($refundArr) === 0): ?>
        <div class="empty-state">
            <i class="bi bi-check-circle" style="font-size:1.8rem;color:var(--success);display:block;margin-bottom:8px"></i>
            No refund requests at this time.
        </div>
    <?php else: ?>
        <table class="refund-table">
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Order Status</th>
                    <th>Payment Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($refundArr as $r): ?>
                <tr>
                    <td>#<?= $r['orderId'] ?></td>
                    <td><?= htmlspecialchars($r['username']) ?></td>
                    <td>$<?= number_format($r['totalPrice'], 2) ?></td>
                    <td><span class="status-pill <?= $r['orderStatus'] ?>"><?= $r['orderStatus'] ?></span></td>
                    <td><span class="status-pill <?= $r['paymentStatus'] ?>"><?= $r['paymentStatus'] ?></span></td>
                    <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="products-header">
        <div class="section-title" style="margin-bottom:0">
            <i class="bi bi-book"></i> Books
        </div>
        <a href="add_book.php" class="btn-add-book">
            <span class="plus">+</span> Add Book
        </a>
    </div>

    <?php if ($products->num_rows === 0): ?>
        <div class="empty-state">
            <i class="bi bi-book" style="font-size:1.8rem;color:var(--muted);display:block;margin-bottom:8px"></i>
            No books yet. Click <strong>+ Add Book</strong> to get started.
        </div>
    <?php else: ?>
        <div class="products-grid">
            <?php while ($p = $products->fetch_assoc()): ?>
            <div class="product-card">
                <div class="cover">
                    <?php if ($p['cover_image']): ?>
                        <img src="../<?= htmlspecialchars($p['cover_image']) ?>" alt="cover">
                    <?php else: ?>
                        <i class="bi bi-book"></i>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="book-title" title="<?= htmlspecialchars($p['title']) ?>"><?= htmlspecialchars($p['title']) ?></div>
                    <div class="book-author"><?= htmlspecialchars($p['author']) ?></div>
                    <div>
                        <span class="book-price">$<?= number_format($p['price'], 2) ?></span>
                        <span class="book-stock">Stock: <?= $p['quantity'] ?></span>
                    </div>
                    <div class="card-actions">
                        <a href="edit_book.php?id=<?= $p['productId'] ?>" class="btn-sm-edit"><i class="bi bi-pencil"></i> Edit</a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

</div>

<?php include '../inc/footer.php'; ?>
