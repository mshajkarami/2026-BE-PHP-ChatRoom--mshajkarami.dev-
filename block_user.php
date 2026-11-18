<?php
// ════════════════════════════════════════════
// ══════════ مسدود کردن کاربر ═══════════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$myCode = $input['my_code'] ?? '';
$userCode = $input['user_code'] ?? '';
$userName = $input['user_name'] ?? '';
$action = $input['action'] ?? 'block'; // block / unblock / list / check

if (empty($myCode)) {
    sendJSON([
        'success' => false,
        'message' => 'my_code is required'
    ]);
}

try {
    if ($action === 'block') {
        if (empty($userCode)) {
            sendJSON([
                'success' => false,
                'message' => 'user_code is required'
            ]);
        }
        
        if ($myCode === $userCode) {
            sendJSON([
                'success' => false,
                'message' => 'You cannot block yourself'
            ]);
        }
        
        blockUser($myCode, $userCode, $userName);
        
        sendJSON([
            'success' => true,
            'message' => 'User blocked'
        ]);
        
    } else if ($action === 'unblock') {
        if (empty($userCode)) {
            sendJSON([
                'success' => false,
                'message' => 'user_code is required'
            ]);
        }
        
        unblockUser($myCode, $userCode);
        
        sendJSON([
            'success' => true,
            'message' => 'User unblocked'
        ]);
        
    } else if ($action === 'list') {
        $blocked = getBlockedUsers($myCode);
        
        sendJSON([
            'success' => true,
            'count' => count($blocked),
            'blocked' => $blocked
        ]);
        
    } else if ($action === 'check') {
        if (empty($userCode)) {
            sendJSON([
                'success' => false,
                'message' => 'user_code is required'
            ]);
        }
        
        $blocked = isBlocked($myCode, $userCode);
        
        sendJSON([
            'success' => true,
            'is_blocked' => $blocked
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
?>