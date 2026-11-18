<?php
// ════════════════════════════════════════════
// ══════════ گرفتن پیام‌های خصوصی ═══════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

$input = getInput();

$myCode = trim($input['my_code'] ?? '');
$otherCode = trim($input['other_code'] ?? '');
$sinceId = isset($input['since_id']) ? (int)$input['since_id'] : 0;
$action = trim($input['action'] ?? 'conversation');

if (empty($myCode)) {
    sendJSON([
        'success' => false,
        'message' => 'my_code is required'
    ]);
}

try {
    if ($action === 'conversation') {
        // مکالمه بین دو نفر
        if (empty($otherCode)) {
            sendJSON([
                'success' => false,
                'message' => 'other_code is required for conversation'
            ]);
        }
        
        $messages = getConversation($myCode, $otherCode, $sinceId);
        
        $noMarkRead = !empty($input['no_mark_read']);
        if (!$noMarkRead) {
            // علامت خوانده‌شده
            markAsRead($myCode, $otherCode);
        }
        
        sendJSON([
            'success' => true,
            'count' => count($messages),
            'messages' => $messages
        ]);
        
    } else if ($action === 'inbox') {
        // لیست صندوق
        $conversations = getInboxLatest($myCode);
        $unread = getUnreadCount($myCode);
        
        sendJSON([
            'success' => true,
            'count' => count($conversations),
            'unread' => $unread,
            'conversations' => $conversations
        ]);
        
    } else {
        sendJSON([
            'success' => false,
            'message' => 'Unknown action'
        ]);
    }
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}