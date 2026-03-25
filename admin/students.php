<?php
$require_admin = true;
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Detect if the database already has the is_archived column
$has_archived_col = false;
try {
    $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_archived'")->fetch();
    $has_archived_col = $col ? true : false;
} catch (Exception $e) {
    $has_archived_col = false;
}

$view = $_GET['view'] ?? 'active';
$filter = $_GET['filter'] ?? 'all';

// Sorting (whitelisted to prevent SQL injection)
$sort = $_GET['sort'] ?? 'name';
$dir  = strtolower($_GET['dir'] ?? 'asc');
if (!in_array($dir, ['asc', 'desc'], true)) {
    $dir = 'asc';
}

$orderMap = [
    // default
    'name' => "u.last_name $dir, u.first_name $dir",
    // requested
    'points' => "u.points $dir, u.last_name ASC, u.first_name ASC",
    'year' => "CASE WHEN u.enrollment_date IS NULL THEN 99 WHEN TIMESTAMPDIFF(YEAR, u.enrollment_date, CURDATE()) >= 4 THEN 5 ELSE TIMESTAMPDIFF(YEAR, u.enrollment_date, CURDATE()) + 1 END $dir, u.last_name ASC, u.first_name ASC",
    'department' => "u.department $dir, u.last_name ASC, u.first_name ASC",
];

$orderBy = $orderMap[$sort] ?? $orderMap['name'];

if ($view === 'archived' && !$has_archived_col) {
    $_SESSION['error'] = "Archive feature needs a database update (missing column: users.is_archived). Please run the SQL patch file included in this zip.";
    $view = 'active';
}

$where = '';
if ($has_archived_col) {
    if ($view === 'archived') {
        $where .= " AND u.is_archived = 1";
    } else {
        $where .= " AND u.is_archived = 0";
    }
}
if ($filter === 'restricted') {
    $where .= " AND points_status = 'restricted'";
}

$studentNumber = trim($_GET['student_number'] ?? '');

$sql = "
    SELECT u.*,
           (SELECT COUNT(*) FROM loans WHERE user_id = u.user_id AND status IN ('active','overdue')) as active_loans
    FROM users u
    WHERE role = 'student' $where";

if ($studentNumber !== '') {
    $sql .= " AND u.student_id LIKE :student_id";
}

$sql .= " ORDER BY $orderBy";

$stmt = $pdo->prepare($sql);
if ($studentNumber !== '') {
    $stmt->bindValue(':student_id', '%' . $studentNumber . '%', PDO::PARAM_STR);
}
$stmt->execute();
$students = $stmt->fetchAll();
foreach ($students as &$studentRow) {
    $studentRow['computed_year_level'] = getYearLevelFromEnrollmentDate($studentRow['enrollment_date'] ?? null);
}
unset($studentRow);
?>
<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Student Accounts (<?php echo count($students); ?>)</h1>
            <div class="page-actions">
                <div class="actions-left">
                    <a href="student_form.php" class="btn btn-sm btn-primary">+ Add Student</a>
                    <a href="students.php?view=active" class="btn btn-sm <?php echo $view=='active'?'btn-primary':'btn-secondary'; ?>">Active</a>
                    <a href="students.php?view=archived" class="btn btn-sm <?php echo $view=='archived'?'btn-primary':'btn-secondary'; ?>">Archived</a>
                    <a href="students.php?view=<?php echo urlencode($view); ?>&filter=restricted" class="btn btn-sm <?php echo $filter=='restricted'?'btn-danger':'btn-secondary'; ?>">Restricted</a>
                </div>

                <div class="actions-right">
                    <!-- Sort controls -->
                    <form method="GET" action="students.php" class="search-form">
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <select name="sort" class="input">
                            <option value="name" <?php echo $sort==='name'?'selected':''; ?>>Sort: Name</option>
                            <option value="department" <?php echo $sort==='department'?'selected':''; ?>>Sort: Department</option>
                            <option value="year" <?php echo $sort==='year'?'selected':''; ?>>Sort: Year</option>
                            <option value="points" <?php echo $sort==='points'?'selected':''; ?>>Sort: Points</option>
                        </select>
                        <select name="dir" class="input">
                            <option value="asc" <?php echo $dir==='asc'?'selected':''; ?>>Ascending</option>
                            <option value="desc" <?php echo $dir==='desc'?'selected':''; ?>>Descending</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-secondary">Apply</button>
                    </form>

                    <!-- Search (right-most) -->
                    <form method="GET" action="students.php" class="search-form">
                        <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <input type="text" name="student_number" class="input search-input"
                               placeholder="Search by Student Number"
                               value="<?php echo htmlspecialchars($_GET['student_number'] ?? ''); ?>">
                        <button type="submit" class="btn btn-sm btn-primary">Search</button>
                        <?php if (!empty($_GET['student_number'] ?? '')): ?>
                            <a class="btn btn-sm btn-secondary" href="students.php?view=<?php echo urlencode($view); ?>&filter=<?php echo urlencode($filter); ?>">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
<table class="table">
                    <thead><tr><th>Student</th><th>Department</th><th>Points</th><th>Status</th><th>Active Loans</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($students as $s): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($s['first_name'].' '.$s['last_name']); ?></strong><br>
                                <small><?php echo $s['student_id']; ?> | <?php echo $s['email']; ?></small></td>
                            <td><?php echo htmlspecialchars($s['department']?:'—'); ?><br><small><?php echo $s['computed_year_level'] === 'Graduate' ? 'Graduate' : ('Year ' . $s['computed_year_level']); ?></small><br><small>Enrolled: <?php echo htmlspecialchars($s['enrollment_date'] ?: '—'); ?></small></td>
                            <td><span class="points-badge status-<?php echo $s['points_status']; ?>"><?php echo $s['points']; ?> pts</span></td>
                            <td>
                                <?php
                                    if (($s['is_archived'] ?? 0) == 1) {
                                        echo '<span class="badge-secondary">Archived</span>';
                                    } else {
                                        echo $s['status']=='suspended' ? '<span class="badge-danger">Suspended</span>' : '<span class="badge-success">Active</span>';
                                    }
                                ?>
                            </td>
                            <td><?php echo $s['active_loans']; ?></td>
                            <td>
                                <a class="btn btn-sm btn-secondary" href="student_manage.php?id=<?php echo (int)$s['user_id']; ?>">Manage</a>
                                <?php if (($s['is_archived'] ?? 0) == 0): ?>
                                    <form action="../actions/archive-student.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$s['user_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Archive this student?');">Archive</button>
                                    </form>
                                <?php else: ?>
                                    <form action="../actions/restore-student.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$s['user_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Restore this student?');">Restore</button>
                                    </form>
                                    <form action="../actions/delete-student.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$s['user_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete permanently? This cannot be undone.');">Delete</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
</div>
            </div>
        </div>
    </div>
    <script src="../assets/js/main.js"></script>
</body>
</html>