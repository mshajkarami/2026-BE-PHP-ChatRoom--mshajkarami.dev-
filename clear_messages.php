<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();
$adminCode = $input['admin_code'] ?? '';
$type = $input['type'] ?? 'public';

if (empty($adminCode)) {
    sendJSON(['success' => false, 'message' => 'admin_code is required']);
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT rank_num FROM users WHERE user_code = ?");
    $stmt->execute([$adminCode]);
    $admin = $stmt->fetch();
    
    if (!$admin || $admin['rank_num'] < 1) {
        sendJSON(['success' => false, 'message' => 'You are not authorized']);
    }
    
    if ($type === 'public') {
        $db->exec("TRUNCATE TABLE messages");
        sendJSON([
            'success' => true,
            'message' => 'All public messages cleared'
        ]);
    } else if ($type === 'private') {
        $db->exec("TRUNCATE TABLE private_messages");
        sendJSON([
            'success' => true,
            'message' => 'All private messages cleared'
        ]);
    } else if ($type === 'all') {
        $db->exec("TRUNCATE TABLE messages");
        $db->exec("TRUNCATE TABLE private_messages");
        sendJSON([
            'success' => true,
            'message' => 'All messages cleared'
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'Unknown type']);
    }
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}