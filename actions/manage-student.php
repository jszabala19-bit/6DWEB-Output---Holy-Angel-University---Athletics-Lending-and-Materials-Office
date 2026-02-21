<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin only
$require_admin = true;
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/students.php');
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$action = sanitize($_POST['action'] ?? '');
$admin_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    $_SESSION['error'] = 'Invalid student selected.';
    header('Location: ../admin/students.php');
    exit;
}

// Ensure student exists
$stmt = $pdo->prepare("SELECT user_id, points, points_status FROM users WHERE user_id = ? AND role = 'student' LIMIT 1");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$student) {
    $_SESSION['error'] = 'Student not found.';
    header('Location: ../admin/students.php');
    exit;
}

try {
    if ($action === 'profile') {
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name  = sanitize($_POST['last_name'] ?? '');
        $email      = sanitize($_POST['email'] ?? '');
        $phone      = sanitize($_POST['phone'] ?? '');
        $department = sanitize($_POST['department'] ?? '');
        $year_level = sanitize($_POST['year_level'] ?? '');

        if ($first_name === '' || $last_name === '' || $email === '') {
            throw new Exception('Please fill in required fields.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }
        if ($year_level !== '' && !in_array($year_level, ['1','2','3','4','Graduate'], true)) {
            throw new Exception('Invalid year level.');
        }

        $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, department = ?, year_level = ? WHERE user_id = ? AND role = 'student'");
        $stmt->execute([$first_name, $last_name, $email, $phone ?: null, $department ?: null, $year_level ?: null, $user_id]);

        $_SESSION['success'] = 'Student profile updated.';
        header('Location: ../admin/student_manage.php?id=' . $user_id);
        exit;
    }

    if ($action === 'status') {
        $status = sanitize($_POST['status'] ?? 'active');
        $suspended_until = sanitize($_POST['suspended_until'] ?? '');
        $suspension_reason = trim($_POST['suspension_reason'] ?? '');

        if (!in_array($status, ['active','suspended'], true)) {
            throw new Exception('Invalid status.');
        }

        // Normalize date
        $date_val = null;
        if ($suspended_until !== '') {
            $dt = date_create($suspended_until);
            if (!$dt) {
                throw new Exception('Invalid suspension date.');
            }
            $date_val = date_format($dt, 'Y-m-d');
        }

        if ($status === 'active') {
            $date_val = null;
            $suspension_reason = null;
        }

        $stmt = $pdo->prepare("UPDATE users SET status = ?, suspended_until = ?, suspension_reason = ? WHERE user_id = ? AND role = 'student'");
        $stmt->execute([$status, $date_val, ($suspension_reason === '' ? null : $suspension_reason), $user_id]);

        $_SESSION['success'] = 'Student status updated.';
        header('Location: ../admin/student_manage.php?id=' . $user_id);
        exit;
    }

    if ($action === 'points') {
        $points_change = (int)($_POST['points_change'] ?? 0);
        $reason = sanitize($_POST['reason'] ?? '');

        if ($points_change === 0) {
            throw new Exception('Points change cannot be 0.');
        }
        if ($reason === '') {
            throw new Exception('Please provide a reason.');
        }

        $new_points = updateUserPoints(
            $pdo,
            $user_id,
            $points_change,
            $reason,
            'adjustment',
            null,
            null,
            $admin_id
        );

        if ($new_points === false) {
            throw new Exception('Failed to update points.');
        }

        $_SESSION['success'] = "Points updated. New balance: {$new_points}.";
        header('Location: ../admin/student_manage.php?id=' . $user_id);
        exit;
    }

    throw new Exception('Invalid action.');

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $_SESSION['error'] = 'Email already exists. Please use a different email.';
    } else {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
    header('Location: ../admin/student_manage.php?id=' . $user_id);
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../admin/student_manage.php?id=' . $user_id);
    exit;
}
