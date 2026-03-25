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
    $stmt = $pdo->prepare("UPDATE equipment SET is_active = 1 WHERE equipment_id = ?");
    $stmt->execute([$equipment_id]);

    $_SESSION['success'] = 'Equipment restored successfully.';
} catch (PDOException $e) {
    $_SESSION['error'] = 'Database error: ' . $e->getMessage();
}

header('Location: ../admin/inventory.php?view=archived');
exit;
?>