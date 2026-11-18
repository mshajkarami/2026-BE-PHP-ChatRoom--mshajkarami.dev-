<?php
require_once __DIR__ . '/db.php';
requirePost();

$input = getInput();
$myCode = $input['my_code'] ?? '';
$otherCode = $input['other_code'] ?? '';

if (empty($myCode)) {
    sendJSON(['success' => false, 'message' => 'my_code is required']);
}

try {
    $db = getDB();
    
    // ⭐ فقط اگه otherCode مشخص باشه، چک کن همون تایپ می‌کنه یا نه
    if (!empty($otherCode)) {
        $threshold = (time() * 1000) - 3000;
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM users 
                              WHERE user_code = ? 
                              AND typing_to = ? 
                              AND typing_at > ?");
        $stmt->execute([$otherCode, $myCode, $threshold]);
        $row = $stmt->fetch();
        $count = ($row['count'] ?? 0) > 0 ? 1 : 0;
        
        sendJSON([
            'success' => true,
            'is_typing' => $count
        ]);
    } else {
        // ⭐ اگه otherCode نداشت، همه رو بده
        $threshold = (time() * 1000) - 3000;
        $stmt = $db->prepare("SELECT user_code, user_name FROM users WHERE typing_to = ? AND typing_at > ?");
        $stmt->execute([$myCode, $threshold]);
        $typingUsers = $stmt->fetchAll();
        
        sendJSON([
            'success' => true,
            'count' => count($typingUsers),
            'typing_users' => $typingUsers
        ]);
    }
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => $e->getMessage()]);
}