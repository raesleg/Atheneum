<?php
$pageTitle = "Admin Register";
$extraCSS = ["../assets/css/login.css"];

$extraJS = [["src" => "../assets/js/main.js", "defer" => true]];
include '../inc/conn.php'; 
include '../inc/header.php';
include "../inc/nav.php";

if ($isLoggedIn) {
    header("Location: ../index.php");
    exit();
}
//CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errorMsg = [];
$success = true;
if (isset($_SESSION['error'])) {
    $errorMsg = (array)$_SESSION['error'];
    unset($_SESSION['error']);
}

$adminRegData = $_SESSION['admin_reg_data'] ?? [];

$username = htmlspecialchars($adminRegData['username'] ?? '');
$email    = htmlspecialchars($adminRegData['email'] ?? '');
$fname    = htmlspecialchars($adminRegData['fname'] ?? '');
$lname    = htmlspecialchars($adminRegData['lname'] ?? '');

// --- Rate Limiting ---
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

$maxAttempts = 5;        // Max login attempts
$timeWindow = 60;       // 5 minutes in seconds

// Reset counter after time window
if (time() - $_SESSION['first_attempt_time'] > $timeWindow) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

// Check if user is temporarily blocked
if ($_SESSION['login_attempts'] >= $maxAttempts) {
    die("Too many login attempts. Please try again after". $timeWindow/60 . "minutes.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg[] = "Invalid request. Please reload the page and try again.";
        $success = false;
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    if (empty($_POST["username"])) {
        $errorMsg[] = "Username is required.";
        $success = false;
    } 
    else {
        $username = sanitize_input($_POST["username"]);
        if (!preg_match('/^[A-Za-z0-9_]{3,45}$/', $username)) {
            $errorMsg[] = "Username must be 3-45 characters and contain only letters, numbers, and underscores.";
            $success = false;
        }
    }
    if (!empty($_POST["fname"])) {
        $fname = sanitize_input($_POST["fname"]);
        if (!preg_match("/^[A-Za-z\s'-]{1,45}$/", $fname)) {
            $errorMsg[] = "Invalid first name format.";
            $success = false;
        }
    }
    if (empty($_POST["lname"])) {
        $errorMsg[] = "Last name is required.";
        $success = false;
    } 
    else {
        $lname = sanitize_input($_POST["lname"]);
        if (!preg_match("/^[A-Za-z\s'-]{1,45}$/", $lname)) {
            $errorMsg[] = "Invalid last name format.";
            $success = false;
        }
    }
    if (empty($_POST["email"])) {
        $errorMsg[] = "Email is required.";
        $success = false;
    } else {
        $email = sanitize_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg[] = "Invalid email format.";
            $success = false;
        }
    }
    if (empty($_POST["pwd"]) || empty($_POST["pwd_confirm"])) {
        $errorMsg[] = "Password is required.";
        $success = false;
    } 
    else {
        // password validation
        $pattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/';
        if ($_POST["pwd"] !== $_POST["pwd_confirm"]) {
            $errorMsg[] = "Passwords do not match.";
            $success = false;
        } 
        elseif (!preg_match($pattern, $_POST["pwd"])) {
            $errorMsg[] = "Password must be at least 8 characters long and contain uppercase, lowercase, numbers.";
            $success = false;
        } 
        else {
            $pwd_hashed = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
        }
    }
    if ($success) {
        $_SESSION['admin_reg_data'] = [
            'username' => $username,
            'email' => $email,
            'fname' => $fname,
            'lname' => $lname,
            'pwd_hashed' => $pwd_hashed
        ];
        header("Location: register_step2.php");
        exit();
    }
}
?>

<main>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Sign Up</h1>
            </div>
            <div class="error"><?php foreach ($errorMsg as $error): ?>
                <?php echo htmlspecialchars($error); ?>
                <?php endforeach; ?>
            </div>
            <form method="post">
                <div id="step1">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="mb-3">
                        <label class="form-label" for="username">Username:</label>
                        <input required maxlength="45" class="form-control" type="text" id="username" name="username" placeholder="Enter username" value="<?= $username ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="email">Email:</label>
                        <input required maxlength="45" class="form-control" type="email" id="email" name="email"
                        placeholder="Enter email" value="<?= $email ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="fname">First name:</label>
                        <input maxlength="45" class="form-control" type="text" id="fname" name="fname"
                        placeholder="Enter first name" value="<?= $fname ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="lname">Last name:</label>
                        <input required maxlength="45" class="form-control" type="text" id="lname" name="lname"
                        placeholder="Enter last name" value="<?= $lname ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pwd">Password:</label>
                        <input required minlength="8" maxlength="64" class="form-control" type="password" id="pwd" name="pwd"
                        placeholder="Enter password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pwd_confirm">Confirm Password:</label>
                        <input required minlength="8" maxlength="64" class="form-control" type="password" id="pwd_confirm" name="pwd_confirm"
                        placeholder="Confirm password">
                    </div>
                    <div class="mb-3 text-center" >
                        <button type="submit" class="btn btn-primary">Next</button>
                    </div>
                </div>
            </form>
            <div>Already have an account? <a href="../login.php">Sign in here! </a>
            </div>
        </div>
    </div>
</main>

<?php include '../inc/footer.php'; ?>
