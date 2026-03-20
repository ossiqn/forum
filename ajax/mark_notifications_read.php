<?php
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$userId = currentUser()['id'];
db()->query("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0", [$userId], 'i');

echo json_encode(['success' => true]);
?>