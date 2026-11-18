<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $db = getDB();

    // ۱. پیدا کردن کاربرانی که کدهای ۴ رقمی یا بالای ۱۰۰۰ دارند (به جز ربات ۹۹۹۹۹۹)
    $stmt = $db->query("SELECT user_code, user_name, device_id, device_model FROM users WHERE CAST(user_code AS UNSIGNED) >= 1000 AND user_code != '999999'");
    $deletedUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $userCodes = array_column($deletedUsers, 'user_code');

    $deletedCount = 0;
    $deletedMessagesCount = 0;
    $deletedPrivateCount = 0;

    if (!empty($userCodes)) {
        // ایجاد placeholder برای کوئری IN
        $inPlaceholders = implode(',', array_fill(0, count($userCodes), '?'));

        // حذف پیام‌های عمومی این کاربران
        $stmtMsg = $db->prepare("DELETE FROM messages WHERE user_code IN ($inPlaceholders)");
        $stmtMsg->execute($userCodes);
        $deletedMessagesCount = $stmtMsg->rowCount();

        // حذف پیام‌های خصوصی این کاربران
        $stmtPriv = $db->prepare("DELETE FROM private_messages WHERE from_code IN ($inPlaceholders) OR to_code IN ($inPlaceholders)");
        $doubleCodes = array_merge($userCodes, $userCodes);
        $stmtPriv->execute($doubleCodes);
        $deletedPrivateCount = $stmtPriv->rowCount();

        // حذف این کاربران از جدول users
        $delStmt = $db->prepare("DELETE FROM users WHERE user_code IN ($inPlaceholders)");
        $delStmt->execute($userCodes);
        $deletedCount = $delStmt->rowCount();
    }

    // ۲. بررسی بالاترین کد باقی‌مانده و کد بعدی
    $maxStmt = $db->query("SELECT MAX(CAST(user_code AS UNSIGNED)) as max_code FROM users WHERE CAST(user_code AS UNSIGNED) >= 144 AND CAST(user_code AS UNSIGNED) < 1000");
    $maxRow = $maxStmt->fetch();
    $currentMax = intval($maxRow['max_code'] ?? 0);
    $nextCode = ($currentMax >= 144) ? ($currentMax + 1) : 144;

    echo json_encode([
        'success' => true,
        'message' => 'اکانت‌های تستی سری ۷۰۰۰ و بالای ۱۰۰۰ با موفقیت حذف شدند.',
        'deleted_count' => $deletedCount,
        'deleted_messages' => $deletedMessagesCount,
        'deleted_private_messages' => $deletedPrivateCount,
        'deleted_users' => $deletedUsers,
        'current_max_code' => $currentMax,
        'next_user_code_will_be' => $nextCode
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در عملیات: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
