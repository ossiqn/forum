<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');
$d = json_decode(file_get_contents('php://input'), true);
$id = (int)($d['ad_id'] ?? 0);
if ($id) db()->query("UPDATE advertisements SET click_count = click_count + 1 WHERE id = ?", [$id], 'i');
echo json_encode(['ok' => true]);
