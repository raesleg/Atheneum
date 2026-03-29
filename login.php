<?php
$pageTitle = "Login";
$extraCSS = [
    "assets/css/login.css"
];
$extraJS = [
    ["src" => "https://www.google.com/recaptcha/api.js", "async" => true, "defer" => true],
    ["src" => "assets/js/main.js", "defer" => true]
];
include 'inc/conn.php'; 
include 'inc/header.php';


//CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$alertMsg = "";
$errorMsg = [];
$success = true;
if (isset($_SESSION['error'])) {
    $errorMsg = (array)$_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['alert'])) {
    $alertMsg = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

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
    die("Too many login attempts. Please try again after"+$timeWindow/60+ "minutes.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg[] = "Invalid request. Please reload the page and try again.";
        $success = false;
    }
    // Optionally regenerate the token after a successful check
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $recaptcha_secret = "6LdCK5wsAAAAAC12fTpTk88DcWeDc5niNbSWNbLd";
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
    $result = json_decode($verify);

    if (!$result->success) {
        $_SESSION['error'] = "Please complete the reCAPTCHA.";
        header("Location: login.php");
        exit();
    } else {
        $username = "";
        $success = true;

        if (empty($_POST["username"])) {
            $errorMsg[] = "Username is required.";
            $success = false;
        } else {
            $username = sanitize_input($_POST["username"]);
        }

        if (empty($_POST["pwd"])) {
            $errorMsg[] = "Password is required.";
            $success = false;
        }

        if ($success) {
            authenticateUser($conn); // pass $conn in
        }

        if ($success) {
            session_write_close();
            if ($_SESSION['role'] === 'admin') {
                header("Location: admin/dashboard.php");
                exit();
            } else {
                header("Location: index.php");
                exit();
            }
        }
        else {
            $_SESSION['error'] = $errorMsg;
            header("Location: login.php");
            exit();
        }
    }
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function authenticateUser($conn) { // receives $conn
    global $username, $fname, $lname, $email, $pwd_hashed, $errorMsg, $success;

    $stmt = $conn->prepare("SELECT * FROM Users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $username   = $row["username"];
        $fname      = $row["fname"];
        $lname      = $row["lname"];
        $email      = $row["email"];
        $pwd_hashed = $row["password"];

        if (!password_verify($_POST["pwd"], $pwd_hashed)) {
            $errorMsg[] = "Incorrect username or password.";
            $success = false;
            $_SESSION['login_attempts'] += 1;
        } else {
            session_regenerate_id(true);
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $row['role'];
            $_SESSION['loggedin'] = true;
            $success = true;
        }
    } else {
        $errorMsg[] = "Incorrect username or password.";
        $success = false;
        $_SESSION['login_attempts'] += 1;
    }
    $stmt->close();
}


?>
<?php if ($alertMsg): ?>
        <div class="alert alert-primary alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($alertMsg); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php include 'inc/nav.php'; ?>
    
<main>
    
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Sign in</h1>
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
                <label class="form-label" for="pwd">Password:</label>
                <input required minlength="8" maxlength="64" class="form-control" type="password" id="pwd" name="pwd"
                placeholder="Enter password">
                <a href="reset_password.php">Forgot your password?</a>
                </div>
                <div>
                    
                </div>
                <div class="g-recaptcha" data-sitekey="6LdCK5wsAAAAAF-um6W9E8AJCCQh8rLHjr2F9gkF"></div>
                <div class="mb-3 submit">
                <button class="btn btn-primary" type="submit">Submit</button>
                </div>
            </form>
            <div>Don't have an account? <a href="register.php">Sign up here! </a>
            </div>
        </div>
    </div>
</main>

<?php include 'inc/footer.php'; ?>
