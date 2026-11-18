<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/db.php';

try {
    $db = getDB();

    // 1. لیست اکانت‌های شبح و تکراری شناسایی شده که باید پاکسازی شوند
    $ghostCodes = ['99', '163', '7780', '146', '17', '159', '23', '103'];

    // 2. کدهای حفاظت‌شده که تحت هیچ شرایطی نباید حذف شوند
    $protectedCodes = ['1', '102', '148', '8', '160', '161', '999999'];

    // فیلتر کدهای حذفی جهت اطمینان ۱۰۰٪
    $codesToDelete = array_values(array_diff($ghostCodes, $protectedCodes));

    if (empty($codesToDelete)) {
        echo json_encode([
            'success' => true,
            'message' => 'هیچ اکانت تکراری برای حذف یافت نشد.'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ۳. انتقال پیام‌های عمومی و خصوصی اکانت‌های شبح به اکانت‌های اصلی تا هیچ پیامی از بین نرود:
    // انتقال پیام‌های زهرا (۹۹، ۱۶۳، ۷۷۸۰) به اکانت اصلی زهرا (۱۰۲)
    $stmtReassignZahraPublic = $db->prepare("UPDATE messages SET user_code = '102', user_name = 'zahra' WHERE user_code IN ('99', '163', '7780')");
    $stmtReassignZahraPublic->execute();
    $reassignedZahraMsg = $stmtReassignZahraPublic->rowCount();

    $stmtReassignZahraPrivFrom = $db->prepare("UPDATE private_messages SET from_code = '102', from_name = 'zahra' WHERE from_code IN ('99', '163', '7780')");
    $stmtReassignZahraPrivFrom->execute();
    $reassignedZahraPrivFrom = $stmtReassignZahraPrivFrom->rowCount();

    $stmtReassignZahraPrivTo = $db->prepare("UPDATE private_messages SET to_code = '102', to_name = 'zahra' WHERE to_code IN ('99', '163', '7780')");
    $stmtReassignZahraPrivTo->execute();
    $reassignedZahraPrivTo = $stmtReassignZahraPrivTo->rowCount();

    // انتقال پیام‌های علی رضا (۱۰۳) به اکانت اصلی علی رضا (۸) در صورت وجود
    $db->prepare("UPDATE messages SET user_code = '8', user_name = 'علی رضا' WHERE user_code = '103'")->execute();
    $db->prepare("UPDATE private_messages SET from_code = '8', from_name = 'علی رضا' WHERE from_code = '103'")->execute();
    $db->prepare("UPDATE private_messages SET to_code = '8', to_name = 'علی رضا' WHERE to_code = '103'")->execute();

    // ۴. حذف اکانت‌های تکراری و شبح از تمام جداول پایگاه داده
    $placeholders = implode(',', array_fill(0, count($codesToDelete), '?'));

    // حذف از users
    $delUsers = $db->prepare("DELETE FROM users WHERE user_code IN ($placeholders)");
    $delUsers->execute($codesToDelete);
    $deletedUsersCount = $delUsers->rowCount();

    // حذف از anti_shoot
    $delAnti = $db->prepare("DELETE FROM anti_shoot WHERE user_code IN ($placeholders)");
    $delAnti->execute($codesToDelete);

    // حذف از hidden_shoot در صورت وجود جدول
    try {
        $delHidden = $db->prepare("DELETE FROM hidden_shoot WHERE user_code IN ($placeholders)");
        $delHidden->execute($codesToDelete);
    } catch (Exception $ignored) {}

    // حذف از banned_users
    $delBanned = $db->prepare("DELETE FROM banned_users WHERE user_code IN ($placeholders)");
    $delBanned->execute($codesToDelete);

    // حذف از blocked_users
    $doubleCodes = array_merge($codesToDelete, $codesToDelete);
    $delBlocked = $db->prepare("DELETE FROM blocked_users WHERE user_code IN ($placeholders) OR blocked_by_code IN ($placeholders)");
    $delBlocked->execute($doubleCodes);

    // حذف از reports
    $delReports = $db->prepare("DELETE FROM reports WHERE reported_code IN ($placeholders) OR reporter_code IN ($placeholders)");
    $delReports->execute($doubleCodes);

    // حذف از deleted_conversations
    $delConv = $db->prepare("DELETE FROM deleted_conversations WHERE user_code IN ($placeholders) OR other_code IN ($placeholders)");
    $delConv->execute($doubleCodes);

    // ۵. دریافت وضعیت جدید کاربران با نام زهرا
    $stmtZahra = $db->query("SELECT user_code, user_name, device_id, device_model, color_num, last_seen FROM users WHERE user_name LIKE '%zahra%' OR user_name LIKE '%زهرا%'");
    $remainingZahras = $stmtZahra->fetchAll(PDO::FETCH_ASSOC);

    // تعداد کل کاربران باقی‌مانده
    $totalUsers = $db->query("SELECT COUNT(*) as count FROM users")->fetchColumn();

    $response = [
        'success' => true,
        'message' => 'پاک‌سازی اکانت‌های تکراری و شبح با موفقیت کامل انجام شد.',
        'deleted_codes' => $codesToDelete,
        'deleted_users_count' => $deletedUsersCount,
        'reassigned_messages' => [
            'zahra_public_messages' => $reassignedZahraMsg,
            'zahra_private_messages' => $reassignedZahraPrivFrom + $reassignedZahraPrivTo
        ],
        'total_remaining_users' => (int)$totalUsers,
        'remaining_zahra_accounts' => $remainingZahras
    ];

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطا در پاک‌سازی: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
