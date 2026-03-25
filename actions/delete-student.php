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
    header('Location: ../admin/students.php?view=archived');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
if ($user_id <= 0) {
    $_SESSION['error'] = 'Invalid student selected.';
    header('Location: ../admin/students.php?view=archived');
    exit;
}

try {
    // Only allow delete if already archived
    $check = $pdo->prepare("SELECT user_id FROM users WHERE user_id = ? AND role = 'student' AND is_archived = 1");
    $check->execute([$user_id]);
    if (!$check->fetch()) {
        $_SESSION['error'] = 'You can only delete a student that is already archived.';
        header('Location: ../admin/students.php?view=archived');
        exit;
    }

    // If there is history, delete may fail due to foreign keys.
    $del = $pdo->prepare("DELETE FROM users WHERE user_id = ? AND role = 'student' AND is_archived = 1");
    $del->execute([$user_id]);

    $_SESSION['success'] = 'Student deleted permanently.';
} catch (PDOException $e) {
    // Friendly FK message
    if (stripos($e->getMessage(), 'foreign key') !== false || stripos($e->getMessage(), 'constraint') !== false) {
        $_SESSION['error'] = 'Cannot delete this student because they have transaction history. Keep them archived instead.';
    } else {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
}

header('Location: ../admin/students.php?view=archived');
exit;
?>