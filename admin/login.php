<?php
$pageTitle = "Login";
$extraCSS = [
    "../assets/css/login.css"
];
$extraJS = [
    ["src" => "https://www.google.com/recaptcha/api.js", "async" => true, "defer" => true],
    ["src" => "assets/js/main.js", "defer" => true]
];

include 'inc/header.php'; // session_start + $conn both ready
include 'inc/nav.php'; 

$errorMsg = "";
if (isset($_SESSION['error'])) {
    $errorMsg = $_SESSION['error'];
    unset($_SESSION['error']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $recaptcha_secret = "6LeIwoYsAAAAAJWYbC-3bkRgoXnRFW32Gr-GsMfL";
    $recaptcha_response = $_POST['g-recaptcha-response'];
    $verify = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$recaptcha_secret}&response={$recaptcha_response}");
    $result = json_decode($verify);

    if (!$result->success) {
        $_SESSION['error'] = "Please complete the reCAPTCHA.";
        header("Location: dashboard.php");
        exit();
    } else {
        $username = "";
        $success = true;

        if (empty($_POST["username"])) {
            $errorMsg .= "Username is required.<br>";
            $success = false;
        } else {
            $username = sanitize_input($_POST["username"]);
        }

        if (empty($_POST["pwd"])) {
            $errorMsg .= "Password is required.<br>";
            $success = false;
        }

        if ($success) {
            authenticateUser($conn); // pass $conn in
        }

        if ($success) {
            session_write_close();
            header("Location: dashboard.php");
            exit();
        } else {
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
            $errorMsg = "Email not found or password doesn't match.";
            $success = false;
        } else {
            $_SESSION['username'] = $username;
            session_regenerate_id(true);
        }
    } else {
        $errorMsg = "Username not found or password doesn't match.";
        $success = false;
    }
    $stmt->close();
}
?>
<main>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h1>Sign in</h1>
            </div>
            <div class="error"><?php echo htmlspecialchars($errorMsg); ?></div>
            <form action="login.php" method="post">
                <div class="mb-3">
                <label class="form-label" for="username">Username:</label>
                <input required maxlength="45" class="form-control" type="text" id="username" name="username"
                placeholder="Enter username">
                </div>
                <div class="mb-3">
                <label class="form-label" for="pwd">Password:</label>
                <input required class="form-control" type="password" id="pwd" name="pwd"
                placeholder="Enter password">
                <a href="reset_password.php">Forgot your password?</a>
                </div>
                <div>
                    
                </div>
                <div class="g-recaptcha" data-sitekey="6LeIwoYsAAAAACMekBEfuTa75miLq2yIoLKgb8F-"></div>
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
