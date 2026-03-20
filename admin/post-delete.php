<?php
require_once '../includes/functions.php';
requireAdmin();

$postId = (int)($_GET['id'] ?? 0);
$threadId = (int)($_GET['thread'] ?? 0);

if ($postId) {
    $post = db()->fetchOne("SELECT thread_id, user_id FROM posts WHERE id = ?", [$postId], 'i');
    if ($post) {
        db()->query("UPDATE posts SET is_deleted = 1 WHERE id = ?", [$postId], 'i');
        db()->query("UPDATE threads SET reply_count = GREATEST(0, reply_count - 1) WHERE id = ?", [$post['thread_id']], 'i');
        db()->query("UPDATE users SET post_count = GREATEST(0, post_count - 1) WHERE id = ?", [$post['user_id']], 'i');
    }
}

header('Location: ' . SITE_URL . '/thread.php?id=' . $threadId);
exit;
