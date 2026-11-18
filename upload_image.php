<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST allowed']);
    exit;
}

if (!isset($_FILES['image'])) {
    echo json_encode(['success' => false, 'message' => 'No image uploaded']);
    exit;
}

$file = $_FILES['image'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
    exit;
}

if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File too large (max 5MB)']);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mimeType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Only images allowed']);
    exit;
}

require_once __DIR__ . '/config.php';

$ext = 'jpg';
if ($mimeType === 'image/png') $ext = 'png';
else if ($mimeType === 'image/gif') $ext = 'gif';
else if ($mimeType === 'image/webp') $ext = 'webp';

$newName = 'img_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;

$uploadDir = UPLOAD_DIR;
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}
$targetPath = $uploadDir . $newName;

if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
    sendJSON(['success' => false, 'message' => 'Cannot save file']);
}

$url = UPLOAD_BASE_URL . $newName;

sendJSON([
    'success' => true,
    'message' => 'Uploaded',
    'url' => $url
]);