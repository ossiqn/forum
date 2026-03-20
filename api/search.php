<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$results = db()->fetchAll("SELECT t.id, t.title, t.reply_count, f.name as forum_name
    FROM threads t
    JOIN forums f ON f.id = t.forum_id
    WHERE t.title LIKE ? AND t.is_deleted = 0
    ORDER BY t.view_count DESC
    LIMIT 8", ["%$q%"], 's');

echo json_encode(['results' => $results]);
