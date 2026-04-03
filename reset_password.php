
<?php
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';
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
    $stmt = $conn->prepare("SELECT userId FROM Users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $token = bin2hex(random_bytes(32));
        $expiry = date("Y-m-d H:i:s", time() + 3600); // set link expiry to 1 hour

        $stmt = $conn->prepare("UPDATE Users SET reset_token=?, reset_expiry=? WHERE email=?");
        $stmt->bind_param("sss", $token, $expiry, $email);
        $stmt->execute();

        // create reset link
        $link = $baseUrl . "/new_password.php?token=" . $token;
        $htmlContent = "
        <html>
        <head>
            <style>
                @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=DM+Sans:wght@400;500&display=swap');
                .email-container { font-family: 'DM Sans', 'Helvetica Neue', Arial, sans-serif; max-width: 600px; margin: 20px auto; border: 1px solid #e1e4e8; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
                .header { background-color: #1a2c5b; padding: 30px; text-align: center; }
                .header h1 { margin: 0; font-family: 'Playfair Display', 'Georgia', serif; color: #ffffff; font-size: 32px; font-weight: 600; letter-spacing: 3px;}
                .content { padding: 40px; background: #fff; }
                .footer { padding: 25px; text-align: center; font-size: 13px; color: #777; background: #f9f9f9; border-top: 1px solid #eee; }
                hr { border: none; border-top: 1px solid #eee; margin: 25px 0; }
            </style>
        </head>
        <body style='background:#f6f6f6; padding:40px 0; margin:0;'>
            <div class='email-container'>
                <div class='header'>
                    <h1 style='margin: 0; font-family: \"Playfair Display\", serif; color: #ffffff; font-size: 28px; letter-spacing: 2px;'>Atheneum</h1>
                </div>
                <div class='content'>
                    <p style='font-size: 18px; font-weight: 500;'>Hello</p>
                    <div style='white-space: pre-wrap; font-size: 15px; color: #444;'>Please click here to reset your password \n" . $link . " </div>
                    <hr>
                    <p style='font-size: 14px;'>If you didn't make this request, just ignore this email.</p>
                    <p>Best Regards,<br><strong>Atheneum Support Team</strong></p>
                </div>
                <div class='footer'>
                    <p style='margin: 0;'>&copy; 2026 Atheneum Book Store, PTE LTD. All rights reserved.</p>
                    <p style='margin-top: 6px; font-size: 11px; color: #aaa; opacity: 0.6;'>Reference: AT-" . date('YmdHis') . "</p>
                </div>
            </div>
        </body>
        </html>";

        if (sendEmail($email, $htmlContent)) {
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

function sendEmail($to, $htmlContent) {
    $mail = new PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; 
        $mail->SMTPAuth = true;
        // Use gmail to send real email
        $mail->Username = 'cc.snapx@gmail.com';
        $mail->Password = 'hzaf xnwh ssoi iowb';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->Timeout = 10;
        $mail->isHTML(true);

        $mail->setFrom('no-reply@atheneum.com', 'Atheneum');
        $mail->addAddress($to);

        $mail->Subject = 'Password Reset';
        $mail->Body = $htmlContent;
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
            <div class="error">
                <?php foreach ($errorMsg as $error): ?>
                    <?php echo htmlspecialchars($error); ?>
                <?php endforeach; ?>
            </div>
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