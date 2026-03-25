<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/points_system.php';
require_once '../includes/functions.php';

// Check authentication - admin only
$require_admin = true;
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: ../admin/requests.php');
    exit;
}

$request_id = (int)($_POST['request_id'] ?? 0);
$action = sanitize($_POST['action'] ?? ''); // 'approve' or 'reject'
$admin_notes = sanitize($_POST['admin_notes'] ?? '');
$rejection_reason = sanitize($_POST['rejection_reason'] ?? '');
$admin_id = $_SESSION['user_id'];

if ($request_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../admin/requests.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("CALL sp_process_request(?, ?, ?, ?, ?)");
    $stmt->execute([$request_id, $action, $admin_id, $admin_notes, $rejection_reason]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$result || (int)$result['ok'] !== 1) {
        throw new Exception($result['message'] ?? 'Action failed');
    }

    $pdo->commit();
    header('Location: ../admin/requests.php');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Request approval failed: " . $e->getMessage());
    $_SESSION['error'] = 'Action failed: ' . $e->getMessage();
    header('Location: ../admin/requests.php');
}
exit;
?>