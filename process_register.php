<?php
include 'inc/header.php';
include 'inc/nav.php';

$username = "";
$fname = "";
$lname = "";
$email = "";
$errorMsg = "";
$success = true;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    $errorMsg = "Invalid request.";
    $success = false;
} else {
    if (empty($_POST["username"])) {
        $errorMsg .= "Username is required.<br>";
        $success = false;
    } else {
        $username = sanitize_input($_POST["username"]);
        if (!preg_match('/^[A-Za-z0-9_]{3,45}$/', $username)) {
            $errorMsg .= "Username must be 3-45 characters and contain only letters, numbers, and underscores.<br>";
            $success = false;
        }
    }
    if (!empty($_POST["fname"])) {
        $fname = sanitize_input($_POST["fname"]);
        if (!preg_match("/^[A-Za-z\s'-]{1,45}$/", $fname)) {
            $errorMsg .= "Invalid first name format.<br>";
            $success = false;
        }
    }
    if (empty($_POST["lname"])) {
        $errorMsg .= "Last name is required.<br>";
        $success = false;
    } else {
        $lname = sanitize_input($_POST["lname"]);
        if (!preg_match("/^[A-Za-z\s'-]{1,45}$/", $lname)) {
            $errorMsg .= "Invalid last name format.<br>";
            $success = false;
        }
    }
    if (empty($_POST["email"])) {
        $errorMsg .= "Email is required.<br>";
        $success = false;
    } else {
        $email = sanitize_input($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg .= "Invalid email format.<br>";
            $success = false;
        }
    }
    if (empty($_POST["pwd"]) || empty($_POST["pwd_confirm"])) {
        $errorMsg .= "Password is required.<br>";
        $success = false;
    } else {
        if ($_POST["pwd"] !== $_POST["pwd_confirm"]) {
            $errorMsg .= "Passwords do not match.<br>";
            $success = false;
        } elseif (strlen($_POST["pwd"]) < 8) {
            $errorMsg .= "Password must be at least 8 characters long.<br>";
            $success = false;
        } else {
            $pwd_hashed = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
        }
    }
    if ($success) {
        saveMemberToDB();
    }
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function saveMemberToDB() {
    global $conn, $username, $fname, $lname, $email, $pwd_hashed, $errorMsg, $success;

    if (!$conn) {
        $errorMsg .= "Database connection failed.<br>";
        $success = false;
        return;
    }

    // Check duplicate username or email
    $checkStmt = $conn->prepare("SELECT username FROM users WHERE username = ? OR email = ?");
    if (!$checkStmt) {
        $errorMsg .= "Prepare failed: " . $conn->error . "<br>";
        $success = false;
        return;
    }

    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $errorMsg .= "Username or email already exists.<br>";
        $success = false;
        $checkStmt->close();
        return;
    }
    $checkStmt->close();

    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (username, fname, lname, email, password)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $errorMsg .= "Prepare failed: " . $conn->error . "<br>";
        $success = false;
        return;
    }

    $stmt->bind_param("sssss", $username, $fname, $lname, $email, $pwd_hashed);

    if (!$stmt->execute()) {
        $errorMsg .= "Execute failed: " . $stmt->error . "<br>";
        $success = false;
    }

    $stmt->close();
}
?>

<main class="container py-5">
    <div class="card mx-auto" style="max-width: 700px;">
        <div class="card-body text-center">
            <?php if ($success): ?>
                <h1>Your registration is successful</h1>
                <h4>Thank you for signing up, <?= htmlspecialchars($username) ?>!</h4>
                <br>
                <a href="login.php" class="btn btn-success">Log in</a>
            <?php else: ?>
                <h1>Oops!</h1>
                <h4>The following errors were detected:</h4>
                <p><?= $errorMsg ?></p>
                <a href="register.php" class="btn btn-danger">Return to register</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'inc/footer.php'; ?>