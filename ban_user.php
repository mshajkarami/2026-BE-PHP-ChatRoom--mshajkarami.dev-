<?php
// ════════════════════════════════════════════
// ══════════ بن کردن کاربر ══════════════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = $input['user_code'] ?? '';
$userName = $input['user_name'] ?? '';
$reason = $input['reason'] ?? '';
$adminCode = $input['admin_code'] ?? '';

if (empty($userCode) || empty($adminCode)) {
    sendJSON([
        'success' => false,
        'message' => 'user_code and admin_code are required'
    ]);
}

if (empty($reason)) {
    sendJSON([
        'success' => false,
        'message' => 'reason is required'
    ]);
}

// چک کن خودت نباشه
if ($userCode === $adminCode) {
    sendJSON([
        'success' => false,
        'message' => 'You cannot ban yourself'
    ]);
}

// چک مدیر بودن
$admin = getUser($adminCode);
if (!$admin || $admin['rank_num'] < 1) {
    sendJSON([
        'success' => false,
        'message' => 'You are not authorized'
    ]);
}

// چک ضد شوت
if (isAntiShoot($userCode)) {
    sendJSON([
        'success' => false,
        'message' => 'This user has anti-shoot protection'
    ]);
}

// چک رتبه
$target = getUser($userCode);
$isOwner = ($adminCode === '1' || (isset($admin['rank_num']) && (int)$admin['rank_num'] >= 100));
if ($target && !$isOwner && (int)($target['rank_num'] ?? 0) >= (int)($admin['rank_num'] ?? 0)) {
    sendJSON([
        'success' => false,
        'message' => 'You cannot ban someone with equal or higher rank'
    ]);
}

try {
    banUser([
        'user_code' => $userCode,
        'user_name' => $userName,
        'rank_num' => $target['rank_num'] ?? 0,
        'admin_code' => $adminCode,
        'admin_name' => $admin['user_name'],
        'admin_rank' => $admin['rank_num'],
        'reason' => $reason,
        'user_message' => $input['user_message'] ?? '',
        'date' => $input['date'] ?? '',
        'time' => $input['time'] ?? ''
    ]);
    
    sendJSON([
        'success' => true,
        'message' => 'User banned'
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>