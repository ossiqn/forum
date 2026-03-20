<?php
require_once dirname(__DIR__) . '/includes/init.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Giriş yapmalısınız']);
    exit;
}

$user_id = currentUser()['id'];
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$action = $_POST['action'] ?? 'toggle';

if (!$post_id) {
    echo json_encode(['success' => false, 'error' => 'Geçersiz mesaj ID']);
    exit;
}

$post = db()->fetchOne("SELECT id, user_id FROM posts WHERE id = ? AND is_deleted = 0", [$post_id], 'i');
if (!$post) {
    echo json_encode(['success' => false, 'error' => 'Mesaj bulunamadı']);
    exit;
}

$like = db()->fetchOne("SELECT id FROM post_likes WHERE post_id = ? AND user_id = ?", [$post_id, $user_id], 'ii');

if ($action === 'like') {
    if (!$like) {
        db()->insert("INSERT INTO post_likes (post_id, user_id) VALUES (?,?)", [$post_id, $user_id], 'ii');

        if ($post['user_id'] != $user_id) {
            $poster = db()->fetchOne("SELECT username FROM users WHERE id = ?", [$post['user_id']], 'i');
            $currentUser = currentUser();
            createNotification(
                $post['user_id'],
                $user_id,
                'like',
                $post_id,
                'post',
                $currentUser['username'] . ' mesajını beğendi.'
            );
        }
        
        $liked = true;
    } else {
        $liked = true;
    }
} elseif ($action === 'unlike') {
    if ($like) {
        db()->query("DELETE FROM post_likes WHERE id = ?", [$like['id']], 'i');
        $liked = false;
    } else {
        $liked = false;
    }
} else {
    if ($like) {
        db()->query("DELETE FROM post_likes WHERE id = ?", [$like['id']], 'i');
        $liked = false;
    } else {
        db()->insert("INSERT INTO post_likes (post_id, user_id) VALUES (?,?)", [$post_id, $user_id], 'ii');

        if ($post['user_id'] != $user_id) {
            $poster = db()->fetchOne("SELECT username FROM users WHERE id = ?", [$post['user_id']], 'i');
            $currentUser = currentUser();
            createNotification(
                $post['user_id'],
                $user_id,
                'like',
                $post_id,
                'post',
                $currentUser['username'] . ' mesajını beğendi.'
            );
        }
        
        $liked = true;
    }
}

$like_count = db()->fetchOne("SELECT COUNT(*) as count FROM post_likes WHERE post_id = ?", [$post_id], 'i');
$count = $like_count['count'] ?? 0;

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'count' => $count
]);