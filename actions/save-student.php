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

$student_id = sanitize($_POST['student_id'] ?? '');
$first_name = sanitize($_POST['first_name'] ?? '');
$last_name = sanitize($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$confirm_password = (string)($_POST['confirm_password'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$department = sanitize($_POST['department'] ?? '');
$enrollment_date = trim($_POST['enrollment_date'] ?? '');
$points = (int)($_POST['points'] ?? POINTS_START);
$status = sanitize($_POST['status'] ?? 'active');

// Basic validation
if ($student_id === '' || $first_name === '' || $last_name === '' || $email === '' || trim($password) === '' || trim($confirm_password) === '') {
    $_SESSION['error'] = 'Please fill in all required fields.';
    header('Location: ../admin/student_form.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'Please enter a valid email address.';
    header('Location: ../admin/student_form.php');
    exit;
}

if ($password !== $confirm_password) {
    $_SESSION['error'] = 'Password and confirm password do not match.';
    header('Location: ../admin/student_form.php');
    exit;
}

$password_errors = validatePasswordStrength($password);
if (!empty($password_errors)) {
    $_SESSION['error'] = implode(' ', $password_errors);
    header('Location: ../admin/student_form.php');
    exit;
}

if ($enrollment_date === '') {
    $_SESSION['error'] = 'Please provide the enrollment date.';
    header('Location: ../admin/student_form.php');
    exit;
}

$dt = date_create($enrollment_date);
if (!$dt) {
    $_SESSION['error'] = 'Invalid enrollment date.';
    header('Location: ../admin/student_form.php');
    exit;
}
$enrollment_date = $dt->format('Y-m-d');
if (!in_array($status, ['active','suspended'], true)) {
    $_SESSION['error'] = 'Invalid status selected.';
    header('Location: ../admin/student_form.php');
    exit;
}

// Clamp points within allowed range
if ($points < POINTS_MIN) $points = POINTS_MIN;
if ($points > POINTS_MAX) $points = POINTS_MAX;

try {
    $stmt = $pdo->prepare("
        INSERT INTO users
            (student_id, first_name, last_name, email, password, phone, department, enrollment_date, role, points, status)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 'student', ?, ?)
    ");
    $stmt->execute([
        $student_id,
        $first_name,
        $last_name,
        $email,
        password_hash($password, PASSWORD_DEFAULT), // Hashed password
        $phone !== '' ? $phone : null,
        $department !== '' ? $department : null,
        $enrollment_date,
        $points,
        $status
    ]);

    $_SESSION['success'] = 'Student account added successfully!';
    header('Location: ../admin/students.php');
    exit;

} catch (PDOException $e) {
    // Handle duplicate student_id/email
    if (stripos($e->getMessage(), 'Duplicate') !== false) {
        $_SESSION['error'] = 'Student ID or email already exists. Please use unique values.';
    } else {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
    header('Location: ../admin/student_form.php');
    exit;
}