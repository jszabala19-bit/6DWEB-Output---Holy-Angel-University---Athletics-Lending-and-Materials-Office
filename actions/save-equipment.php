<?php
session_start();
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

// Admin only
$require_admin = true;
require_once '../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/inventory.php');
    exit;
}

$equipment_id = (int)($_POST['equipment_id'] ?? 0);
$is_edit = $equipment_id > 0;

// Collect fields (keep field names simple; does not affect any existing backend routes)
$code = sanitize($_POST['code'] ?? '');
$name = sanitize($_POST['name'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$brand = sanitize($_POST['brand'] ?? '');
$size_info = sanitize($_POST['size_info'] ?? '');
$image = sanitize($_POST['image'] ?? 'default.png');
$quantity_total = (int)($_POST['quantity_total'] ?? 0);
$quantity_available = (int)($_POST['quantity_available'] ?? 0);
$location = sanitize($_POST['location'] ?? '');
$condition_status = sanitize($_POST['condition_status'] ?? 'good');
$max_borrow_days = (int)($_POST['max_borrow_days'] ?? 7);
$max_renewals = (int)($_POST['max_renewals'] ?? 2);
$min_points_required = (int)($_POST['min_points_required'] ?? 0);
$is_active = (int)($_POST['is_active'] ?? 1) ? 1 : 0;
$notes = trim($_POST['notes'] ?? '');

// Basic validation
if ($code === '' || $name === '' || $category_id <= 0 || $location === '') {
    $_SESSION['error'] = 'Please fill in all required fields.';
    header('Location: ../admin/equipment_form.php' . ($is_edit ? ('?id=' . $equipment_id) : ''));
    exit;
}
if ($quantity_total < 0 || $quantity_available < 0) {
    $_SESSION['error'] = 'Quantity values cannot be negative.';
    header('Location: ../admin/equipment_form.php' . ($is_edit ? ('?id=' . $equipment_id) : ''));
    exit;
}
if ($quantity_available > $quantity_total) {
    $_SESSION['error'] = 'Available quantity cannot be greater than total quantity.';
    header('Location: ../admin/equipment_form.php' . ($is_edit ? ('?id=' . $equipment_id) : ''));
    exit;
}
if (!in_array($condition_status, ['excellent', 'good', 'fair', 'maintenance'], true)) {
    $_SESSION['error'] = 'Invalid condition selected.';
    header('Location: ../admin/equipment_form.php' . ($is_edit ? ('?id=' . $equipment_id) : ''));
    exit;
}
if ($max_borrow_days < 1) $max_borrow_days = 1;
if ($max_renewals < 0) $max_renewals = 0;
if ($min_points_required < 0) $min_points_required = 0;

try {
    // Ensure category exists
    $cat = $pdo->prepare('SELECT category_id FROM categories WHERE category_id = ? AND is_active = 1');
    $cat->execute([$category_id]);
    if (!$cat->fetchColumn()) {
        throw new Exception('Selected category is not available.');
    }

    if ($is_edit) {
        // Update
        $stmt = $pdo->prepare("UPDATE equipment SET
            code = ?,
            name = ?,
            category_id = ?,
            description = ?,
            brand = ?,
            size_info = ?,
            image = ?,
            quantity_total = ?,
            quantity_available = ?,
            location = ?,
            condition_status = ?,
            max_borrow_days = ?,
            max_renewals = ?,
            min_points_required = ?,
            is_active = ?,
            notes = ?
            WHERE equipment_id = ?
        ");
        $stmt->execute([
            $code,
            $name,
            $category_id,
            $description,
            $brand,
            $size_info,
            $image,
            $quantity_total,
            $quantity_available,
            $location,
            $condition_status,
            $max_borrow_days,
            $max_renewals,
            $min_points_required,
            $is_active,
            $notes,
            $equipment_id
        ]);
        $_SESSION['success'] = 'Equipment updated successfully!';
        header('Location: ../admin/inventory.php');
        exit;
    }

    // Insert
    $stmt = $pdo->prepare("INSERT INTO equipment
        (code, name, category_id, description, brand, size_info, image,
         quantity_total, quantity_available, location, condition_status,
         max_borrow_days, max_renewals, min_points_required, is_active, notes)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $code,
        $name,
        $category_id,
        $description,
        $brand,
        $size_info,
        $image,
        $quantity_total,
        $quantity_available,
        $location,
        $condition_status,
        $max_borrow_days,
        $max_renewals,
        $min_points_required,
        $is_active,
        $notes
    ]);

    $_SESSION['success'] = 'Equipment added successfully!';
    header('Location: ../admin/inventory.php');
    exit;

} catch (PDOException $e) {
    // Handle duplicate code
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        $_SESSION['error'] = 'Equipment code already exists. Please use a unique code.';
    } else {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
    }
    header('Location: ../admin/equipment_form.php' . ($is_edit ? ('?id=' . $equipment_id) : ''));
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header('Location: ../admin/equipment_form.php' . ($is_edit ? ('?id=' . $equipment_id) : ''));
    exit;
}
