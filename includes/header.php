<?php
// Header is included after authentication check
$user_initials = getUserInitials($_SESSION['first_name'], $_SESSION['last_name']);
$points_status_class = 'status-' . $_SESSION['points_status'];

// Get pending count for admin
$pending_count = 0;
if ($_SESSION['role'] == 'admin') {
    $pending_count = getPendingRequestsCount($pdo);
}
?>
<script>
// Apply minimal interface preferences (stored in localStorage)
(function(){
  try {
    var reduce = localStorage.getItem('pref_reduce_motion') === '1';
    if (reduce) document.body.classList.add('pref-reduce-motion');
  } catch (e) {}
})();
</script>
<!-- Top Navigation -->
<nav class="top-nav">
    <div class="top-nav-left">
        <button class="sidebar-toggle" id="sidebar-toggle">
            <span>☰</span>
        </button>
        <div class="hau-logo">
            <img src="../assets/images/logo.png" alt="HAU Logo">
        </div>
        <span class="site-title"><?php echo SITE_NAME; ?></span>
    </div>

    <div class="top-nav-right">
        <?php if ($_SESSION['role'] == 'student'): ?>
            <div class="points-badge <?php echo $points_status_class; ?>">
                Points: <?php echo $_SESSION['points']; ?>
                <span><?php echo getPointsStatusIcon($_SESSION['points_status']); ?></span>
            </div>
        <?php endif; ?>

        <div class="user-menu">
            <div class="user-menu-toggle">
                <div class="user-avatar"><?php echo $user_initials; ?></div>
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']); ?></span>
                <span>▼</span>
            </div>
            <div class="user-menu-dropdown">
                <?php if ($_SESSION['role'] == 'student'): ?>
                    <a href="../student/notifications.php">Notifications</a>
                    <a href="../student/contact.php">Contact Us</a>
                    <a href="../student/settings.php">Settings</a>
                <?php else: ?>
                    <a href="../admin/notifications.php">Notifications</a>
                    <a href="../admin/settings.php">Settings</a>
                <?php endif; ?>
                <a href="../auth/logout.php">Logout</a>
            </div>
        </div>
    </div>
</nav>
