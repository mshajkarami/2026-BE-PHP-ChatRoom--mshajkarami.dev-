<?php
require_once __DIR__ . '/db.php';
requirePost();

$input = getInput();
$userCode = $input['user_code'] ?? '';
$typingTo = $input['typing_to'] ?? '';

if (empty($userCode)) {
    sendJSON(['success' => false, 'message' => 'user_code is required']);
}

try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET typing_to = ?, typing_at = ? WHERE user_code = ?");
    $stmt->execute([$typingTo, time() * 1000, $userCode]);
    sendJSON(['success' => true]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}