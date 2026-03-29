
<?php
require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$pageTitle = "Reset Password";
$extraCSS = [
    "assets/css/login.css"
];
$extraJS = [
    ["src" => "assets/js/main.js", "defer" => true] 
];

include 'inc/conn.php'; 
include 'inc/header.php';
// include "inc/nav.php";

//CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$alertMsg = "";
$errorMsg = [];
$success = false;
if (isset($_SESSION['error'])) {
    $errorMsg = (array)$_SESSION['error'];
    unset($_SESSION['error']);
}
if (isset($_SESSION['alert'])) {
    $alertMsg = $_SESSION['alert'];
    unset($_SESSION['alert']);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid request.";
        header("Location: reset_password.php");
        exit();
    }
    
    $email = $_POST['email'];
    $stmt = $conn->prepare("SELECT userId FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", time() + 3600); // set link expiry to 1 hour

        $stmt = $conn->prepare("UPDATE users SET reset_token=?, reset_expiry=? WHERE email=?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        // create reset link
        $link = "http://localhost:8000/new_password.php?token=" . $token;

        if (sendEmail($email, $link)) {
            $_SESSION['alert'] = "If this email exists, a reset link has been sent.";
            $success=true;
            
        } else {
            $_SESSION['alert'] = "Failed to send email.";
        }
    }
    else{
        $_SESSION['alert'] = "If this email exists, a reset link has been sent.";

    }
    header("Location: reset_password.php");
    exit();
}

function sendEmail($to, $link) {
    $mail = new PHPMailer(true);
    try{
        $mail->isSMTP();
        // $mail->Host = 'smtp.gmail.com';
        $mail->Host = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '2327ad7a4371df';
        $mail->Password = '834865c96d87bf';
        // Real emails
        // $mail->Username = 'cgxh125@gmail.com';
        // $mail->Password = 'fntm fpnz qekr zovh';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        $mail->isHTML(false);

        $mail->setFrom('no-reply@atheneum.com', 'Atheneum');
        $mail->addAddress($to);

        $mail->Subject = 'Password Reset';
        $mail->Body = "Click here to reset your password: \n$link";
        return $mail->send();
    } 
    catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
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
                        <h1>Reset Password</h1>
                    </div>
                    <div class="error"><?php foreach ($errorMsg as $error): ?>
                    <?php echo htmlspecialchars($error); ?>
                <?php endforeach; ?></div>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <div class="mb-3">
                        <label class="form-label" for="email">Email:</label>
                        <input required maxlength="45" class="form-control" type="email" id="email" name="email"
                        placeholder="Enter email">
                        </div>
                        <div class="mb-3 submit">
                        <button class="btn btn-primary" type="submit">Submit</button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </main>
<?php include 'inc/footer.php'; ?>