<?php
// ════════════════════════════════════════════
// ══════════ گرفتن پیام‌های عمومی ═══════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

$input = getInput();

$sinceId = isset($input['since_id']) ? (int)$input['since_id'] : 0;
$limit = isset($input['limit']) ? (int)$input['limit'] : 30;

if ($limit > 50) $limit = 50;
if ($limit < 1) $limit = 30;

try {
    if ($sinceId > 0) {
        $messages = getMessagesSince($sinceId, $limit);
    } else {
        $messages = getLastMessages($limit);
    }
    
    sendJSON([
        'success' => true,
        'count' => count($messages),
        'messages' => $messages
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}