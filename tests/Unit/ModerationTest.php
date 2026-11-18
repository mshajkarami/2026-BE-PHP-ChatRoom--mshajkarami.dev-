<?php
namespace Tests\Unit;

use Tests\Framework\TestCase;
use Tests\Framework\DatabaseHarness;
use PDO;

class ModerationTest extends TestCase {
    private PDO $db;

    public function setUp(): void {
        DatabaseHarness::resetDatabase();
        $this->db = DatabaseHarness::getTestDB();
    }

    public function testBanUserUpdatesBothBannedTableAndUserStatus(): void {
        $userCode = '120';
        $userName = 'کاربر متخلف';
        $adminCode = '1';
        $adminName = 'مدیر کل';
        $reason = 'استفاده از کلمات نامناسب';

        // کاربر در دیتابیس
        $this->db->prepare("INSERT INTO users (user_code, user_name, is_banned) VALUES (?, ?, 0)")
            ->execute([$userCode, $userName]);

        // اعمال بن
        $banStmt = $this->db->prepare("INSERT INTO banned_users 
            (user_code, user_name, rank_num, admin_code, admin_name, admin_rank, reason, date, time, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $banStmt->execute([$userCode, $userName, 0, $adminCode, $adminName, 100, $reason, '1405-08-28', '14:20:00', time() * 1000]);

        $this->db->prepare("UPDATE users SET is_banned = 1, ban_reason = ? WHERE user_code = ?")
            ->execute([$reason, $userCode]);

        // اعتبارسنجی
        $checkBan = $this->db->query("SELECT * FROM banned_users WHERE user_code = '{$userCode}'")->fetch();
        $this->assertNotNull($checkBan);
        $this->assertEquals($reason, $checkBan['reason']);
        $this->assertEquals($adminCode, $checkBan['admin_code']);

        $user = $this->db->query("SELECT is_banned, ban_reason FROM users WHERE user_code = '{$userCode}'")->fetch();
        $this->assertEquals(1, $user['is_banned']);
        $this->assertEquals($reason, $user['ban_reason']);
    }

    public function testUnbanUserClearsBannedStatus(): void {
        $userCode = '121';
        $this->db->exec("INSERT INTO users (user_code, user_name, is_banned, ban_reason) VALUES ('{$userCode}', 'User 121', 1, 'دلیل تستی')");
        $this->db->exec("INSERT INTO banned_users (user_code, user_name) VALUES ('{$userCode}', 'User 121')");

        // لغو بن
        $this->db->prepare("DELETE FROM banned_users WHERE user_code = ?")->execute([$userCode]);
        $this->db->prepare("UPDATE users SET is_banned = 0, ban_reason = '' WHERE user_code = ?")->execute([$userCode]);

        $checkBan = $this->db->query("SELECT * FROM banned_users WHERE user_code = '{$userCode}'")->fetch();
        $this->assertFalse($checkBan);

        $user = $this->db->query("SELECT is_banned, ban_reason FROM users WHERE user_code = '{$userCode}'")->fetch();
        $this->assertEquals(0, $user['is_banned']);
        $this->assertEquals('', $user['ban_reason']);
    }

    public function testBlockAndUnblockUserPair(): void {
        $myCode = '130';
        $blockedCode = '131';
        $blockedName = 'کاربر مزاحم';

        // بلاک کردن
        $stmt = $this->db->prepare("SELECT id FROM blocked_users WHERE blocked_by_code = ? AND user_code = ?");
        $stmt->execute([$myCode, $blockedCode]);
        if (!$stmt->fetch()) {
            $this->db->prepare("INSERT INTO blocked_users (user_code, user_name, blocked_by_code, blocked_at) VALUES (?, ?, ?, ?)")
                ->execute([$blockedCode, $blockedName, $myCode, time() * 1000]);
        }

        // بررسی بلاک بودن
        $stmt->execute([$myCode, $blockedCode]);
        $isBlocked = ($stmt->fetch() !== false);
        $this->assertTrue($isBlocked, "User must be marked as blocked");

        // آنبلاک کردن
        $this->db->prepare("DELETE FROM blocked_users WHERE blocked_by_code = ? AND user_code = ?")
            ->execute([$myCode, $blockedCode]);

        $stmt->execute([$myCode, $blockedCode]);
        $isBlockedAfter = ($stmt->fetch() !== false);
        $this->assertFalse($isBlockedAfter, "User must not be blocked after unblock");
    }

    public function testAntiShootToggle(): void {
        $userCode = '140';

        // فعال‌سازی ضد شوت
        $this->db->prepare("INSERT OR REPLACE INTO anti_shoot (user_code, is_active) VALUES (?, 1)")
            ->execute([$userCode]);

        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM anti_shoot WHERE user_code = ? AND is_active = 1");
        $stmt->execute([$userCode]);
        $row = $stmt->fetch();
        $this->assertTrue(($row['count'] ?? 0) > 0, "Anti-shoot must be active");

        // غیرفعال‌سازی
        $this->db->prepare("DELETE FROM anti_shoot WHERE user_code = ?")->execute([$userCode]);

        $stmt->execute([$userCode]);
        $rowAfter = $stmt->fetch();
        $this->assertFalse(($rowAfter['count'] ?? 0) > 0, "Anti-shoot must be deactivated");
    }

    public function testSaveAndManageReports(): void {
        $reportData = [
            'reporter_code' => '101',
            'reporter_name' => 'شاکی',
            'reported_code' => '102',
            'reported_name' => 'متهم',
            'message_text' => 'متن پیام متخلفانه',
            'reason' => 'اسپم مکرر',
            'date' => '1405-08-28',
            'time' => '15:00',
            'status' => 'new'
        ];

        $stmt = $this->db->prepare("INSERT INTO reports 
            (reporter_code, reporter_name, reported_code, reported_name, message_text, reason, date, time, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $reportData['reporter_code'],
            $reportData['reporter_name'],
            $reportData['reported_code'],
            $reportData['reported_name'],
            $reportData['message_text'],
            $reportData['reason'],
            $reportData['date'],
            $reportData['time'],
            $reportData['status'],
            time() * 1000
        ]);

        $reportId = $this->db->lastInsertId();
        $this->assertGreaterThan(0, (int)$reportId);

        // دریافت تعداد گزارش‌های جدید
        $countStmt = $this->db->query("SELECT COUNT(*) as count FROM reports WHERE status = 'new'");
        $this->assertEquals(1, $countStmt->fetch()['count']);

        // تغییر وضعیت گزارش به بررسی شده
        $this->db->prepare("UPDATE reports SET status = ? WHERE id = ?")->execute(['reviewed', $reportId]);

        $checkStmt = $this->db->prepare("SELECT status FROM reports WHERE id = ?");
        $checkStmt->execute([$reportId]);
        $this->assertEquals('reviewed', $checkStmt->fetch()['status']);
    }
}
