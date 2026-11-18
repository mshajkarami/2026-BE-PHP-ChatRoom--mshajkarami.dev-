<?php
// ════════════════════════════════════════════
// ══════════ بن کردن کاربر ══════════════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = trim((string)($input['user_code'] ?? ''));
$userName = trim((string)($input['user_name'] ?? ''));
$reason = trim((string)($input['reason'] ?? ''));
$adminCode = trim((string)($input['admin_code'] ?? ''));

if (empty($userCode) || empty($adminCode)) {
    sendJSON([
        'success' => false,
        'message' => 'کد کاربر و کد مدیر الزامی است.'
    ]);
}

if (empty($reason)) {
    sendJSON([
        'success' => false,
        'message' => 'لطفاً علت محروم‌سازی را وارد کنید.'
    ]);
}

// چک کن خودت نباشی
if ($userCode === $adminCode) {
    sendJSON([
        'success' => false,
        'message' => 'شما نمی‌توانید خودتان را شوت کنید!'
    ]);
}

// چک کن مالک نباشه
if ($userCode === '1') {
    sendJSON([
        'success' => false,
        'message' => 'شما نمی‌توانید مالک چت را شوت کنید!'
    ]);
}

// استخراج و بررسی اطلاعات مدیر
$admin = getUser($adminCode);
$adminRank = (int)($admin['rank_num'] ?? 0);
$isOwner = ($adminCode === '1' || (isset($admin['is_owner']) && $admin['is_owner']) || $adminRank >= 100);

if (!$admin && !$isOwner) {
    sendJSON([
        'success' => false,
        'message' => "حساب کاربری شما (کد $adminCode) در سرور یافت نشد. لطفاً یک‌بار از برنامه خارج شده و مجدداً وارد شوید."
    ]);
}

if (!$isOwner && $adminRank < 1) {
    sendJSON([
        'success' => false,
        'message' => 'شما دسترسی مدیریت جهت شوت کردن کاربران را ندارید.'
    ]);
}

// چک ضد شوت
if (isAntiShoot($userCode)) {
    sendJSON([
        'success' => false,
        'message' => 'این کاربر دارای ضد شوت دائمی است و قابل محروم‌سازی نیست.'
    ]);
}

// چک رتبه کاربر هدف
$target = getUser($userCode);
$targetRank = (int)($target['rank_num'] ?? 0);
$targetIsOwner = ($userCode === '1' || (isset($target['is_owner']) && $target['is_owner']) || $targetRank >= 100);

if ($targetIsOwner) {
    sendJSON([
        'success' => false,
        'message' => 'شما نمی‌توانید مالک چت را شوت کنید!'
    ]);
}

if ($target && !$isOwner && $targetRank >= $adminRank) {
    sendJSON([
        'success' => false,
        'message' => 'شما نمی‌توانید کاربری با مقام مساوی یا بالاتر از خودتان را شوت کنید!'
    ]);
}

try {
    banUser([
        'user_code' => $userCode,
        'user_name' => !empty($userName) ? $userName : ($target['user_name'] ?? 'کاربر'),
        'rank_num' => $targetRank,
        'admin_code' => $adminCode,
        'admin_name' => $admin ? ($admin['user_name'] ?? 'مدیر') : 'مالک',
        'admin_rank' => $isOwner ? 200 : $adminRank,
        'reason' => $reason,
        'user_message' => $input['user_message'] ?? '',
        'date' => $input['date'] ?? '',
        'time' => $input['time'] ?? ''
    ]);
    
    sendJSON([
        'success' => true,
        'message' => 'کاربر با موفقیت محروم شد.'
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'خطا در ثبت شوت: ' . $e->getMessage()
    ]);
}