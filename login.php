<?php
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../student/dashboard.php');
    }
    exit;
}

$error = '';

if (function_exists('autoArchiveExpiredStudents')) {
    autoArchiveExpiredStudents($pdo);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($student_id) || empty($password)) {
        $error = 'Please enter both Student ID and password';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT user_id, student_id, first_name, last_name, email, password, role, points, points_status, status, COALESCE(is_archived, 0) AS is_archived
                FROM users
                WHERE student_id = ?
            ");
            $stmt->execute([$student_id]);
            $user = $stmt->fetch();

            if ($user) {
                $stored = (string)$user['password'];
                $ok = false;

                // Secure check: hashed passwords
                if (password_verify($password, $stored)) {
                    $ok = true;

                    if (password_needs_rehash($stored, PASSWORD_DEFAULT)) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$new_hash, $user['user_id']]);
                    }
                } else {
                    // Legacy support (plain-text passwords in old DBs):
                    // If it matches, upgrade it to a hash immediately.
                    if (!preg_match('/^\$2[ayb]\$/', $stored) && hash_equals($stored, (string)$password)) {
                        $new_hash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?")->execute([$new_hash, $user['user_id']]);
                        $ok = true;
                    }
                }

                if ($ok) {
                    if ((int)($user['is_archived'] ?? 0) === 1 && $user['role'] === 'student') {
                        $error = 'Your account has already been archived after reaching four years from the enrollment date.';
                    // Check if account is suspended
                    } elseif ($user['status'] == 'suspended') {
                        $error = 'Your account is suspended. Please contact Athletics Office.';
                    } else {
                        session_regenerate_id(true); // Prevent session fixation
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['student_id'] = $user['student_id'];
                        $_SESSION['first_name'] = $user['first_name'];
                        $_SESSION['last_name'] = $user['last_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['points'] = $user['points'];
                        $_SESSION['points_status'] = $user['points_status'];
                        $_SESSION['last_activity'] = time();

                        // Update last login
                        $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $stmt->execute([$user['user_id']]);

                        // Redirect based on role
                        if ($user['role'] == 'admin') {
                            header('Location: ../admin/dashboard.php');
                        } else {
                            header('Location: ../student/dashboard.php');
                        }
                        exit;
                    }
                } else {
                    $error = 'Invalid Student ID or password';
                }
            } else {
                $error = 'Invalid Student ID or password';
            }
} catch (PDOException $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
 <style>
    <style>
    .login-container {
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)),
                    url('../assets/images/login-background.png');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        overflow: hidden;
    }

    .login-box {
        background: white;
        border-radius: 10px;
        box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
        width: 100%;
        max-width: 460px;
        height: auto;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .login-header {
        padding: 30px 25px 20px;
        text-align: center;
        border-bottom: 1px solid #dee2e6;
    }

    .login-logo {
        width: 100%;
        max-width: 220px;
        margin: 0 auto 15px;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .login-title {
        font-size: 26px;
        font-weight: 600;
        color: #800000;
        margin-bottom: 4px;
    }

    .login-subtitle {
        font-size: 14px;
        color: #666;
        font-style: italic;
    }

    .login-body {
        padding: 20px 25px;
    }

    .login-form .form-group {
        margin-bottom: 16px;
    }

    .login-form label {
        display: block;
        margin-bottom: 6px;
        font-weight: 500;
        font-size: 14px;
        color: #333;
    }

    .login-form input {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .login-form input:focus {
        outline: none;
        border-color: #800000;
        box-shadow: 0 0 0 2px rgba(128, 0, 0, 0.1);
    }

    .login-btn {
        width: 100%;
        padding: 11px;
        background: #800000;
        color: white;
        border: none;
        border-radius: 5px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .login-btn:hover {
        background: #660000;
    }

    .login-footer {
        padding: 15px 20px;
        background: #f8f9fa;
        text-align: center;
        font-size: 12px;
        color: #666;
    }

    .alert {
        padding: 10px 12px;
        border-radius: 5px;
        margin-bottom: 15px;
        font-size: 13px;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    
    }
    .forgot-password {
        display: block;
        margin-top: 10px;
        margin-bottom: 15px;
        text-align: right;
        font-size: 13px;
    }

    .forgot-password a {
        color: #800000;
        text-decoration: none;
        padding: 5px 8px;
        border-radius: 4px;
        transition: all 0.2s ease;
    }

    .forgot-password a:hover {
        background: rgba(128, 0, 0, 0.08);
        text-decoration: underline;
    }
</style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <div class="login-logo">
                    <img src="../assets/images/logo.png" alt="HAU Logo">
                </div>
                <h1 class="login-title">Athletics Lending and Materials Office</h1>
            </div>

            <div class="login-body">
                <?php if (isset($_GET['archived']) && $_GET['archived'] == '1' && empty($error)): ?>
                    <div class="alert alert-error">Your student account is archived because it has already reached four years from the enrollment date.</div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (isset($_GET['timeout'])): ?>
                    <div class="alert alert-error">Your session has expired. Please log in again.</div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <input
                            type="text"
                            id="student_id"
                            name="student_id"
                            placeholder="e.g., 20000000"
                            value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>"
                            required
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="login-btn">Log In</button>
                    <div style="text-align:right; margin-top:10px;">
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                    </div>
                </form>

            <div class="login-footer">
                &copy; <?php echo date('Y'); ?> Holy Angel University. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>