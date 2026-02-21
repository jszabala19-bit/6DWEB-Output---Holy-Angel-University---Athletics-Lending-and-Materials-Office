<?php
$require_admin = true;
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Safe HTML escape (prevents PHP 8.1+ deprecation when value is NULL)
function esc($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$user_id = (int)($_GET['id'] ?? 0);
if ($user_id <= 0) {
    $_SESSION['error'] = 'Invalid student selected.';
    header('Location: students.php');
    exit;
}

// Student info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? AND role = 'student' LIMIT 1");
$stmt->execute([$user_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    $_SESSION['error'] = 'Student not found.';
    header('Location: students.php');
    exit;
}

?>

<?php
// Fetch active loans and latest history for display
$stmt = $pdo->prepare("SELECT l.*, e.name as equipment_name, e.code as equipment_code
    FROM loans l
    JOIN equipment e ON l.equipment_id = e.equipment_id
    WHERE l.user_id = ? AND l.status IN ('active','overdue')
    ORDER BY l.due_date ASC
");
$stmt->execute([$user_id]);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$hist = $pdo->prepare("SELECT * FROM points_history WHERE user_id = ? ORDER BY processed_date DESC LIMIT 10");
$hist->execute([$user_id]);
$history = $hist->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Student - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <div class="breadcrumb">
                    <a href="dashboard.php">Admin</a> / <a href="students.php">Students</a> / Manage
                </div>
                <h1 class="page-title">Manage Student</h1>
                <div class="page-subtitle"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?> (<?php echo htmlspecialchars($student['student_id']); ?>)</div>
            </div>
            <div class="page-actions">
                <a class="btn btn-secondary" href="students.php">← Back to Students</a>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-card-icon">👤</div>
                <div class="stat-card-value"><?php echo (int)$student['points']; ?></div>
                <div class="stat-card-label">Points</div>
            </div>
            <div class="stat-card" style="border-left-color:#ffc107;">
                <div class="stat-card-icon">📌</div>
                <div class="stat-card-value"><?php echo htmlspecialchars($student['points_status']); ?></div>
                <div class="stat-card-label">Points Status</div>
            </div>
            <div class="stat-card" style="border-left-color:#17a2b8;">
                <div class="stat-card-icon">📦</div>
                <div class="stat-card-value"><?php echo count($loans); ?></div>
                <div class="stat-card-label">Active Loans</div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Student Profile</div>
            </div>
            <div class="card-body">
                <form method="POST" action="../actions/manage-student.php">
                    <input type="hidden" name="user_id" value="<?php echo (int)$student['user_id']; ?>">
                    <input type="hidden" name="action" value="profile">

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
                            <label class="form-label required" for="email">Email</label>
                        <input class="form-control" id="email" name="email" type="email" required value="<?php echo esc($student['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone</label>
                        <input class="form-control" id="phone" name="phone" value="<?php echo esc($student['phone']); ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="department">Department</label>
                        <input class="form-control" id="department" name="department" value="<?php echo esc($student['department']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="year_level">Year Level</label>
                            <select class="form-control" id="year_level" name="year_level">
                                <?php
                                $years = ['1','2','3','4','Graduate'];
                                foreach ($years as $y):
                                ?>
                                    <option value="<?php echo $y; ?>" <?php echo ($student['year_level'] === $y) ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Save Profile</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Account Status</div>
            </div>
            <div class="card-body">
                <form method="POST" action="../actions/manage-student.php">
                    <input type="hidden" name="user_id" value="<?php echo (int)$student['user_id']; ?>">
                    <input type="hidden" name="action" value="status">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="active" <?php echo ($student['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="suspended" <?php echo ($student['status'] === 'suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="suspended_until">Suspended Until (optional)</label>
                        <input class="form-control" id="suspended_until" name="suspended_until" type="date" value="<?php echo esc($student['suspended_until']); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="suspension_reason">Suspension Reason</label>
                    <textarea class="form-control" id="suspension_reason" name="suspension_reason" placeholder="Reason (optional)"><?php echo esc($student['suspension_reason']); ?></textarea>
                    </div>

                    <button class="btn btn-primary" type="submit">Save Status</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Adjust Points</div>
            </div>
            <div class="card-body">
                <form method="POST" action="../actions/manage-student.php">
                    <input type="hidden" name="user_id" value="<?php echo (int)$student['user_id']; ?>">
                    <input type="hidden" name="action" value="points">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="points_change">Points Change</label>
                            <input class="form-control" id="points_change" name="points_change" type="number" required placeholder="e.g., -10 or 5">
                            <small class="form-text">Use negative numbers to subtract points.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="reason">Reason</label>
                            <input class="form-control" id="reason" name="reason" required placeholder="e.g., Manual adjustment">
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Apply Adjustment</button>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
