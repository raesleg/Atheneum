<?php
$pageTitle = "Profile";
$extraCSS = [
    "assets/css/login.css"
];
include 'inc/header.php'; // session_start + $conn both ready
include "inc/nav.php";
?>
<main>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Sign Up</h1>
            </div>
            <form action="process_register.php" method="post">
                <div class="mb-3">
                <label class="form-label" for="username">Username:</label>
                <input required maxlength="45" class="form-control" type="text" id="username" name="username"
                placeholder="Enter username">
                </div>
                <div class="mb-3">
                <label class="form-label" for="email">Email:</label>
                <input required maxlength="45" class="form-control" type="email" id="email" name="email"
                placeholder="Enter email">
                </div>
                <div class="mb-3">
                <label class="form-label" for="fname">First name:</label>
                <input maxlength="45" class="form-control" type="text" id="fname" name="fname"
                placeholder="Enter first name">
                </div>
                <div class="mb-3">
                <label class="form-label" for="lname">Last name:</label>
                <input required maxlength="45" class="form-control" type="text" id="lname" name="lname"
                placeholder="Enter last name">
                </div>
                <div class="mb-3">
                <label class="form-label" for="pwd">Password:</label>
                <input required class="form-control" type="password" id="pwd" name="pwd"
                placeholder="Enter password">
                </div>
                <div class="mb-3">
                <label class="form-label" for="pwd_confirm">Confirm Password:</label>
                <input required class="form-control" type="password" id="pwd_confirm" name="pwd_confirm"
                placeholder="Confirm password">
                </div>
                <div class="mb-3 submit">
                <button class="btn btn-primary" type="submit">Submit</button>
                </div>
            </form>
            <div>Already have an account? <a href="login.php">Sign in here! </a>
            </div>
        </div>
    </div>
</main>

<?php include 'inc/footer.php'; ?>
