<?php
// $conn and $sessionId already set by header.php
$cartCount = 0;
$stmt = $conn->prepare("SELECT SUM(quantity) as total FROM Cart"); //sessionid
// $stmt->bind_param("s", $sessionId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$cartCount = $row['total'] ?? 0;
?>

<nav class="navbar navbar-expand-lg custom-navbar py-4">
    <div class="container-fluid px-4 px-lg-5">

        <!-- Logo -->
        <a class="navbar-brand brand-logo" href="index.php">ATHENEUM</a>

        <!-- Mobile Toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="mainNavbar">

            <!-- Center Menu -->
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-lg-4">
                <li class="nav-item">
                    <a class="nav-link" href="#">HOME</a>
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

                <a href="#" class="icon-link position-relative">
                    <i class="bi bi-heart"></i>
                    <span class="badge-circle">0</span>
                </a>

                <a href="cart.php" class="icon-link position-relative">
                    <i class="bi bi-bag"></i>
                    <span class="badge-circle"><?= $cartCount ?></span>
                </a>

                <a href="#" class="icon-link"><i class="bi bi-person"></i></a>
            </div>
        </div>
    </div>
</nav>