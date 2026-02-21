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

$equipment_id = (int)($_GET['id'] ?? 0);
$is_edit = $equipment_id > 0;

// Categories for dropdown
$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order, name ASC")->fetchAll();

// Default values
$equipment = [
    'equipment_id' => 0,
    'code' => '',
    'name' => '',
    'category_id' => $categories[0]['category_id'] ?? 0,
    'description' => '',
    'brand' => '',
    'size_info' => '',
    'image' => 'default.png',
    'quantity_total' => 1,
    'quantity_available' => 1,
    'location' => '',
    'condition_status' => 'good',
    'max_borrow_days' => 7,
    'max_renewals' => 2,
    'min_points_required' => 0,
    'notes' => '',
    'is_active' => 1,
];

if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ? LIMIT 1");
    $stmt->execute([$equipment_id]);
    $found = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$found) {
        $_SESSION['error'] = 'Equipment not found.';
        header('Location: inventory.php');
        exit;
    }
    $equipment = array_merge($equipment, $found);
}

$page_title = $is_edit ? 'Edit Equipment' : 'Add Equipment';
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
                    <a href="dashboard.php">Admin</a> / <a href="inventory.php">Inventory</a> / <?php echo $page_title; ?>
                </div>
                <h1 class="page-title"><?php echo $page_title; ?></h1>
                <div class="page-subtitle">Update equipment details. This page changes only the equipment record in the database.</div>
            </div>
            <div class="page-actions">
                <a class="btn btn-secondary" href="inventory.php">← Back to Inventory</a>
            </div>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div class="card-title">Equipment Information</div>
            </div>
            <div class="card-body">
                <form method="POST" action="../actions/save-equipment.php" autocomplete="off">
                    <input type="hidden" name="equipment_id" value="<?php echo (int)$equipment['equipment_id']; ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="code">Equipment Code</label>
                            <input class="form-control" id="code" name="code" required value="<?php echo esc($equipment['code']); ?>" placeholder="e.g., BB-001">
                            <small class="form-text">Must be unique.</small>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="name">Equipment Name</label>
                            <input class="form-control" id="name" name="name" required value="<?php echo esc($equipment['name']); ?>" placeholder="e.g., Basketball">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="category_id">Category</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?php echo (int)$c['category_id']; ?>" <?php echo ((int)$equipment['category_id'] === (int)$c['category_id']) ? 'selected' : ''; ?>>
                                        <?php echo esc($c['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="location">Location</label>
                            <input class="form-control" id="location" name="location" required value="<?php echo esc($equipment['location']); ?>" placeholder="e.g., Main Gym">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="brand">Brand</label>
                            <input class="form-control" id="brand" name="brand" value="<?php echo esc($equipment['brand']); ?>" placeholder="e.g., Molten">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="size_info">Size Info</label>
                            <input class="form-control" id="size_info" name="size_info" value="<?php echo esc($equipment['size_info']); ?>" placeholder="e.g., Size 7">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="image">Image Filename</label>
                            <input class="form-control" id="image" name="image" value="<?php echo esc($equipment['image']); ?>" placeholder="e.g., basketball.jpg">
                            <small class="form-text">Stored in <code>assets/images/equipment/</code></small>
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="condition_status">Condition</label>
                            <select class="form-control" id="condition_status" name="condition_status" required>
                                <?php
                                    $conds = ['excellent' => 'Excellent', 'good' => 'Good', 'fair' => 'Fair', 'maintenance' => 'Maintenance'];
                                    foreach ($conds as $k => $label):
                                ?>
                                    <option value="<?php echo $k; ?>" <?php echo ($equipment['condition_status'] === $k) ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label required" for="quantity_total">Total Quantity</label>
                            <input class="form-control" id="quantity_total" name="quantity_total" type="number" min="0" required value="<?php echo (int)$equipment['quantity_total']; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label required" for="quantity_available">Available Quantity</label>
                            <input class="form-control" id="quantity_available" name="quantity_available" type="number" min="0" required value="<?php echo (int)$equipment['quantity_available']; ?>">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="max_borrow_days">Max Borrow Days</label>
                            <input class="form-control" id="max_borrow_days" name="max_borrow_days" type="number" min="1" value="<?php echo (int)$equipment['max_borrow_days']; ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="min_points_required">Minimum Points Required</label>
                            <input class="form-control" id="min_points_required" name="min_points_required" type="number" min="0" value="<?php echo (int)$equipment['min_points_required']; ?>">
                            <small class="form-text">Set to 0 if no points are required.</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="is_active">Status</label>
                            <select class="form-control" id="is_active" name="is_active">
                                <option value="1" <?php echo ((int)$equipment['is_active'] === 1) ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo ((int)$equipment['is_active'] === 0) ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" placeholder="Short description (optional)"><?php echo esc($equipment['description']); ?></textarea>
                    </div>

                    <!-- Admin notes removed to keep management minimal -->

                    <div class="modal-footer" style="padding:0; border:none; justify-content:flex-start;">
                        <button type="submit" class="btn btn-primary"><?php echo $is_edit ? 'Save Changes' : 'Add Equipment'; ?></button>
                        <a href="inventory.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
