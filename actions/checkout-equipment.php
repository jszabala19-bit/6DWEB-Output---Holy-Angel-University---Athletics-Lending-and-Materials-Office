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
    header('Location: ../admin/checkouts.php');
    exit;
}

$request_id = (int)($_POST['request_id'] ?? 0);
$condition_on_checkout = sanitize($_POST['condition_on_checkout'] ?? 'good');
$checkout_notes = sanitize($_POST['checkout_notes'] ?? '');
$admin_id = $_SESSION['user_id'];

if ($request_id <= 0) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ../admin/checkouts.php');
    exit;
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("CALL sp_checkout_equipment(?, ?, ?, ?)");
    $stmt->execute([$request_id, $admin_id, $condition_on_checkout, $checkout_notes]);

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt->closeCursor();

    if (!$result || (int)$result['ok'] !== 1) {
        throw new Exception($result['message'] ?? 'Checkout failed');
    }

    $pdo->commit();

    header('Location: ../admin/checkouts.php');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Checkout failed: " . $e->getMessage());
    $_SESSION['error'] = 'Checkout failed: ' . $e->getMessage();
    header('Location: ../admin/checkouts.php');
}
exit;
?>