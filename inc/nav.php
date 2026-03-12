<?php
// $conn and $username already set by header.php
$cartCount = 0;
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM Cart WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $cartCount = $row['total'] ?? 0;
}
?>

<nav class="navbar navbar-expand-lg custom-navbar py-4">
    <div class="container-fluid px-4 px-lg-5">

        <!-- Logo -->
        <a class="navbar-brand" href="index.php">
            <img src="assets/Atheneum_logo.svg" alt="ATHENEUM" height="40">
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
                    <a class="nav-link" href="index.php">HOME</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">ABOUT US</a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        SHOP
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#">Books</a></li>
                        <li><a class="dropdown-item" href="#">Categories</a></li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link" href="#">FAQ</a>
                </li>
                
            </ul>

            <!-- Right Icons -->
            <div class="nav-icons d-flex align-items-center gap-3">
                <a href="#" class="icon-link"><i class="bi bi-search"></i></a>
                
                <?php if ($isLoggedIn): ?>
                    <a href="#" class="icon-link position-relative">
                        <i class="bi bi-heart"></i>
                        <span class="badge-circle">0</span>
                    </a>
                    <a href="cart.php" class="icon-link position-relative">
                        <i class="bi bi-bag"></i>
                        <span class="badge-circle" id="cart-count"><?= $cartCount ?></span>
                    </a>

                    <div class="dropdown">
                        <a href="#" class="icon-link d-flex align-items-center gap-2 dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <span style="font-size:0.85rem;font-weight:500"><?= htmlspecialchars($username) ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="process_logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline-dark btn-sm" style="font-size:0.78rem;letter-spacing:1px;border-radius:4px">Login</a>
                    <a href="register.php" class="btn btn-dark btn-sm" style="font-size:0.78rem;letter-spacing:1px;border-radius:4px">Sign Up</a>
                <?php endif; ?>                
            </div>
        </div>
    </div>
</nav>