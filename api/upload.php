<?php
require_once '../includes/functions.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { echo json_encode(['success'=>false,'message'=>'Giriş gerekli']); exit; }
if (empty($_FILES['file'])) { echo json_encode(['success'=>false,'message'=>'Dosya seçilmedi']); exit; }
$user   = currentUser();
$result = uploadMedia($_FILES['file'], $user['id']);
if (isset($result['error'])) {
    echo json_encode(['success'=>false,'message'=>$result['error']]);
} else {
    echo json_encode(['success'=>true,'url'=>$result['url'],'bbcode'=>'[img]'.$result['url'].'[/img]']);
}
