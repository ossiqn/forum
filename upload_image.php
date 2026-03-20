<?php
error_reporting(0);
ob_start();

require_once 'includes/functions.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Resim yüklemek için giriş yapmalısınız.']);
    exit;
}

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/posts/';
    
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $fileInfo = pathinfo($_FILES['image']['name']);
    $ext = strtolower($fileInfo['extension']);
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'error' => 'Sadece JPG, PNG, GIF ve WEBP formatları yüklenebilir.']);
        exit;
    }

    $newName = uniqid('post_img_') . '.' . $ext;
    $targetFile = $uploadDir . $newName;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
        $url = SITE_URL . '/' . $targetFile;
        echo json_encode(['success' => true, 'url' => $url]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Resim sunucuya kaydedilemedi. Klasör izinlerini kontrol et.']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Dosya seçilmedi veya boyutu sunucu sınırını aşıyor.']);
}
exit;
?>