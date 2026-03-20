<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');
$d = json_decode(file_get_contents('php://input'), true);
$id = (int)($d['ad_id'] ?? 0);
if ($id) db()->query("UPDATE advertisements SET impression_count = impression_count + 1 WHERE id = ?", [$id], 'i');
echo json_encode(['ok' => true]);
