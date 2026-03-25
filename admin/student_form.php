<?php
$require_admin = true;
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Safe HTML escape
function esc($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

// Default values
$student = [
    'student_id' => '',
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'password' => '',
    'phone' => '',
    'department' => '',
    'enrollment_date' => date('Y-m-d'),
    'points' => POINTS_START,
    'status' => 'active',
];

$page_title = 'Add Student';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <div class="breadcrumb">
                    <a href="dashboard.php">Admin</a> / <a href="students.php">Students</a> / <?php echo $page_title; ?>
                </div>
                <h1 class="page-title"><?php echo $page_title; ?></h1>
                <div class="page-subtitle">Create a new student account. The year level is computed automatically from the enrollment date.</div>
            </div>
            <div class="page-actions">
                <a class="btn btn-secondary" href="students.php">← Back to Students</a>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Student Information</div>
            </div>
            <div class="card-body">
                <form method="POST" action="../actions/save-student.php" autocomplete="off">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="student_id">Student ID</label>
                            <input class="form-control" id="student_id" name="student_id" required value="<?php echo esc($student['student_id']); ?>" placeholder="e.g., 2023-00001">
                            <small class="form-text">Must be unique.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="email">Email</label>
                            <input class="form-control" id="email" name="email" type="email" required value="<?php echo esc($student['email']); ?>" placeholder="e.g., student@hau.edu.ph">
                            <small class="form-text">Must be unique.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="first_name">First Name</label>
                            <input class="form-control" id="first_name" name="first_name" required value="<?php echo esc($student['first_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="last_name">Last Name</label>
                            <input class="form-control" id="last_name" name="last_name" required value="<?php echo esc($student['last_name']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="password">Password</label>
                            <input class="form-control" id="password" name="password" type="password" required value="<?php echo esc($student['password']); ?>" placeholder="Set a password" minlength="8" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}" title="At least 8 characters with 1 uppercase letter, 1 lowercase letter, and 1 number.">
                            <small class="form-text">Use at least 8 characters with 1 uppercase letter, 1 lowercase letter, and 1 number.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Re-enter the password" minlength="8" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone</label>
                            <input class="form-control" id="phone" name="phone" value="<?php echo esc($student['phone']); ?>" placeholder="Optional">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="department">Department</label>
                            <input class="form-control" id="department" name="department" value="<?php echo esc($student['department']); ?>" placeholder="e.g., School of Computing">
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="enrollment_date">Enrollment Date</label>
                            <input class="form-control" id="enrollment_date" name="enrollment_date" type="date" required value="<?php echo esc($student['enrollment_date']); ?>">
                            <small class="form-text">Year level and automatic archiving are based on this date.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="points">Starting Points</label>
                            <input class="form-control" id="points" name="points" type="number" min="<?php echo (int)POINTS_MIN; ?>" max="<?php echo (int)POINTS_MAX; ?>" value="<?php echo (int)$student['points']; ?>">
                            <small class="form-text">Status will be auto-set based on points.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Account Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button class="btn btn-primary" type="submit">Save Student</button>
                        <a class="btn btn-secondary" href="students.php">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
