<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

$require_admin = true;
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/inventory.php?view=archived');
    exit;
}

$equipment_id = (int)($_POST['equipment_id'] ?? 0);
if ($equipment_id <= 0) {
    $_SESSION['error'] = 'Invalid equipment selected.';
    header('Location: ../admin/inventory.php?view=archived');
    exit;
}

try {
    // Only allow delete if archived (inactive)
    $check = $pdo->prepare("SELECT equipment_id FROM equipment WHERE equipment_id = ? AND is_active = 0");
    $check->execute([$equipment_id]);
    if (!$check->fetch()) {
        $_SESSION['error'] = 'You can only delete an equipment item that is already archived.';
        header('Location: ../admin/inventory.php?view=archived');
        exit;
    }

    $del = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ? AND is_active = 0");
    $del->execute([$equipment_id]);

    $_SESSION['success'] = 'Equipment deleted permanently.';
} catch (PDOException $e) {
    if (stripos($e->getMessage(), 'foreign key') !== false || stripos($e->getMessage(), 'constraint') !== false) {
        $_SESSION['error'] = 'Cannot delete this equipment because it has transaction history. Keep it archived instead.';
    } else {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
}

header('Location: ../admin/inventory.php?view=archived');
exit;
?>