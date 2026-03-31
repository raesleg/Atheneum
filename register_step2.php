<?php
$pageTitle = "Register";
$extraCSS = [
    "assets/css/login.css"
];

$extraJS = [
    ["src" => "https://www.google.com/recaptcha/api.js", "async" => true, "defer" => true],
    ["src" => "assets/js/main.js", "defer" => true]
];
include 'inc/conn.php'; 
include 'inc/header.php';
include "inc/nav.php";

if ($isLoggedIn) {
    header("Location: index.php");
    exit();
}
if (!isset($_SESSION['reg_data'])) {
    header("Location: register.php");
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

// --- Rate Limiting ---
if (!isset($_SESSION['register_attempts'])) {
    $_SESSION['register_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

$maxAttempts = 5;        // Max attempts
$timeWindow = 2600;       // seconds

// Reset counter after time window
if (time() - $_SESSION['first_attempt_time'] > $timeWindow) {
    $_SESSION['register_attempts'] = 0;
    $_SESSION['first_attempt_time'] = time();
}

// Check if user is temporarily blocked
if ($_SESSION['register_attempts'] >= $maxAttempts) {
    die("Too many registration attempts. Please try again after". $timeWindow/60 . "minutes.");
}

$username = $_SESSION['reg_data']['username'];
$email = $_SESSION['reg_data']['email'];
$fname = $_SESSION['reg_data']['fname'];
$lname = $_SESSION['reg_data']['lname'];
$pwd_hashed = $_SESSION['reg_data']['pwd_hashed'];
$profilePicPath = 'assets/images/default-avatar.jpg';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg[] = "Invalid request. Please reload the page and try again.";
        $success = false;
    }
    if ($success) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } 
    // Recaptcha
    $recaptcha_secret = "6LdCK5wsAAAAAC12fTpTk88DcWeDc5niNbSWNbLd";
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
    $result = json_decode($verify);

    if (!$result->success) {
        $errorMsg[] = "Please complete the reCAPTCHA.";
    } 
    else {
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === 0) {
            $checkMime = getimagesize($_FILES['profile_pic']['tmp_name']);
            $fileType = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
            $allowTypes = ['jpg','jpeg','png','gif'];

            // check file type
            if ($checkMime === false || !in_array($fileType, $allowTypes)) {
                $errorMsg[] = "Invalid file type. Only images are allowed.";
                $success = false;
            }

            // check file size is <2mb
            if ($_FILES['profile_pic']['size'] > 2 * 1024 * 1024) {
                $errorMsg[] = "File too large (max 2MB).";
                $success = false;
            }
            $uploadDir = 'uploads/profile_pic/';
            
            if (!is_dir($uploadDir)){
                mkdir($uploadDir, 0755, true);
            } 

            // Ensure file does not contain any invalid characters
            $fileName = time() . '_' . preg_replace("/[^a-zA-Z0-9_\.-]/", "_", basename($_FILES['profile_pic']['name']));
            $targetFilePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $targetFilePath)) {
                $profilePicPath = $targetFilePath;
            } else {
                $errorMsg[] = "Error uploading file.";
                $success = false;
                exit();
            }
            
        }
        if ($success) { 
            saveMemberToDB();
        }
        if ($success) {
            unset($_SESSION['reg_data']);
            session_regenerate_id(true);
            $_SESSION['register_attempts'] += 1;
            $_SESSION['alert'] = "Account created successfully. Please login";
            header("Location: login.php");
            exit();
        }
    }
}

function sanitize_input($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

function saveMemberToDB() {
    global $conn, $username, $fname, $lname, $email, $pwd_hashed, $errorMsg, $profilePicPath, $success;
    if (!$conn) {
        $errorMsg[] = "Database connection failed.";
        $success = false;
        return;
    }

    // Check duplicate username or email
    $checkStmt = $conn->prepare("SELECT username FROM Users WHERE username = ? OR email = ?");
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
        INSERT INTO Users (username, fname, lname, email, password, profile_pic)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $errorMsg[] = "Prepare failed: " . $conn->error;
        $success = false;
        return;
    }

    $stmt->bind_param("ssssss", $username, $fname, $lname, $email, $pwd_hashed, $profilePicPath);

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
                <?php endforeach; ?>
            </div>
            <form method="post"  enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <div id="step2">
                    <div class="mb-3 text-center">
                    <img id="imgPreview" src="assets/images/default-avatar.jpg" alt="Profile Preview">
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="profile_pic">Select Image (JPG, PNG):</label>
                        <input class="form-control" type="file" id="profile_pic" name="profile_pic" 
                        accept="image/*">
                    </div>
                    <div class="g-recaptcha text-center" data-sitekey="6LdCK5wsAAAAAF-um6W9E8AJCCQh8rLHjr2F9gkF"></div>
                    <div class="mb-3 d-flex justify-content-between">
                        <a href="register.php" class="btn btn-secondary">Back</a>
                        <!-- <div class="g-recaptcha" data-sitekey="6LdCK5wsAAAAAF-um6W9E8AJCCQh8rLHjr2F9gkF"></div> -->
                        <button class="btn btn-success" type="submit">Register</button>
                    </div>
                </div>
            </form>
            <div>Already have an account? <a href="login.php">Sign in here! </a>
            </div>
        </div>
    </div>
</main>

<?php include 'inc/footer.php'; ?>
