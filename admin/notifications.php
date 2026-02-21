<?php
$require_admin = true;
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/points_system.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Latest requests (system-wide)
$stmt = $pdo->prepare("
    SELECT r.request_id, r.status, r.request_date, u.student_id, u.first_name, u.last_name,
           e.name AS equipment_name, e.code AS equipment_code
    FROM requests r
    JOIN users u ON u.user_id = r.user_id
    JOIN equipment e ON e.equipment_id = r.equipment_id
    ORDER BY r.request_date DESC
    LIMIT 12
");
$stmt->execute();
$items = $stmt->fetchAll();

$page_title = 'Notifications';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<?php
include '../includes/header.php';
include '../includes/nav.php';
?>
<div class="main-content">
    <div class="page-header">
        <div>
            <h1 class="page-title">Notifications</h1>
            <div class="page-subtitle">Recent system activity</div>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="requests.php">Go to Requests</a>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Latest Requests</div>
        </div>
        <div class="card-body">
            <?php if (empty($items)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">🔔</div>
                    <div class="empty-state-message">No recent activity</div>
                    <div class="empty-state-description">Request updates will appear here.</div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Student</th>
                                <th>Equipment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $r): ?>
                                <?php
                                    $status = strtolower($r['status'] ?? '');
                                    $badge = 'badge-secondary';
                                    if ($status === 'approved' || $status === 'returned') $badge = 'badge-success';
                                    elseif ($status === 'pending') $badge = 'badge-warning';
                                    elseif ($status === 'rejected' || $status === 'cancelled') $badge = 'badge-danger';
                                ?>
                                <tr>
                                    <td><?php echo formatDateTime($r['request_date']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($r['student_id']); ?></strong> — <?php echo htmlspecialchars($r['first_name'].' '.$r['last_name']); ?></td>
                                    <td><strong><?php echo htmlspecialchars($r['equipment_code']); ?></strong> — <?php echo htmlspecialchars($r['equipment_name']); ?></td>
                                    <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($r['status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
