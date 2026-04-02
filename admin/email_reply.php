<?php
require_once __DIR__ . '/../PHPMailer/Exception.php';
require_once __DIR__ . '/../PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendAdminReplyEmail($toEmail, $subject, $messageBody, $guestName = 'Valued Customer')
{
    $serverPath = '/var/www/private/db-config.ini';
    $localPath = __DIR__ . '/../db-config.ini';
    $config = [];

    if (file_exists($serverPath)) {
        $config = (array) parse_ini_file($serverPath);
    } elseif (file_exists($localPath)) {
        $config = (array) parse_ini_file($localPath);
    }

    // SMTP Configurations
    $config['smtp_host'] = $config['smtp_host'] ?? 'smtp.gmail.com';
    $config['smtp_user'] = $config['smtp_user'] ?? 'cc.snapx@gmail.com';
    $config['smtp_pass'] = $config['smtp_pass'] ?? 'hzafxnwhssoiiowb';
    $config['smtp_port'] = $config['smtp_port'] ?? 587;
    $config['smtp_secure'] = $config['smtp_secure'] ?? 'tls';
    $config['smtp_from'] = $config['smtp_from'] ?? 'cc.snapx@gmail.com';


    $displaySubject = htmlspecialchars($subject);

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
                <p style='font-size: 18px; font-weight: 500;'>Hello " . htmlspecialchars($guestName) . ",</p>
                <p>Thank you for reaching out to Atheneum Book Store. Here is the response to your enquiry regarding: <strong style='color: #1a1a1a;'>" . htmlspecialchars($displaySubject) . "</strong></p>
                <hr>
                <div style='white-space: pre-wrap; font-size: 15px; color: #444;'>" . nl2br(htmlspecialchars($messageBody)) . "</div>
                <hr>
                <p style='font-size: 14px;'>If you have any further questions, please don't hesitate to contact us again.</p>
                <p>Best Regards,<br><strong>Atheneum Support Team</strong></p>
            </div>
            <div class='footer'>
                <p style='margin: 0;'>&copy; 2026 Atheneum Book Store, PTE LTD. All rights reserved.</p>
                <p style='margin-top: 6px; font-size: 11px; color: #aaa; opacity: 0.6;'>Reference: AT-" . date('YmdHis') . "</p>
            </div>
        </div>
    </body>
    </html>";


    // Send via PHPMailer
    $mail = new PHPMailer(true);

    try {
        if (!empty($config['smtp_host'])) {
            $mail->isSMTP();
            $mail->Host = $config['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp_user'];
            $mail->Password = $config['smtp_pass'];
            $mail->SMTPSecure = $config['smtp_secure'] ?? PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = $config['smtp_port'] ?? 587;
        } else {
            $mail->isMail();
        }

        // Recipients
        $fromEmail = $config['smtp_from'] ?? 'hello@atheneum.sg';
        $mail->setFrom($fromEmail, 'Atheneum Support');
        $mail->addAddress($toEmail, $guestName);
        $mail->addReplyTo($fromEmail, 'Atheneum Support');

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlContent;
        $mail->AltBody = strip_tags($messageBody);

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>