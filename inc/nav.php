<?php
// $conn, $username, $userId, $isLoggedIn all set by header.php (via conn.php)
$cartCount = 0;
if ($isLoggedIn && $userId) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM Cart WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $cartCount = $row['total'] ?? 0;
    $stmt->close();
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar navbar-expand-lg custom-navbar py-4">
    <div class="container-fluid px-4 px-lg-5">

        <!-- Logo -->
        <a class="navbar-brand" href="<?= $baseUrl ?>/index.php">
            <img src="<?= $baseUrl ?>/assets/Atheneum_logo.svg" alt="ATHENEUM" height="40">
        </a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- Center Menu -->
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active-link' : '' ?>"
                       href="<?= $baseUrl ?>/index.php">HOME</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active-link' : '' ?>"
                       href="<?= $baseUrl ?>/about.php">ABOUT US</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($currentPage, ['products.php','book.php']) ? 'active-link' : '' ?>"
                       href="#" role="button" data-bs-toggle="dropdown">
                        SHOP
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/products.php">All Books</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/products.php?genre=Fiction+%26+Literature">Fiction &amp; Literature</a></li>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/products.php?genre=Non-Fiction+%26+Self+Help">Non-Fiction &amp; Self Help</a></li>
                        <li><a class="dropdown-item" href="<?= $baseUrl ?>/products.php?genre=Science+%26+Technology">Science &amp; Technology</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'faq.php' ? 'active-link' : '' ?>"
                       href="<?= $baseUrl ?>/faq.php">FAQ</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'contact.php' ? 'active-link' : '' ?>"
                       href="<?= $baseUrl ?>/contact.php">CONTACT</a>
                </li>

                <?php if ($isLoggedIn && ($_SESSION['role'] ?? '') === 'admin'): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'dashboard.php' ? 'active-link' : '' ?>"
                       href="<?= $baseUrl ?>/admin/dashboard.php"
                       style="color:var(--gold)!important;font-weight:600;">
                        <i class="bi bi-speedometer2 me-1"></i>DASHBOARD
                    </a>
                </li>
                <?php endif; ?>
            </ul>

            <!-- Right Icons -->
            <div class="nav-icons d-flex align-items-center gap-3">
                <a href="<?= $baseUrl ?>/products.php" class="icon-link">
                    <i class="bi bi-search"></i>
                </a>

                <?php if ($isLoggedIn): ?>
                    <a href="<?= $baseUrl ?>/cart.php" class="icon-link position-relative">
                        <i class="bi bi-bag"></i>
                        <span class="badge-circle" id="cart-count"><?= $cartCount ?></span>
                    </a>

                    <div class="dropdown">
                        <a href="#" class="icon-link d-flex align-items-center gap-2 dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span style="font-size:0.85rem;font-weight:500"><?= htmlspecialchars($username) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <li><a class="dropdown-item" href="<?= $baseUrl ?>/admin/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Admin Dashboard</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= $baseUrl ?>/process_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/login.php" class="btn btn-outline-dark btn-sm" style="font-size:0.78rem;letter-spacing:1px;border-radius:4px">Login</a>
                    <a href="<?= $baseUrl ?>/register.php" class="btn btn-dark btn-sm" style="font-size:0.78rem;letter-spacing:1px;border-radius:4px">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
