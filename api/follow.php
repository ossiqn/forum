<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Giriş gerekli']); exit; }
$data     = json_decode(file_get_contents('php://input'), true);
$threadId = (int)($data['thread_id'] ?? 0);
$user     = currentUser();
if (!$threadId) { echo json_encode(['success'=>false]); exit; }
$exists = db()->fetchOne("SELECT id FROM thread_follows WHERE thread_id = ? AND user_id = ?", [$threadId, $user['id']], 'ii');
if ($exists) {
    db()->query("DELETE FROM thread_follows WHERE thread_id = ? AND user_id = ?", [$threadId, $user['id']], 'ii');
    echo json_encode(['success'=>true,'following'=>false]);
} else {
    db()->query("INSERT INTO thread_follows (thread_id, user_id) VALUES (?,?)", [$threadId, $user['id']], 'ii');
    echo json_encode(['success'=>true,'following'=>true]);
}
