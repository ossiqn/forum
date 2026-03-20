<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Giriş yapmanız gerekiyor']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$currentUser = currentUser();
$type = $data['type'] ?? '';

if ($type === 'post') {
    $postId = (int)($data['post_id'] ?? 0);
    if (!$postId) { echo json_encode(['success' => false]); exit; }

    $existing = db()->fetchOne("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?", [$postId, $currentUser['id']], 'ii');
    if ($existing) {
        db()->query("DELETE FROM post_likes WHERE post_id = ? AND user_id = ?", [$postId, $currentUser['id']], 'ii');
        db()->query("UPDATE posts SET like_count = GREATEST(0, like_count - 1) WHERE id = ?", [$postId], 'i');
        $liked = false;
    } else {
        db()->query("INSERT INTO post_likes (post_id, user_id) VALUES (?,?)", [$postId, $currentUser['id']], 'ii');
        db()->query("UPDATE posts SET like_count = like_count + 1 WHERE id = ?", [$postId], 'i');
        $liked = true;
    }
    $post = db()->fetchOne("SELECT like_count FROM posts WHERE id = ?", [$postId], 'i');
    echo json_encode(['success' => true, 'liked' => $liked, 'count' => $post['like_count']]);

} elseif ($type === 'thread') {
    $threadId = (int)($data['thread_id'] ?? 0);
    if (!$threadId) { echo json_encode(['success' => false]); exit; }

    $existing = db()->fetchOne("SELECT id FROM thread_likes WHERE thread_id = ? AND user_id = ?", [$threadId, $currentUser['id']], 'ii');
    if ($existing) {
        db()->query("DELETE FROM thread_likes WHERE thread_id = ? AND user_id = ?", [$threadId, $currentUser['id']], 'ii');
        db()->query("UPDATE threads SET like_count = GREATEST(0, like_count - 1) WHERE id = ?", [$threadId], 'i');
        $liked = false;
    } else {
        db()->query("INSERT INTO thread_likes (thread_id, user_id) VALUES (?,?)", [$threadId, $currentUser['id']], 'ii');
        db()->query("UPDATE threads SET like_count = like_count + 1 WHERE id = ?", [$threadId], 'i');
        $liked = true;
    }
    $thread = db()->fetchOne("SELECT like_count FROM threads WHERE id = ?", [$threadId], 'i');
    echo json_encode(['success' => true, 'liked' => $liked, 'count' => $thread['like_count']]);

} else {
    echo json_encode(['success' => false]);
}
