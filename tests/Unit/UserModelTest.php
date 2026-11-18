<?php
namespace Tests\Unit;

use Tests\Framework\TestCase;
use Tests\Framework\DatabaseHarness;
use PDO;

class UserModelTest extends TestCase {
    private PDO $db;

    public function setUp(): void {
        DatabaseHarness::resetDatabase();
        $this->db = DatabaseHarness::getTestDB();
    }

    public function testRegisterNewUser(): void {
        $userCode = '144';
        $userName = 'کاربر تستی ۱';
        $userBio = 'بیوگرافی کاربر ۱';
        $deviceId = 'device_uuid_12345';
        $deviceModel = 'Samsung Galaxy S24';

        $stmt = $this->db->prepare("INSERT INTO users (user_code, user_name, user_bio, device_id, device_model, last_seen, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userCode, $userName, $userBio, $deviceId, $deviceModel, time() * 1000, time() * 1000]);

        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_code = ?");
        $stmt->execute([$userCode]);
        $user = $stmt->fetch();

        $this->assertNotNull($user);
        $this->assertEquals($userName, $user['user_name']);
        $this->assertEquals($userBio, $user['user_bio']);
        $this->assertEquals($deviceId, $user['device_id']);
        $this->assertEquals($deviceModel, $user['device_model']);
        $this->assertEquals(0, $user['rank_num']);
        $this->assertEquals('', $user['custom_color']);
    }

    public function testRegisterExistingDeviceUpdatesProfileWithoutChangingCode(): void {
        $deviceId = 'device_persistent_abc';
        
        // کاربر اولیه
        $stmt = $this->db->prepare("INSERT INTO users (user_code, user_name, user_bio, device_id, device_model, last_seen) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['145', 'نام اولیه', 'بیو ۱', $deviceId, 'Xiaomi 13', 1000]);

        // ورود مجدد کاربر از همان دستگاه با نام جدید
        $stmt = $this->db->prepare("SELECT * FROM users WHERE device_id = ?");
        $stmt->execute([$deviceId]);
        $existing = $stmt->fetch();

        $this->assertNotNull($existing);
        $this->assertEquals('145', $existing['user_code']);

        // به روزرسانی
        $updateStmt = $this->db->prepare("UPDATE users SET user_name = ?, user_bio = ?, device_model = ?, last_seen = ? WHERE device_id = ?");
        $updateStmt->execute(['نام جدید ویرایش شده', 'بیو ۲', 'Xiaomi 14', 2000, $deviceId]);

        $checkStmt = $this->db->prepare("SELECT * FROM users WHERE device_id = ?");
        $checkStmt->execute([$deviceId]);
        $updated = $checkStmt->fetch();

        $this->assertEquals('145', $updated['user_code'], "User code must remain untouched on re-login");
        $this->assertEquals('نام جدید ویرایش شده', $updated['user_name']);
        $this->assertEquals('Xiaomi 14', $updated['device_model']);
    }

    public function testUserCodeSequenceAndGapFilling(): void {
        // درج کدهای 144 و 146 (کد 145 خالی است)
        $this->db->exec("INSERT INTO users (user_code, user_name) VALUES ('144', 'User 144')");
        $this->db->exec("INSERT INTO users (user_code, user_name) VALUES ('146', 'User 146')");
        $this->db->exec("INSERT INTO bots (code, name) VALUES ('999', 'Maryam Bot')");

        $reserved = ['999' => 0, '999999' => 1];

        // الگوریتم یافتن اولین کد خالی از 144 به بالا
        $candidate = 144;
        $found = null;
        while ($candidate < 1000) {
            $code = (string)$candidate;
            if (!isset($reserved[$code])) {
                $stmt = $this->db->prepare("SELECT 1 FROM users WHERE user_code = ? LIMIT 1");
                $stmt->execute([$code]);
                if (!$stmt->fetch()) {
                    $found = $code;
                    break;
                }
            }
            $candidate++;
        }

        $this->assertEquals('145', $found, "Algorithm must fill vacant gap at 145");
    }

    public function testRankUpdate(): void {
        $this->db->exec("INSERT INTO users (user_code, user_name, rank_num) VALUES ('150', 'User 150', 0)");

        $newRank = 10; // مدیر طلایی
        $stmt = $this->db->prepare("UPDATE users SET rank_num = ? WHERE user_code = ?");
        $stmt->execute([$newRank, '150']);

        $stmt = $this->db->prepare("SELECT rank_num FROM users WHERE user_code = ?");
        $stmt->execute(['150']);
        $user = $stmt->fetch();

        $this->assertEquals(10, $user['rank_num']);
    }

    public function testCustomColorSetsColorAndResetsPreset(): void {
        $this->db->exec("INSERT INTO users (user_code, user_name, color_num, custom_color) VALUES ('151', 'User 151', 5, '')");

        $hexColor = '#E91E63';
        $stmt = $this->db->prepare("UPDATE users SET custom_color = ?, color_num = 0 WHERE user_code = ?");
        $stmt->execute([$hexColor, '151']);

        $stmt = $this->db->prepare("SELECT color_num, custom_color FROM users WHERE user_code = ?");
        $stmt->execute(['151']);
        $user = $stmt->fetch();

        $this->assertEquals(0, $user['color_num']);
        $this->assertEquals('#E91E63', $user['custom_color']);
    }

    public function testPresetColorSetsColorAndResetsCustom(): void {
        $this->db->exec("INSERT INTO users (user_code, user_name, color_num, custom_color) VALUES ('152', 'User 152', 0, '#9C27B0')");

        $presetNum = 7;
        $stmt = $this->db->prepare("UPDATE users SET color_num = ?, custom_color = '' WHERE user_code = ?");
        $stmt->execute([$presetNum, '152']);

        $stmt = $this->db->prepare("SELECT color_num, custom_color FROM users WHERE user_code = ?");
        $stmt->execute(['152']);
        $user = $stmt->fetch();

        $this->assertEquals(7, $user['color_num']);
        $this->assertEquals('', $user['custom_color']);
    }

    public function testInboxColorIsolation(): void {
        // inbox_color باید کاملاً مستقل از color_num و custom_color عمومی باشد
        $this->db->exec("INSERT INTO users (user_code, user_name, color_num, custom_color, inbox_color) VALUES ('153', 'User 153', 3, '#FF9800', 0)");

        $inboxColor = 12;
        $stmt = $this->db->prepare("UPDATE users SET inbox_color = ? WHERE user_code = ?");
        $stmt->execute([$inboxColor, '153']);

        $stmt = $this->db->prepare("SELECT color_num, custom_color, inbox_color FROM users WHERE user_code = ?");
        $stmt->execute(['153']);
        $user = $stmt->fetch();

        $this->assertEquals(3, $user['color_num'], "Public color_num must not change");
        $this->assertEquals('#FF9800', $user['custom_color'], "Public custom_color must not change");
        $this->assertEquals(12, $user['inbox_color']);
    }

    public function testUserCodeMigrationAcrossAllTables(): void {
        $oldCode = '160';
        $newCode = '190';
        $newName = 'نام جدید مهاجرت‌یافته';

        // ایجاد داده در جداول مختلف برای کاربر ۱۶۰
        $this->db->exec("INSERT INTO users (user_code, user_name) VALUES ('{$oldCode}', 'نام قدیم')");
        $this->db->exec("INSERT INTO messages (user_code, user_name, message) VALUES ('{$oldCode}', 'نام قدیم', 'پیام عمومی قدیم')");
        $this->db->exec("INSERT INTO private_messages (from_code, to_code, from_name, to_name, message) VALUES ('{$oldCode}', '200', 'نام قدیم', 'کاربر دیگر', 'پیام خصوصی')");
        $this->db->exec("INSERT INTO anti_shoot (user_code, is_active) VALUES ('{$oldCode}', 1)");
        $this->db->exec("INSERT INTO hidden_shoot (user_code, is_active) VALUES ('{$oldCode}', 1)");

        // اجرای عملیات مهاجرت
        $this->db->prepare("UPDATE users SET user_code = ?, user_name = ? WHERE user_code = ?")->execute([$newCode, $newName, $oldCode]);
        $this->db->prepare("UPDATE messages SET user_code = ?, user_name = ? WHERE user_code = ?")->execute([$newCode, $newName, $oldCode]);
        $this->db->prepare("UPDATE private_messages SET from_code = ?, from_name = ? WHERE from_code = ?")->execute([$newCode, $newName, $oldCode]);
        $this->db->prepare("UPDATE anti_shoot SET user_code = ? WHERE user_code = ?")->execute([$newCode, $oldCode]);
        $this->db->prepare("UPDATE hidden_shoot SET user_code = ? WHERE user_code = ?")->execute([$newCode, $oldCode]);

        // حذف هرگونه ردپای کد قدیمی
        $this->db->prepare("DELETE FROM users WHERE user_code = ?")->execute([$oldCode]);

        // اعتبارسنجی
        $oldUser = $this->db->query("SELECT * FROM users WHERE user_code = '{$oldCode}'")->fetch();
        $this->assertFalse($oldUser, "Old user code must be deleted completely");

        $newUser = $this->db->query("SELECT * FROM users WHERE user_code = '{$newCode}'")->fetch();
        $this->assertNotNull($newUser);
        $this->assertEquals($newName, $newUser['user_name']);

        $newMsg = $this->db->query("SELECT * FROM messages WHERE user_code = '{$newCode}'")->fetch();
        $this->assertNotNull($newMsg);
        $this->assertEquals('پیام عمومی قدیم', $newMsg['message']);

        $newPriv = $this->db->query("SELECT * FROM private_messages WHERE from_code = '{$newCode}'")->fetch();
        $this->assertNotNull($newPriv);

        $newAnti = $this->db->query("SELECT * FROM anti_shoot WHERE user_code = '{$newCode}'")->fetch();
        $this->assertNotNull($newAnti);
    }
}
