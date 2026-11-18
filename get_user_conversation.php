<?php
require_once __DIR__ . '/db.php';
requirePost();

$input = getInput();
$targetCode = $input['target_code'] ?? '';
$otherCode = $input['other_code'] ?? '';
$adminCode = $input['admin_code'] ?? '';

if (empty($targetCode) || empty($otherCode) || empty($adminCode)) {
    sendJSON(['success' => false, 'message' => 'required fields missing']);
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT rank_num FROM users WHERE user_code = ?");
    $stmt->execute([$adminCode]);
    $admin = $stmt->fetch();
    
    if (!$admin || ($admin['rank_num'] < 1 && $adminCode !== '1' && $adminCode !== '100')) {
        sendJSON(['success' => false, 'message' => 'شما اجازه ندارید']);
    }
    
    $stmt = $db->prepare("
        SELECT * FROM private_messages 
        WHERE (from_code = ? AND to_code = ?)
        OR (from_code = ? AND to_code = ?)
        ORDER BY id ASC
    ");
    $stmt->execute([$targetCode, $otherCode, $otherCode, $targetCode]);
    $messages = $stmt->fetchAll();
    
    sendJSON([
        'success' => true,
        'count' => count($messages),
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}