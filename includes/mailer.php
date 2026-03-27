<?php
// Simple PHPMailer wrapper
require_once __DIR__ . '/../config/config.php';

function sendMail($toEmail, $toName, $subject, $htmlBody) {
    if (!defined('MAIL_ENABLED') || MAIL_ENABLED !== true) {
        return false; // Email disabled (safe no-op)
    }

    // PHPMailer classes
    require_once __DIR__ . '/../vendor/PHPMailer/src/Exception.php';
    require_once __DIR__ . '/../vendor/PHPMailer/src/PHPMailer.php';
    require_once __DIR__ . '/../vendor/PHPMailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->Port = SMTP_PORT;

        if (defined('SMTP_SECURE') && SMTP_SECURE) {
            $mail->SMTPSecure = SMTP_SECURE;
        }

        $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
        $mail->addAddress($toEmail, $toName ?: $toEmail);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Avoid fatal error - just fail gracefully
        return false;
    }
}
?>