<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'nexusforum');
define('SITE_NAME', 'OssiqnForum');
define('SITE_VERSION', '2.0.0');

$_p  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$_h  = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_sn = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$_dir = dirname($_sn);
if ($_dir === '.' || $_dir === '/') $_dir = '';
if (basename($_dir) === 'admin' || basename($_dir) === 'api') $_dir = dirname($_dir);
if ($_dir === '.' || $_dir === '/') $_dir = '';
define('SITE_URL', $_p . '://' . $_h . rtrim($_dir, '/'));

define('UPLOADS_PATH', __DIR__ . '/../uploads/');
define('UPLOADS_URL', SITE_URL . '/uploads/');
define('DEFAULT_AVATAR', SITE_URL . '/assets/images/default-avatar.png');
define('DEFAULT_COVER',  SITE_URL . '/assets/images/default-cover.jpg');
define('MAX_AVATAR_SIZE', 3 * 1024 * 1024);
define('ALLOWED_IMAGE_TYPES', ['image/jpeg','image/png','image/gif','image/webp']);
define('POSTS_PER_PAGE', 15);
define('THREADS_PER_PAGE', 20);
define('SESSION_LIFETIME', 86400 * 30);
define('BCRYPT_COST', 12);
define('CSRF_TOKEN_LENGTH', 32);
define('AD_POSITIONS', [
    'header_banner'  => 'Header Banner (970×90)',
    'sidebar_top'    => 'Sidebar Üst (300×250)',
    'sidebar_bottom' => 'Sidebar Alt (300×250)',
    'thread_between' => 'Konu İçi (728×90)',
    'footer_banner'  => 'Footer Banner (970×90)',
]);
