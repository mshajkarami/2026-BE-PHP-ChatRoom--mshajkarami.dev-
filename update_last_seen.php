<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();
$userCode = $input['user_code'] ?? '';

if (empty($userCode)) {
    sendJSON(['success' => false, 'message' => 'user_code is required']);
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET last_seen = ? WHERE user_code = ?");
    $stmt->execute([time() * 1000, $userCode]);

    sendJSON([
        'success' => true,
        'message' => 'Last seen updated'
    ]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>