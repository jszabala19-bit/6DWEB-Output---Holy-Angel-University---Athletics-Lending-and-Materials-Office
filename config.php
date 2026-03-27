<?php
// GENERAL CONFIGURATION

// Site Settings
define('SITE_NAME', 'Athletics Lending and Materials Office');
define('SITE_TAGLINE', 'University Borrowing Service');
define('SITE_URL', 'http://localhost/hau-athletics-portal');

// Base URL used for building links (auto-detects your folder in XAMPP)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $parts = explode('/', trim($script, '/'));
    $project_folder = $parts[0] ?? '';
    define('BASE_URL', $protocol . '://' . $host . ($project_folder ? '/' . $project_folder : ''));
}


// University Branding
define('UNIVERSITY_NAME', 'Holy Angel University');
define('UNIVERSITY_LOGO', 'assets/images/logo.png');

// File Upload Settings
define('MAX_FILE_SIZE', 2097152); // 2MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/jpg']);
define('UPLOAD_PATH', __DIR__ . '/../assets/images/equipment/');

// Session Settings
define('SESSION_LIFETIME', 7200); // 2 hours

// Pagination
define('ITEMS_PER_PAGE', 20);

// Timezone
date_default_timezone_set('Asia/Manila');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not started (hardened cookies)
if (session_status() === PHP_SESSION_NONE) {
    $is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    // Stronger default session settings
    ini_set('session.use_strict_mode', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $is_https ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// EMAIL (PHPMailer) SETTINGS

define('MAIL_ENABLED', true);

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

define('SMTP_USERNAME', 'haualm.office@gmail.com');
define('SMTP_PASSWORD', 'atxr gzaj jywv bgcx');

define('MAIL_FROM_EMAIL', 'haualm.office@gmail.com');
define('MAIL_FROM_NAME', 'HAU Athletics Lending Office');

// Backward-compatible aliases
if (!defined('SMTP_FROM_NAME')) { define('SMTP_FROM_NAME', MAIL_FROM_NAME); }
if (!defined('SMTP_FROM_EMAIL')) { define('SMTP_FROM_EMAIL', MAIL_FROM_EMAIL); }

?>