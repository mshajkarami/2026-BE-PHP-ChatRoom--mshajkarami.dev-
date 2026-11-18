<?php
// ════════════════════════════════════════════
// ══════════ آزاد کردن کاربر ════════════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = trim((string)($input['user_code'] ?? ''));
$adminCode = trim((string)($input['admin_code'] ?? ''));

if (empty($userCode) || empty($adminCode)) {
    sendJSON([
        'success' => false,
        'message' => 'کد کاربر و کد مدیر الزامی است.'
    ]);
}

// چک مدیر بودن
$admin = getUser($adminCode);
$adminRank = (int)($admin['rank_num'] ?? 0);
$isOwner = ($adminCode === '1' || (isset($admin['is_owner']) && $admin['is_owner']) || $adminRank >= 100);

if (!$admin && !$isOwner) {
    sendJSON([
        'success' => false,
        'message' => "حساب کاربری شما (کد $adminCode) در سرور یافت نشد."
    ]);
}

if (!$isOwner && $adminRank < 1) {
    sendJSON([
        'success' => false,
        'message' => 'شما دسترسی مدیریت جهت آزاد کردن کاربران را ندارید.'
    ]);
}

if (!$isOwner) {
    $db = getDB();
    $stmt = $db->prepare("SELECT admin_code FROM banned_users WHERE user_code = ? LIMIT 1");
    $stmt->execute([$userCode]);
    $ban = $stmt->fetch();
    if ($ban && !empty($ban['admin_code']) && $ban['admin_code'] !== $adminCode) {
        sendJSON([
            'success' => false,
            'message' => 'شما فقط مجاز به آزاد کردن کاربرانی هستید که توسط خودتان محروم شده‌اند.'
        ]);
    }
}

try {
    unbanUser($userCode);
    
    sendJSON([
        'success' => true,
        'message' => 'کاربر با موفقیت آزاد شد.'
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'خطا در آزادسازی کاربر: ' . $e->getMessage()
    ]);
}