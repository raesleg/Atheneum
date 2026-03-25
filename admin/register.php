<?php
$pageTitle = "Profile";
$extraCSS = [
    "../assets/css/login.css"
];
include '../inc/header.php'; // session_start + $conn both ready

$conn = getDBConnection();
$errorMsg = "";
if (isset($_SESSION['error'])) {
    $errorMsg = $_SESSION['error'];
    unset($_SESSION['error']);
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $success = true;
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
    if ($success) {
        session_write_close();
        if ($role === 'admin') {
            header("Location: admin_dashboard.php");
            exit();
        } else {
            header("Location: index.php");
            exit();
        }
    }
    else{
        $_SESSION['error'] = $errorMsg;
            header("Location: ../register.php");
            exit();
    }
    
}
include "../inc/nav.php";
function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function saveMemberToDB() {
    global $conn, $username, $fname, $lname, $email, $pwd_hashed, $role, $errorMsg, $success;

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

    // Insert new admin
    $role = 'admin';
    $stmt = $conn->prepare("
        INSERT INTO users (username, fname, lname, email, password, role)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $errorMsg .= "Prepare failed: " . $conn->error . "<br>";
        $success = false;
        return;
    }

    $stmt->bind_param("ssssss", $username, $fname, $lname, $email, $pwd_hashed, $role);

    if (!$stmt->execute()) {
        $errorMsg .= "Execute failed: " . $stmt->error . "<br>";
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
            <div class="error"><?php echo $errorMsg; ?></div>
            <form method="post">
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
            <div>Already have an account? <a href="../login.php">Sign in here! </a>
            </div>
        </div>
    </div>
</main>

<?php include '../inc/footer.php'; ?>
