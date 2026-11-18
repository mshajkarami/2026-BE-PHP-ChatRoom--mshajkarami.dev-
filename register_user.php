<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userName = $input['user_name'] ?? '';
$userBio = $input['user_bio'] ?? '';
if (strpos($userBio, 'BANNER_B64:') === 0 || strpos($userBio, 'BANNER:') === 0) {
    $userBio = '';
}
$deviceId = $input['device_id'] ?? '';
$deviceModel = $input['device_model'] ?? '';

if (empty($userName)) {
    sendJSON(['success' => false, 'message' => 'user_name is required']);
}

if (empty($deviceId)) {
    sendJSON([
        'success' => false,
        'message' => 'دستگاه شما شناسایی نشد، لطفاً دوباره تلاش کنید.'
    ]);
}

try {
    $db = getDB();

    // ⭐ کاربر فعلی رو با device_id پیدا کن
    $stmt = $db->prepare("SELECT user_code FROM users WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    $existing = $stmt->fetch();

    if ($existing) {
        // ⭐ کاربر قبلی → فقط همان کاربر را آپدیت کن (همون کد می‌مونه)
        $stmt = $db->prepare("UPDATE users SET user_name = ?, user_bio = ?, device_model = ?, last_seen = ? WHERE user_code = ?");
        $stmt->execute([$userName, $userBio, $deviceModel, time() * 1000, $existing['user_code']]);

        sendJSON([
            'success' => true,
            'message' => 'User found',
            'action' => 'found',
            'user' => getUser($existing['user_code'])
        ]);
    } else {
        // ⭐ کاربر جدید → اولین کد آزاد (پر کردن جاهای خالی و حذف‌شده از ۱۴۴ به بالا)
        $newCode = generateNextUserCode();
        
        registerUser($newCode, $userName, $userBio, $deviceId, $deviceModel);
        
        sendJSON([
            'success' => true,
            'message' => 'New user registered',
            'action' => 'registered',
            'user' => getUser($newCode)
        ]);
    }

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// ⭐ ساخت کد پشت سر هم — پر کردن کدهای خالی و آزاد از ۱۴۴ به بالا
function generateNextUserCode() {
    $db = getDB();
    
    // کدهای ربات‌ها برای جلوگیری از تداخل
    $botCodes = ['999', '999999'];
    try {
        $stmtBots = $db->query("SELECT code FROM bots");
        if ($stmtBots) {
            $fetched = $stmtBots->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($fetched)) {
                $botCodes = array_merge($botCodes, $fetched);
            }
        }
    } catch (Exception $e) {}
    $reserved = array_flip($botCodes);

    // از ۱۴۴ به بالا، اولین شماره‌ای که در جدول users وجود ندارد را پیدا کن
    $candidate = 144;
    while ($candidate < 1000) {
        $code = (string)$candidate;
        if (!isset($reserved[$code])) {
            $stmt = $db->prepare("SELECT 1 FROM users WHERE user_code = ? LIMIT 1");
            $stmt->execute([$code]);
            if (!$stmt->fetch()) {
                // این شماره در جدول users وجود ندارد و آزاد است!
                return $code;
            }
        }
        $candidate++;
    }
    
    return (string)$candidate;
}