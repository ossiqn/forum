<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

if ($action === 'mark_all_read') {
    markNotificationsRead(currentUser()['id']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
