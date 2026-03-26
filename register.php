<?php
$pageTitle = "Register";
$extraCSS = [
    "assets/css/login.css"
];
include 'inc/conn.php'; 
include 'inc/header.php';
include "inc/nav.php";

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg[] = "Invalid request. Please reload the page and try again.";
        $success = false;
    }
    // Optionally regenerate the token after a successful check
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    
    if (empty($_POST["username"])) {
        $errorMsg[] = "Username is required.";
        $success = false;
    } else {
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
    } else {
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
    } else {
        if ($_POST["pwd"] !== $_POST["pwd_confirm"]) {
            $errorMsg[] = "Passwords do not match.";
            $success = false;
        } elseif (strlen($_POST["pwd"]) < 8) {
            $errorMsg[] = "Password must be at least 8 characters long.";
            $success = false;
        } else {
            $pwd_hashed = password_hash($_POST["pwd"], PASSWORD_DEFAULT);
        }
    }
    if ($success) {
        saveMemberToDB();
        
    }
    if ($success) {
        session_regenerate_id(true);
        $_SESSION['alert'] = "Account created successfully. Please login";
        header("Location: login.php");
            exit();
    }
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function saveMemberToDB() {
    global $conn, $username, $fname, $lname, $email, $pwd_hashed, $errorMsg, $success;

    if (!$conn) {
        $errorMsg[] = "Database connection failed.";
        $success = false;
        return;
    }

    // Check duplicate username or email
    $checkStmt = $conn->prepare("SELECT username FROM users WHERE username = ? OR email = ?");
    if (!$checkStmt) {
        $errorMsg[] = "Prepare failed: " . $conn->error;
        $success = false;
        return;
    }

    $checkStmt->bind_param("ss", $username, $email);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        $errorMsg[] = "Username already exists.";
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
        $errorMsg[] = "Prepare failed: " . $conn->error;
        $success = false;
        return;
    }

    $stmt->bind_param("sssss", $username, $fname, $lname, $email, $pwd_hashed);

    if (!$stmt->execute()) {
        $errorMsg[] = "Execute failed: " . $stmt->error;
        $success = false;
    }

    $stmt->close();
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
                <?php endforeach; ?></div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
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
