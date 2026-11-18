<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

// تابع تبدیل ارقام فارسی و عربی به انگلیسی و حذف فاصله‌ها و کاراکترهای نامرئی
function sanitizeCode($val) {
    if ($val === null) return '';
    $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
    $english = ['0','1','2','3','4','5','6','7','8','9'];
    $str = str_replace($persian, $english, (string)$val);
    $str = str_replace($arabic, $english, $str);
    // فقط ارقام مجاز هستند (حذف نیم‌فاصله، اسپیس و کاراکترهای مخفی یونیکد)
    return preg_replace('/[^\d]/', '', $str);
}

$oldCode = sanitizeCode($input['old_code'] ?? '');
$newCode = sanitizeCode($input['new_code'] ?? '');
$newName = isset($input['new_name']) && trim($input['new_name']) !== '' ? trim($input['new_name']) : null;

if (empty($oldCode) || empty($newCode)) {
    sendJSON(['success' => false, 'message' => 'old_code and new_code are required']);
}

if ($oldCode === $newCode) {
    sendJSON(['success' => false, 'message' => 'کد جدید با کد قبلی یکسان است']);
}

try {
    $db = getDB();

    // ایجاد جداول احتمالی در صورت عدم وجود در هاست
    $db->exec("CREATE TABLE IF NOT EXISTS hidden_shoot (user_code VARCHAR(20) PRIMARY KEY, is_active INT DEFAULT 1)");
    $db->exec("CREATE TABLE IF NOT EXISTS anti_shoot (user_code VARCHAR(20) PRIMARY KEY, is_active INT DEFAULT 1)");

    // ۱. بررسی اینکه کاربر با کد قدیمی در سیستم وجود دارد
    $stmt = $db->prepare("SELECT * FROM users WHERE user_code = ?");
    $stmt->execute([$oldCode]);
    $oldUser = $stmt->fetch();
    if (!$oldUser) {
        sendJSON(['success' => false, 'message' => "کاربری با کد قدیمی ($oldCode) در سرور یافت نشد"]);
    }

    // ۲. بررسی اینکه کد جدید متعلق به ربات‌ها نباشد
    $stmt = $db->prepare("SELECT code FROM bots WHERE code = ?");
    $stmt->execute([$newCode]);
    if ($stmt->fetch()) {
        sendJSON(['success' => false, 'message' => 'این کد متعلق به ربات است و قابل اختصاص نیست']);
    }

    // ۳. بررسی اینکه آیا کد جدید از قبل در جدول users وجود دارد؟
    $stmt = $db->prepare("SELECT * FROM users WHERE user_code = ?");
    $stmt->execute([$newCode]);
    $existingNew = $stmt->fetch();

    if ($existingNew) {
        // حالت ۱: کد جدید از قبل در جدول users وجود دارد (ادغام دو حساب)
        $deviceId = !empty($oldUser['device_id']) ? $oldUser['device_id'] : $existingNew['device_id'];
        $deviceModel = !empty($oldUser['device_model']) ? $oldUser['device_model'] : $existingNew['device_model'];
        $nameToSet = $newName !== null ? $newName : (!empty($oldUser['user_name']) ? $oldUser['user_name'] : $existingNew['user_name']);
        $bioToSet = !empty($oldUser['user_bio']) ? $oldUser['user_bio'] : $existingNew['user_bio'];
        $avatarToSet = !empty($oldUser['avatar_url']) ? $oldUser['avatar_url'] : $existingNew['avatar_url'];
        $rankToSet = intval($oldUser['rank_num'] ?? 0) > 0 ? intval($oldUser['rank_num']) : intval($existingNew['rank_num'] ?? 0);
        $colorToSet = intval($oldUser['color_num'] ?? 0) > 0 ? intval($oldUser['color_num']) : intval($existingNew['color_num'] ?? 0);
        $inboxColorToSet = intval($oldUser['inbox_color'] ?? 0) > 0 ? intval($oldUser['inbox_color']) : intval($existingNew['inbox_color'] ?? 0);

        $customColorToSet = !empty($oldUser['custom_color']) ? $oldUser['custom_color'] : ($existingNew['custom_color'] ?? '');
        $stmt = $db->prepare("UPDATE users SET user_name = ?, user_bio = ?, avatar_url = ?, rank_num = ?, color_num = ?, inbox_color = ?, custom_color = ?, device_id = ?, device_model = ?, last_seen = ? WHERE user_code = ?");
        $stmt->execute([$nameToSet, $bioToSet, $avatarToSet, $rankToSet, $colorToSet, $inboxColorToSet, $customColorToSet, $deviceId, $deviceModel, time() * 1000, $newCode]);

        // انتقال پیام‌های عمومی و خصوصی
        $db->prepare("UPDATE messages SET user_code = ?, user_name = ? WHERE user_code = ?")->execute([$newCode, $nameToSet, $oldCode]);
        $db->prepare("UPDATE private_messages SET from_code = ?, from_name = ? WHERE from_code = ?")->execute([$newCode, $nameToSet, $oldCode]);
        $db->prepare("UPDATE private_messages SET to_code = ?, to_name = ? WHERE to_code = ?")->execute([$newCode, $nameToSet, $oldCode]);

        // انتقال ضد شوت و شوت مخفی
        $isAnti = $db->prepare("SELECT 1 FROM anti_shoot WHERE user_code = ?");
        $isAnti->execute([$oldCode]);
        if ($isAnti->fetch()) {
            $db->prepare("INSERT IGNORE INTO anti_shoot (user_code, is_active) VALUES (?, 1)")->execute([$newCode]);
        }

        $isHidden = $db->prepare("SELECT 1 FROM hidden_shoot WHERE user_code = ?");
        $isHidden->execute([$oldCode]);
        if ($isHidden->fetch()) {
            $db->prepare("INSERT IGNORE INTO hidden_shoot (user_code, is_active) VALUES (?, 1)")->execute([$newCode]);
        }

        // انتقال بلاک، بن، گزارش و مکالمات حذف‌شده با IGNORE
        $db->prepare("UPDATE IGNORE blocked_users SET user_code = ? WHERE user_code = ?")->execute([$newCode, $oldCode]);
        $db->prepare("UPDATE IGNORE blocked_users SET blocked_by_code = ? WHERE blocked_by_code = ?")->execute([$newCode, $oldCode]);
        $db->prepare("UPDATE banned_users SET user_code = ? WHERE user_code = ?")->execute([$newCode, $oldCode]);
        $db->prepare("UPDATE reports SET reporter_code = ? WHERE reporter_code = ?")->execute([$newCode, $oldCode]);
        $db->prepare("UPDATE reports SET reported_code = ? WHERE reported_code = ?")->execute([$newCode, $oldCode]);
        $db->prepare("UPDATE IGNORE deleted_conversations SET user_code = ? WHERE user_code = ?")->execute([$newCode, $oldCode]);
        $db->prepare("UPDATE IGNORE deleted_conversations SET other_code = ? WHERE other_code = ?")->execute([$newCode, $oldCode]);

        // پاک‌سازی کامل و قطعی کد قدیمی از تمام جداول تا کد فوراً ۱۰۰٪ آزاد شود
        $db->prepare("DELETE FROM users WHERE user_code = ?")->execute([$oldCode]);
        $db->prepare("DELETE FROM anti_shoot WHERE user_code = ?")->execute([$oldCode]);
        $db->prepare("DELETE FROM hidden_shoot WHERE user_code = ?")->execute([$oldCode]);
        $db->prepare("DELETE FROM banned_users WHERE user_code = ?")->execute([$oldCode]);
        $db->prepare("DELETE FROM blocked_users WHERE user_code = ? OR blocked_by_code = ?")->execute([$oldCode, $oldCode]);
        $db->prepare("DELETE FROM reports WHERE reported_code = ? OR reporter_code = ?")->execute([$oldCode, $oldCode]);
        $db->prepare("DELETE FROM deleted_conversations WHERE user_code = ? OR other_code = ?")->execute([$oldCode, $oldCode]);

        sendJSON([
            'success' => true,
            'message' => "کد با موفقیت ادغام شد. تمام سوابق منتقل گردید و کد $oldCode به طور کامل حذف و آزاد شد.",
            'action' => 'merged_and_deleted',
            'user' => getUser($newCode)
        ]);

    } else {
        // حالت ۲: کد جدید خالی و آزاد است → انتقال تمامی سوابق و آزادسازی قطعی کد قبلی
        updateUserCode($oldCode, $newCode, $newName);

        sendJSON([
            'success' => true,
            'message' => "کد کاربر از $oldCode به $newCode تغییر یافت، تمامی سوابق منتقل شد و کد $oldCode به طور کامل آزاد گردید.",
            'action' => 'renamed',
            'user' => getUser($newCode)
        ]);
    }

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}