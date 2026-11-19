<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = trim((string)($input['user_code'] ?? ''));
$color = isset($input['color']) ? (int)$input['color'] : -1;
$type = trim((string)($input['type'] ?? 'chat'));

if (empty($userCode)) {
    sendJSON(['success' => false, 'message' => 'کد کاربر الزامی است.']);
}

try {
    if ($type === 'custom') {
        $customColor = trim((string)($input['custom_color'] ?? ''));
        updateUserCustomColor($userCode, $customColor);
        sendJSON([
            'success' => true,
            'message' => !empty($customColor) ? 'رنگ‌ساز کاربر با موفقیت فعال شد.' : 'رنگ‌ساز کاربر با موفقیت غیرفعال شد.',
            'user' => getUser($userCode)
        ]);
    }

    if ($color < 0 || $color > 50) {
        sendJSON(['success' => false, 'message' => 'شماره رنگ باید بین ۰ تا ۵۰ باشد.']);
    }

    if ($type === 'chat') {
        updateUserColor($userCode, $color);
    } else if ($type === 'inbox') {
        updateUserInboxColor($userCode, $color);
    }

    sendJSON([
        'success' => true,
        'message' => 'رنگ با موفقیت ذخیره شد.',
        'user' => getUser($userCode)
    ]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'خطا در ثبت رنگ: ' . $e->getMessage()]);
}
