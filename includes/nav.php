<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);

// Simple inline SVG icons (no external libs)
function navIcon($name) {
    $common = "width=\"18\" height=\"18\" viewBox=\"0 0 24 24\" fill=\"none\" xmlns=\"http://www.w3.org/2000/svg\"";

    switch ($name) {
        case 'dashboard':
            return "<svg $common><path d=\"M4 11.5V20a1 1 0 0 0 1 1h5v-7h4v7h5a1 1 0 0 0 1-1v-8.5\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><path d=\"M3 12l9-9 9 9\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>";
        case 'browse':
            return "<svg $common><path d=\"M4 7h16M4 12h16M4 17h10\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>";
        case 'bag':
            return "<svg $common><path d=\"M6 7h12l1 14H5L6 7z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linejoin=\"round\"/><path d=\"M9 7a3 3 0 0 1 6 0\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/></svg>";
        case 'history':
            return "<svg $common><path d=\"M3 12a9 9 0 1 0 3-6.7\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M3 4v4h4\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><path d=\"M12 7v5l3 2\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>";
        case 'account':
            return "<svg $common><path d=\"M20 21a8 8 0 1 0-16 0\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>";
        case 'inventory':
            return "<svg $common><path d=\"M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4a2 2 0 0 0 1-1.7z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><path d=\"M3.3 7.3 12 12l8.7-4.7\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><path d=\"M12 22V12\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/></svg>";
        case 'requests':
            return "<svg $common><path d=\"M9 11h6\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M9 15h6\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M7 3h10a2 2 0 0 1 2 2v16l-4-3H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linejoin=\"round\"/></svg>";
        case 'loans':
            return "<svg $common><path d=\"M7 7h10v14H7V7z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linejoin=\"round\"/><path d=\"M9 3h6v4H9V3z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linejoin=\"round\"/><path d=\"M9 11h6\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/></svg>";
        case 'returns':
            return "<svg $common><path d=\"M21 12a9 9 0 1 1-3-6.7\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M21 3v6h-6\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/></svg>";
        case 'students':
            return "<svg $common><path d=\"M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M9 11a4 4 0 1 0-4-4 4 4 0 0 0 4 4z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"/><path d=\"M23 21v-2a4 4 0 0 0-3-3.87\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M16 3.13a4 4 0 0 1 0 7.75\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/></svg>";
        case 'reports':
            return "<svg $common><path d=\"M4 19V5a2 2 0 0 1 2-2h9l5 5v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2z\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linejoin=\"round\"/><path d=\"M14 3v5h5\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linejoin=\"round\"/><path d=\"M8 13h8\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/><path d=\"M8 17h5\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\"/></svg>";
        default:
            return "<svg $common><circle cx=\"12\" cy=\"12\" r=\"9\" stroke=\"currentColor\" stroke-width=\"2\"/></svg>";
    }
}
?>
<!-- Sidebar Navigation -->
<aside class="sidebar" id="sidebar">
    <ul class="sidebar-nav">
        <?php if ($_SESSION['role'] == 'student'): ?>
            <!-- Student Navigation -->
            <li>
                <a href="../student/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('dashboard'); ?></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../student/browse.php" class="<?php echo $current_page == 'browse.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('browse'); ?></span>
                    <span class="nav-text">Browse Equipment</span>
                </a>
            </li>
            <li>
                <a href="../student/my-equipment.php" class="<?php echo $current_page == 'my-equipment.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('bag'); ?></span>
                    <span class="nav-text">My Equipment</span>
                    <?php
                    $active_count = getActiveLoansCount($pdo, $_SESSION['user_id']);
                    if ($active_count > 0): ?>
                        <span class="badge"><?php echo $active_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../student/history.php" class="<?php echo $current_page == 'history.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('history'); ?></span>
                    <span class="nav-text">History</span>
                </a>
            </li>
            <li>
                <a href="../student/account.php" class="<?php echo $current_page == 'account.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('account'); ?></span>
                    <span class="nav-text">My Account</span>
                </a>
            </li>

        <?php else: ?>
            <!-- Admin Navigation -->
            <li>
                <a href="../admin/dashboard.php" class="<?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('dashboard'); ?></span>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../admin/inventory.php" class="<?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('inventory'); ?></span>
                    <span class="nav-text">Inventory</span>
                </a>
            </li>
            <li>
                <a href="../admin/requests.php" class="<?php echo $current_page == 'requests.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('requests'); ?></span>
                    <span class="nav-text">Requests</span>
                    <?php if ($pending_count > 0): ?>
                        <span class="badge"><?php echo $pending_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="../admin/checkouts.php" class="<?php echo $current_page == 'checkouts.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('loans'); ?></span>
                    <span class="nav-text">Active Loans</span>
                </a>
            </li>
            <li>
                <a href="../admin/returns.php" class="<?php echo $current_page == 'returns.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('returns'); ?></span>
                    <span class="nav-text">Returns</span>
                </a>
            </li>
            <li>
                <a href="../admin/students.php" class="<?php echo $current_page == 'students.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('students'); ?></span>
                    <span class="nav-text">Students</span>
                </a>
            </li>
            <li>
                <a href="../admin/reports.php" class="<?php echo $current_page == 'reports.php' ? 'active' : ''; ?>">
                    <span class="nav-icon" aria-hidden="true"><?php echo navIcon('reports'); ?></span>
                    <span class="nav-text">Reports</span>
                </a>
            </li>
        <?php endif; ?>
    </ul>
</aside>
