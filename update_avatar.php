<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();
$userCode = $input['user_code'] ?? '';
$avatarUrl = $input['avatar_url'] ?? '';

if (empty($userCode)) {
    sendJSON(['success' => false, 'message' => 'user_code is required']);
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT avatar_url FROM users WHERE user_code = ?");
    $stmt->execute([$userCode]);
    $row = $stmt->fetch();
    
    if ($row && !empty($row['avatar_url'])) {
        $oldUrl = $row['avatar_url'];
        $fileName = basename($oldUrl);
        $oldFilePath = UPLOAD_DIR . $fileName;
        if (file_exists($oldFilePath)) {
            @unlink($oldFilePath);
        }
    }
    
    $stmt = $db->prepare("UPDATE users SET avatar_url = ? WHERE user_code = ?");
    $stmt->execute([$avatarUrl, $userCode]);
    
    sendJSON([
        'success' => true,
        'message' => 'Avatar updated',
        'user' => getUser($userCode)
    ]);
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>