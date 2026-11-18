<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();
$adminCode = trim((string)($input['admin_code'] ?? ''));
$type = $input['type'] ?? 'public';

if (empty($adminCode)) {
    sendJSON(['success' => false, 'message' => 'کد مدیر الزامی است.']);
}

try {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT rank_num, is_owner FROM users WHERE user_code = ?");
    $stmt->execute([$adminCode]);
    $admin = $stmt->fetch();
    
    $adminRank = (int)($admin['rank_num'] ?? 0);
    $isOwner = ($adminCode === '1' || (isset($admin['is_owner']) && $admin['is_owner']) || $adminRank >= 100);
    
    if (!$isOwner && (!$admin || $adminRank < 1)) {
        sendJSON(['success' => false, 'message' => 'شما دسترسی مدیریت جهت پاکسازی پیام‌ها را ندارید.']);
    }
    
    if ($type === 'public') {
        $db->exec("TRUNCATE TABLE messages");
        sendJSON([
            'success' => true,
            'message' => 'تمامی پیام‌های عمومی با موفقیت پاکسازی شدند.'
        ]);
    } else if ($type === 'private') {
        $db->exec("TRUNCATE TABLE private_messages");
        sendJSON([
            'success' => true,
            'message' => 'تمامی پیام‌های خصوصی با موفقیت پاکسازی شدند.'
        ]);
    } else if ($type === 'all') {
        $db->exec("TRUNCATE TABLE messages");
        $db->exec("TRUNCATE TABLE private_messages");
        sendJSON([
            'success' => true,
            'message' => 'تمامی پیام‌های عمومی و خصوصی با موفقیت پاکسازی شدند.'
        ]);
    } else {
        sendJSON(['success' => false, 'message' => 'نوع پاکسازی نامعتبر است.']);
    }
    
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'خطا در پاکسازی: ' . $e->getMessage()]);
}