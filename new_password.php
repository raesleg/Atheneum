
<?php
$pageTitle = "Reset Password";
$extraCSS = [
    "assets/css/login.css"
];
include 'inc/conn.php'; 
include 'inc/header.php';
include "inc/nav.php";

$errorMsg = [];
$success = true;
//CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$token = $_GET['token'] ?? '';
if (!$token) {
    $_SESSION['alert'] = "Invalid or missing reset link.";
    header("Location: login.php");
    exit();
}

// if (isset($_SESSION['error'])) {
//     $errorMsg = $_SESSION['error'];
//     unset($_SESSION['error']);
// }

$stmt = $conn->prepare("SELECT username, reset_expiry FROM users WHERE reset_token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    $_SESSION['alert'] = "Invalid reset link.";
    header("Location: login.php");
    exit();
}

if (strtotime($user['reset_expiry']) < time()) {
    $_SESSION['alert'] = "This reset link has expired.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $user['username'];
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg[] = "Invalid request.";
        $success = false;
    }
    if (empty($_POST["password_new"]) || empty($_POST["password_confirm"])) {
        $errorMsg[] = "Password is required.";
        $success = false;
    } else {
        if ($_POST["password_new"] !== $_POST["password_confirm"]) {
            $errorMsg[] = "Passwords do not match.";
            $success = false;
        } elseif (strlen($_POST["password_new"]) < 8) {
            $errorMsg[] = "Password must be at least 8 characters long.";
            $success = false;
        } else {
            $pwd_hashed = password_hash($_POST["password_new"], PASSWORD_DEFAULT);
        }
    }
    if ($success) {
        updatePassword();
    }

    $hashed = password_hash($_POST["password_new"], PASSWORD_DEFAULT);

    $_SESSION['alert'] = "Password updated. You can log in now.";
    header("Location: login.php");
    exit();
}

function updatePassword() {
    global $conn, $username, $pwd_hashed, $errorMsg, $success;

    if (!$conn) {
        $errorMsg[] = "Database connection failed.";
        $success = false;
        return;
    }

    $checkStmt = $conn->prepare("SELECT username FROM users WHERE username = ?");
    if (!$checkStmt) {
        $errorMsg[] = "Prepare failed: " . $conn->error;
        $success = false;
        return;
    }

    $checkStmt->bind_param("s", $username);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows === 0) {
        $errorMsg[] = "User not found.";
        $success = false;
        $checkStmt->close();
        return;
    }

    $checkStmt->close();

    // Update password
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    if (!$stmt) {
        $errorMsg[] = "Prepare failed: " . $conn->error;
        $success = false;
        return;
    }

    $stmt->bind_param("ss", $pwd_hashed, $username);

    if (!$stmt->execute()) {
        $errorMsg[] = "Execute failed: " . $stmt->error;
        $success = false;
    }

    $stmt->close();
}
?>
<html lang="en">
    <body>
        <main>
            <div class="container">
                <div class="card">
                    <div class="card-header">
                        <h1>Reset Password</h1>
                    </div>
                    <div class="error"><?php foreach ($errorMsg as $error): ?>
                    <?php echo htmlspecialchars($error); ?>
                <?php endforeach; ?></div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                        <label class="form-label" for="password_new">New Password:</label>
                        <input required class="form-control" type="password" id="password_new" name="password_new"
                        placeholder="Enter new password">
                        </div>
                        <div class="mb-3">
                        <label class="form-label" for="password_confirm">Confirm New Password:</label>
                        <input required class="form-control" type="password" id="password_confirm" name="password_confirm"
                        placeholder="Confirm new password">
                        </div>
                        <div class="mb-3 submit">
                        <button class="btn btn-primary" type="submit">Set Password</button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </main>
    </body>
</html>
