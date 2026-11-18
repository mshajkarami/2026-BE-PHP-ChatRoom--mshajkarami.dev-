<?php
// ════════════════════════════════════════════
// ══════════ آزاد کردن کاربر ════════════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = $input['user_code'] ?? '';
$adminCode = $input['admin_code'] ?? '';

if (empty($userCode) || empty($adminCode)) {
    sendJSON([
        'success' => false,
        'message' => 'user_code and admin_code are required'
    ]);
}

// چک مدیر بودن
$admin = getUser($adminCode);
if (!$admin || ((int)($admin['rank_num'] ?? 0) < 1 && $adminCode !== '1')) {
    sendJSON([
        'success' => false,
        'message' => 'You are not authorized'
    ]);
}

$isOwner = ($adminCode === '1' || (isset($admin['rank_num']) && (int)$admin['rank_num'] >= 100));
if (!$isOwner) {
    $db = getDB();
    $stmt = $db->prepare("SELECT admin_code FROM banned_users WHERE user_code = ? LIMIT 1");
    $stmt->execute([$userCode]);
    $ban = $stmt->fetch();
    if ($ban && !empty($ban['admin_code']) && $ban['admin_code'] !== $adminCode) {
        sendJSON([
            'success' => false,
            'message' => 'شما فقط مجاز به آزاد کردن کاربرانی هستید که خودتان محروم کرده‌اید'
        ]);
    }
}

try {
    unbanUser($userCode);
    
    sendJSON([
        'success' => true,
        'message' => 'User unbanned'
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>