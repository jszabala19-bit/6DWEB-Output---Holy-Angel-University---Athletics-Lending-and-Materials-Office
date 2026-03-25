<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$require_admin = true;
require_once '../includes/auth_check.php';

// Ensure DB supports archiving
try {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_archived'")->fetch();
    if (!$col) {
        $_SESSION['error'] = "Archive feature needs a database update (missing column: users.is_archived).";
        header('Location: ../admin/students.php');
        exit;
    }
} catch (Exception $e) {
    $_SESSION['error'] = "Archive feature needs a database update (missing column: users.is_archived).";
    header('Location: ../admin/students.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/students.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
if ($user_id <= 0) {
    $_SESSION['error'] = 'Invalid student selected.';
    header('Location: ../admin/students.php');
    exit;
}

try {
    // Prevent archiving your own account (safety)
    if ((int)($_SESSION['user_id'] ?? 0) === $user_id) {
        $_SESSION['error'] = 'You cannot archive your own account.';
        header('Location: ../admin/students.php');
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET is_archived = 1 WHERE user_id = ? AND role = 'student'");
    $stmt->execute([$user_id]);

    $_SESSION['success'] = 'Student archived successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../admin/students.php');
exit;
?>