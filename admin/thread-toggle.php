<?php
require_once '../includes/functions.php';
requireAdmin();

$threadId = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';
$token = $_GET['token'] ?? '';

if (!verifyCsrfToken($token) || !$threadId) {
    header('Location: ' . SITE_URL . '/admin/threads.php');
    exit;
}

if ($action === 'sticky') {
    db()->query("UPDATE threads SET is_sticky = NOT is_sticky WHERE id = ?", [$threadId], 'i');
} elseif ($action === 'lock') {
    db()->query("UPDATE threads SET is_locked = NOT is_locked WHERE id = ?", [$threadId], 'i');
}

$referer = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/admin/threads.php';
header('Location: ' . $referer);
exit;
