<?php
// RESET PASSWORD

require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
ensurePasswordResetTables($pdo);

$token = trim($_GET['token'] ?? '');
$error = '';
$success = '';
$token_valid = false;

if ($token !== '' && ctype_xdigit($token) && strlen($token) === 64) {
    try {
        $token_hash = hash('sha256', $token);

        $stmt = $pdo->prepare("
            SELECT pr.reset_id, pr.user_id, pr.expires_at, pr.used_at, u.email
            FROM password_resets pr
            JOIN users u ON u.user_id = pr.user_id
            WHERE pr.token_hash = ?
            LIMIT 1
        ");
        $stmt->execute([$token_hash]);
        $row = $stmt->fetch();

        if ($row && $row['used_at'] === null && strtotime($row['expires_at']) > time()) {
            $token_valid = true;
            $reset_id = (int)$row['reset_id'];
            $user_id = (int)$row['user_id'];
            $email = (string)$row['email'];
        } else {
            $error = 'This reset link is invalid or expired.';
        }
    } catch (PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
} else {
    if ($token !== '') $error = 'Invalid reset token.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token'] ?? '');
    $new_password = (string)($_POST['new_password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    if ($token === '') {
        $error = 'Invalid request.';
    } elseif ($new_password === '' || $confirm_password === '') {
        $error = 'Please fill in all fields.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $password_errors = validatePasswordStrength($new_password);
        if (!empty($password_errors)) {
            $error = implode(' ', $password_errors);
        } else {
            try {
                $token_hash = hash('sha256', $token);

                $stmt = $pdo->prepare("SELECT reset_id, user_id, expires_at, used_at FROM password_resets WHERE token_hash = ? LIMIT 1");
                $stmt->execute([$token_hash]);
                $row = $stmt->fetch();

                if (!$row || $row['used_at'] !== null || strtotime($row['expires_at']) <= time()) {
                    $error = 'This reset link is invalid or expired.';
                } else {
                    $reset_id = (int)$row['reset_id'];
                    $user_id = (int)$row['user_id'];

                    $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);

                    $pdo->beginTransaction();
                    $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$new_hashed, $user_id]);
                    $pdo->prepare("UPDATE password_resets SET used_at = NOW() WHERE reset_id = ?")->execute([$reset_id]);
                    $pdo->commit();

                    $success = 'Password has been reset successfully. You may now log in.';
                    $token_valid = false;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
<style>
    .rp-container{
        min-height:100vh;
        display:flex;
        align-items:center;
        justify-content:center;
        background: linear-gradient(rgba(0,0,0,.45), rgba(0,0,0,.45)),
                    url('../assets/images/login-background.png') center/cover no-repeat;
        padding:20px;
    }

    .rp-box{
        background:#fff;
        width:100%;
        max-width:480px;
        border-radius:12px;
        box-shadow:0 15px 40px rgba(0,0,0,0.15);
        overflow:hidden;
        display:flex;
        flex-direction:column;
    }

    .rp-header{
        background:#800000;
        color:#fff;
        text-align:center;
        padding:28px 20px;
    }

    /* Removed logo styling completely */

    .rp-title{
        margin:0;
        font-size:22px;
        font-weight:600;
        letter-spacing:.5px;
    }

    .rp-body{
        padding:32px;
    }

    .form-group{
        margin-bottom:20px;
    }

    label{
        display:block;
        margin-bottom:8px;
        font-size:14px;
        font-weight:500;
        color:#444;
    }

    input{
        width:100%;
        padding:12px 14px;
        border:1px solid #ddd;
        border-radius:8px;
        font-size:14px;
        transition:all .2s ease;
    }

    input:focus{
        outline:none;
        border-color:#800000;
        box-shadow:0 0 0 3px rgba(128,0,0,.1);
    }

    .btn{
        display:block;
        width:100%;
        padding:13px;
        border:none;
        border-radius:8px;
        background:#800000;
        color:#fff;
        font-weight:600;
        font-size:15px;
        cursor:pointer;
        transition:background .2s ease;
        margin-top:10px;
    }

    .btn:hover{
        background:#6a0000;
    }

    .link{
        display:block;
        margin-top:18px;
        color:#800000;
        font-size:14px;
        text-decoration:none;
        text-align:center;
    }

    .link:hover{
        text-decoration:underline;
    }

    .alert{
        padding:12px;
        border-radius:8px;
        margin-bottom:18px;
        font-size:13px;
    }

    .alert-error{
        background:#fdeaea;
        color:#a10000;
        border:1px solid #f5c2c2;
    }

    .alert-success{
        background:#eaf6ec;
        color:#1b5e20;
        border:1px solid #c3e6cb;
    }

    .rp-footer{
        padding:18px;
        background:#fafafa;
        text-align:center;
        font-size:12px;
        color:#888;
    }
</style>
</head>
<body>
<div class="rp-container">
    <div class="rp-box">
        <div class="rp-header">
            <h1 class="rp-title">Reset Password</h1>
        </div>

        <div class="rp-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <a class="link" href="login.php">Back to Login</a>
            <?php elseif ($token_valid): ?>
                <form method="POST">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" minlength="6" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" minlength="6" required>
                    </div>
                    <button type="submit" class="btn">Change Password</button>
                    <div style="margin-top:10px;">
                        <a class="link" href="login.php">Back to Login</a>
                    </div>
                </form>
            <?php else: ?>
                <a class="link" href="forgot-password.php">Request a new reset link</a>
            <?php endif; ?>
        </div>

        <div class="rp-footer">
            &copy; <?php echo date('Y'); ?> Holy Angel University. All rights reserved.
        </div>
    </div>
</div>
</body>
</html>
