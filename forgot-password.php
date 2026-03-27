<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
ensurePasswordResetTables($pdo);
require_once '../includes/mailer.php';

$message = '';
$error = '';
$debug_link = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // Always show a generic message (do not expose if email exists)
            $message = 'If the email exists in our system, please check your email (including spam) for password reset instructions.';

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $token_hash = hash('sha256', $token);

                // Invalidate previous unused tokens
                $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL")
                    ->execute([(int)$user['user_id']]);

                $expires_at = date('Y-m-d H:i:s', time() + 30*60);

                $pdo->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)")
                    ->execute([(int)$user['user_id'], $token_hash, $expires_at]);

                $reset_link = BASE_URL . '/auth/reset-password.php?token=' . urlencode($token);

                // Email content
                $full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                $subject = 'Password Reset Request';
                $body = '
                    <div style="font-family:Arial,sans-serif;line-height:1.5;">
                        <h2 style="color:#800000;margin:0 0 10px;">Password Reset</h2>
                        <p>Hi ' . htmlspecialchars($full_name ?: $user['email']) . ',</p>
                        <p>We received a request to reset your password. Click the button below to continue:</p>
                        <p style="margin:18px 0;">
                            <a href="' . htmlspecialchars($reset_link) . '" style="display:inline-block;background:#800000;color:#fff;padding:12px 18px;border-radius:6px;text-decoration:none;font-weight:600;">
                                Reset Password
                            </a>
                        </p>
                        <p>This link will expire in <strong>30 minutes</strong>.</p>
                        <p>If you did not request this, you can ignore this email.</p>
                        <p style="color:#666;font-size:12px;margin-top:18px;">' . htmlspecialchars(SITE_NAME) . '</p>
                    </div>
                ';

                $sent = sendMail($user['email'], $full_name, $subject, $body);

                // Log attempt (only if enabled and sent)
                if ($sent) {
                    $pdo->prepare("INSERT INTO email_logs (user_id, type, related_id) VALUES (?, 'password_reset', NULL)")
                        ->execute([(int)$user['user_id']]);
                } else {
                    // If email disabled, show debug link for local testing
                    if (defined('MAIL_ENABLED') && MAIL_ENABLED !== true) {
                        $debug_link = $reset_link;
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .fp-container{
            min-height:100vh;display:flex;align-items:center;justify-content:center;
            background: linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)),
                        url('../assets/images/login-background.png');
            background-size:cover;background-position:center;background-repeat:no-repeat;background-attachment:fixed;
            padding:20px;
        }
        .fp-box{background:#fff;border-radius:10px;box-shadow:0 10px 40px rgba(0,0,0,0.22);width:100%;max-width:540px;overflow:hidden;}
        .fp-header{background:#800000;color:#fff;padding:18px 22px;display:flex;align-items:center;gap:14px;}
        .fp-header img{width:48px;height:48px;border-radius:50%;background:#fff;padding:6px;object-fit:contain;}
        .fp-title{margin:0;font-size:18px;font-weight:700;}
        .fp-body{padding:24px;}
        .fp-body p{margin:0 0 16px;color:#555;line-height:1.4;}
        .form-group{margin-bottom:16px;}
        label{display:block;margin-bottom:8px;font-weight:600;color:#333;}
        input{width:100%;padding:12px 14px;border:1px solid #d9dee5;border-radius:6px;font-size:14px;}
        input:focus{outline:none;border-color:#800000;box-shadow:0 0 0 3px rgba(128,0,0,0.10);}
        .btn-row{display:flex;gap:12px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-top:8px;}
        .btn{display:inline-block;padding:11px 16px;border-radius:6px;border:1px solid #800000;background:#800000;color:#fff;cursor:pointer;font-weight:600;text-decoration:none;}
        .btn:hover{background:#660000;}
        .link{color:#800000;text-decoration:none;font-weight:600;}
        .link:hover{text-decoration:underline;}
        .alert{padding:12px 14px;border-radius:8px;margin-bottom:16px;font-size:14px;}
        .alert-error{background:#f8d7da;color:#721c24;border-left:4px solid #dc3545;}
        .alert-success{background:#d4edda;color:#155724;border-left:4px solid #28a745;}
        .debug-box{margin-top:14px;padding:12px 14px;border:1px dashed #800000;border-radius:8px;background:#fff9f9;}
        .debug-box small{display:block;color:#555;margin-bottom:6px;}
        .debug-box a{word-break:break-all;}
        .fp-footer{padding:14px 20px;background:#f8f9fa;text-align:center;font-size:13px;color:#666;}
    </style>
</head>
<body>
<div class="fp-container">
    <div class="fp-box">
        <div class="fp-header">
            <img src="../assets/images/logo.png" alt="HAU Logo">
            <h1 class="fp-title">Forgot Password</h1>
        </div>

        <div class="fp-body">
            <p>Enter your email and we will send password reset instructions.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input id="email" type="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="btn-row">
                    <a class="link" href="login.php">Back to Login</a>
                    <button type="submit" class="btn">Request Password</button>
                </div>
            </form>

            <?php if (!empty($debug_link)): ?>
                <div class="debug-box">
                    <small><strong>Local testing (email disabled):</strong></small>
                    <a class="link" href="<?php echo htmlspecialchars($debug_link); ?>"><?php echo htmlspecialchars($debug_link); ?></a>
                </div>
            <?php endif; ?>
        </div>

        <div class="fp-footer">
            &copy; <?php echo date('Y'); ?> Holy Angel University. All rights reserved.
        </div>
    </div>
</div>
</body>
</html>
