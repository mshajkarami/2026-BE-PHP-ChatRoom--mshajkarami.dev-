<?php
// ════════════════════════════════════════════
// ══════════ حذف مکالمه (فقط برای خودم) ═══════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();
$myCode = trim($input['my_code'] ?? '');
$otherCode = trim($input['other_code'] ?? '');

if (empty($myCode) || empty($otherCode)) {
    sendJSON(['success' => false, 'message' => 'my_code and other_code are required']);
}

try {
    $db = getDB();
    
    // ⭐ ۱. بزرگترین id پیام‌های این مکالمه
    $stmt = $db->prepare("SELECT MAX(id) as max_id FROM private_messages 
                          WHERE (from_code = ? AND to_code = ?) 
                          OR (from_code = ? AND to_code = ?)");
    $stmt->execute([$myCode, $otherCode, $otherCode, $myCode]);
    $row = $stmt->fetch();
    $maxId = intval($row['max_id'] ?? 0);
    
    if ($maxId === 0) {
        $maxId = 1;
    }
    
    // ⭐ ۲. فقط برای خودم علامت بزن — نه طرف مقابل!
    $stmt = $db->prepare("INSERT INTO deleted_conversations (user_code, other_code, deleted_at) 
                          VALUES (?, ?, ?) 
                          ON DUPLICATE KEY UPDATE deleted_at = ?");
    $stmt->execute([$myCode, $otherCode, $maxId, $maxId]);
    
    // ⭐ ۳. دیگه پیام‌ها رو از سرور پاک نکن!
    // (این بخش حذف شد)
    
    sendJSON([
        'success' => true, 
        'message' => 'Conversation deleted for you',
        'max_id' => $maxId
    ]);
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}