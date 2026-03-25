<?php
$require_admin = true;
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

$view = $_GET['view'] ?? 'active';

$stmt = $pdo->query("
    SELECT e.*, c.name as category_name
    FROM equipment e
    JOIN categories c ON e.category_id = c.category_id
    WHERE e.is_active = " . ($view === 'archived' ? "0" : "1") . "
    ORDER BY e.name ASC
");
$equipment = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY display_order")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
        <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory Management - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/nav.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1 class="page-title">Equipment Inventory</h1>
            <div class="page-actions inventory-actions">
                <a class="btn btn-primary" href="equipment_form.php">➕ Add Equipment</a>
                <div class="inventory-view-toggle" role="group" aria-label="Inventory view">
                    <a href="inventory.php?view=active" class="btn btn-sm <?php echo $view=='active'?'btn-primary':'btn-secondary'; ?>">Active</a>
                    <a href="inventory.php?view=archived" class="btn btn-sm <?php echo $view=='archived'?'btn-primary':'btn-secondary'; ?>">Archived</a>
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
                    <thead><tr><th>Code</th><th>Name</th><th>Category</th><th>Location</th><th>Quantity</th><th>Stock Level</th><th>Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($equipment as $e): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($e['code']); ?></strong></td>
                            <td><?php echo htmlspecialchars($e['name']); ?><br><small><?php echo htmlspecialchars($e['brand']); ?></small></td>
                            <td><?php echo $e['category_name']; ?></td>
                            <td><?php echo htmlspecialchars($e['location']); ?></td>
                            <td><?php echo $e['quantity_available']; ?> / <?php echo $e['quantity_total']; ?></td>
<td>
                                <?php
                                    $avail = (int)($e['quantity_available'] ?? 0);
                                    $total = (int)($e['quantity_total'] ?? 0);

                                    // Stock level rules (simple + practical):
                                    // - No Stock: available == 0 OR total == 0
                                    // - Low Stock: available <= 2 OR available <= 25% of total
                                    // - Moderate: available <= 60% of total
                                    // - High: otherwise
                                    if ($total <= 0 || $avail <= 0) {
                                        $label = 'No Stock';
                                        $badge = 'danger';
                                    } else {
                                        $ratio = $avail / max($total, 1);
                                        if ($avail <= 2 || $ratio <= 0.25) {
                                            $label = 'Low Stock';
                                            $badge = 'warning';
                                        } elseif ($ratio <= 0.60) {
                                            $label = 'Moderate';
                                            $badge = 'secondary';
                                        } else {
                                            $label = 'High';
                                            $badge = 'success';
                                        }
                                    }
                                ?>
                                <span class="badge-<?php echo $badge; ?>"><?php echo $label; ?></span>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-secondary" href="equipment_form.php?id=<?php echo (int)$e['equipment_id']; ?>">Edit</a>
                                <?php if ($view !== 'archived'): ?>
                                    <form action="../actions/archive-equipment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="equipment_id" value="<?php echo (int)$e['equipment_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('Archive this equipment?');">Archive</button>
                                    </form>
                                <?php else: ?>
                                    <form action="../actions/restore-equipment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="equipment_id" value="<?php echo (int)$e['equipment_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Restore this equipment?');">Restore</button>
                                    </form>
                                    <form action="../actions/delete-equipment.php" method="POST" style="display:inline;">
                                        <input type="hidden" name="equipment_id" value="<?php echo (int)$e['equipment_id']; ?>">
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
