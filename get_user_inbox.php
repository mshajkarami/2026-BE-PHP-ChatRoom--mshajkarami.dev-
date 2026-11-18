<?php
require_once __DIR__ . '/db.php';
requirePost();

$input = getInput();
$targetCode = $input['target_code'] ?? '';
$adminCode = $input['admin_code'] ?? '';

if (empty($targetCode) || empty($adminCode)) {
    sendJSON(['success' => false, 'message' => 'target_code and admin_code required']);
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
        SELECT pm.* FROM private_messages pm
        INNER JOIN (
            SELECT 
                CASE WHEN from_code = ? THEN to_code ELSE from_code END AS other_code,
                MAX(id) AS max_id
            FROM private_messages
            WHERE from_code = ? OR to_code = ?
            GROUP BY other_code
        ) latest ON pm.id = latest.max_id
        ORDER BY pm.id DESC
    ");
    $stmt->execute([$targetCode, $targetCode, $targetCode]);
    $conversations = $stmt->fetchAll();
    
    sendJSON([
        'success' => true,
        'count' => count($conversations),
        'conversations' => $conversations
    ]);
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}