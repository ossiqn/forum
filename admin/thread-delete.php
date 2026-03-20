<?php
require_once '../includes/functions.php';
requireAdmin();

$threadId = (int)($_GET['id'] ?? 0);
$token = $_GET['token'] ?? '';

if (!verifyCsrfToken($token) || !$threadId) {
    header('Location: ' . SITE_URL . '/admin/threads.php');
    exit;
}

$thread = db()->fetchOne("SELECT forum_id FROM threads WHERE id = ?", [$threadId], 'i');
if ($thread) {
    db()->query("UPDATE threads SET is_deleted = 1 WHERE id = ?", [$threadId], 'i');
    db()->query("UPDATE forums SET thread_count = GREATEST(0, thread_count - 1) WHERE id = ?", [$thread['forum_id']], 'i');
}

header('Location: ' . SITE_URL . '/admin/threads.php');
exit;
