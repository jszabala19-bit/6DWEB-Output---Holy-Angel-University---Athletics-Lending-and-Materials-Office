<?php
$require_admin = true;
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../config/points_system.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

$page_title = 'Settings';
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
            <h1 class="page-title">Settings</h1>
            <div class="page-subtitle">Minimal interface preferences (stored on this browser)</div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">Interface</div>
        </div>
        <div class="card-body">
            <div class="form-group checkbox-group">
                <input type="checkbox" id="pref-reduce-motion">
                <label for="pref-reduce-motion" class="form-label" style="margin:0;">Reduce motion</label>
            </div>
            <button class="btn btn-primary" id="save-prefs" type="button">Save Settings</button>

            <div class="form-text" style="margin-top:10px;">
                These settings only affect this browser/device.
            </div>
        </div>
    </div>
</div>

<script>
(function(){
  const reduce = document.getElementById('pref-reduce-motion');
  const btn = document.getElementById('save-prefs');

  function load(){
    reduce.checked = localStorage.getItem('pref_reduce_motion') === '1';
  }
  function apply(){
    document.body.classList.toggle('pref-reduce-motion', reduce.checked);
  }
  load(); apply();

  btn.addEventListener('click', function(){
    localStorage.setItem('pref_reduce_motion', reduce.checked ? '1' : '0');
    apply();
  });
})();
</script>
<?php include '../includes/footer.php'; ?>
