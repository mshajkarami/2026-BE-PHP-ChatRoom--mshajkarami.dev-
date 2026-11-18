<?php
namespace Tests\Unit;

use Tests\Framework\TestCase;
use Tests\Framework\DatabaseHarness;
use PDO;

class MessageModelTest extends TestCase {
    private PDO $db;

    public function setUp(): void {
        DatabaseHarness::resetDatabase();
        $this->db = DatabaseHarness::getTestDB();
    }

    public function testSaveAndRetrievePublicMessage(): void {
        $msg = [
            'user_code' => '144',
            'user_name' => 'رضا',
            'message' => 'سلام به همه دوستان',
            'time' => '12:30:45',
            'color_num' => 0,
            'rank_num' => 5,
            'custom_color' => '#4CAF50',
            'is_sticker' => 0,
            'user_bio' => 'بیو تستی',
            'reply_to_id' => 10,
            'reply_to_name' => 'علی',
            'reply_to_message' => 'پیام قبلی علی'
        ];

        $stmt = $this->db->prepare("INSERT INTO messages 
            (user_code, user_name, message, time, color_num, rank_num, custom_color, is_sticker, user_bio, reply_to_id, reply_to_name, reply_to_message, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $msg['user_code'],
            $msg['user_name'],
            $msg['message'],
            $msg['time'],
            $msg['color_num'],
            $msg['rank_num'],
            $msg['custom_color'],
            $msg['is_sticker'],
            $msg['user_bio'],
            $msg['reply_to_id'],
            $msg['reply_to_name'],
            $msg['reply_to_message'],
            time() * 1000
        ]);

        $msgId = $this->db->lastInsertId();
        $this->assertGreaterThan(0, (int)$msgId);

        $stmt = $this->db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$msgId]);
        $fetched = $stmt->fetch();

        $this->assertEquals('144', $fetched['user_code']);
        $this->assertEquals('سلام به همه دوستان', $fetched['message']);
        $this->assertEquals('#4CAF50', $fetched['custom_color']);
        $this->assertEquals(5, $fetched['rank_num']);
        $this->assertEquals(10, $fetched['reply_to_id']);
        $this->assertEquals('علی', $fetched['reply_to_name']);
    }

    public function testGetMessagesSincePagination(): void {
        for ($i = 1; $i <= 10; $i++) {
            $this->db->prepare("INSERT INTO messages (user_code, user_name, message) VALUES (?, ?, ?)")
                ->execute(['144', 'رضا', "پیام شماره {$i}"]);
        }

        // دریافت پیام‌های جدیدتر از پیام شماره ۷
        $sinceId = 7;
        $stmt = $this->db->prepare("SELECT * FROM messages WHERE id > ? ORDER BY id ASC LIMIT 50");
        $stmt->execute([$sinceId]);
        $rows = $stmt->fetchAll();

        $this->assertCount(3, $rows);
        $this->assertEquals('پیام شماره 8', $rows[0]['message']);
        $this->assertEquals('پیام شماره 10', $rows[2]['message']);
    }

    public function testSavePrivateMessageAndUnreadCount(): void {
        $from = '101';
        $to = '102';

        $stmt = $this->db->prepare("INSERT INTO private_messages 
            (from_code, to_code, from_name, to_name, message, custom_color, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
        
        $stmt->execute([$from, $to, 'فرستنده', 'گیرنده', 'پیام محرمانه ۱', '#FF5722', time() * 1000]);
        $stmt->execute([$from, $to, 'فرستنده', 'گیرنده', 'پیام محرمانه ۲', '#FF5722', time() * 1000]);

        // شمارش خوانده‌نشده برای کاربر ۱۰۲
        $countStmt = $this->db->prepare("SELECT COUNT(*) as count FROM private_messages WHERE to_code = ? AND is_read = 0");
        $countStmt->execute([$to]);
        $countRow = $countStmt->fetch();

        $this->assertEquals(2, $countRow['count']);

        // علامت‌گذاری به عنوان خوانده شده
        $markStmt = $this->db->prepare("UPDATE private_messages SET is_read = 1 WHERE to_code = ? AND from_code = ? AND is_read = 0");
        $markStmt->execute([$to, $from]);

        $countStmt->execute([$to]);
        $afterMark = $countStmt->fetch();
        $this->assertEquals(0, $afterMark['count'], "Unread count must become 0 after mark read");
    }

    public function testConversationBidirectionalLoading(): void {
        $userA = '105';
        $userB = '106';

        // پیام از A به B
        $this->db->prepare("INSERT INTO private_messages (from_code, to_code, from_name, to_name, message) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userA, $userB, 'A', 'B', 'سلام از A']);

        // پاسخ از B به A
        $this->db->prepare("INSERT INTO private_messages (from_code, to_code, from_name, to_name, message) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userB, $userA, 'B', 'A', 'علیک سلام از B']);

        // فراخوانی مکالمه
        $stmt = $this->db->prepare("
            SELECT * FROM private_messages 
            WHERE ((from_code = ? AND to_code = ?) OR (from_code = ? AND to_code = ?))
            ORDER BY id ASC
        ");
        $stmt->execute([$userA, $userB, $userB, $userA]);
        $conversation = $stmt->fetchAll();

        $this->assertCount(2, $conversation);
        $this->assertEquals('سلام از A', $conversation[0]['message']);
        $this->assertEquals('علیک سلام از B', $conversation[1]['message']);
    }

    public function testDeletedConversationFiltersPastMessages(): void {
        $userA = '110';
        $userB = '111';

        // پیام‌های قبل از حذف
        $this->db->prepare("INSERT INTO private_messages (from_code, to_code, from_name, to_name, message) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userA, $userB, 'A', 'B', 'پیام خیلی قدیمی']);
        $oldId = $this->db->lastInsertId();

        // ثبت حذف مکالمه توسط A تا آیدی oldId
        $this->db->prepare("INSERT INTO deleted_conversations (user_code, other_code, deleted_at) VALUES (?, ?, ?)")
            ->execute([$userA, $userB, $oldId]);

        // ارسال پیام جدید بعد از حذف
        $this->db->prepare("INSERT INTO private_messages (from_code, to_code, from_name, to_name, message) VALUES (?, ?, ?, ?, ?)")
            ->execute([$userB, $userA, 'B', 'A', 'پیام بعد از حذف']);
        $newId = $this->db->lastInsertId();

        // کوئری با فیلتر deleted_at
        $delStmt = $this->db->prepare("SELECT deleted_at FROM deleted_conversations WHERE user_code = ? AND other_code = ?");
        $delStmt->execute([$userA, $userB]);
        $row = $delStmt->fetch();
        $deletedAt = $row ? intval($row['deleted_at']) : 0;

        $stmt = $this->db->prepare("
            SELECT * FROM private_messages 
            WHERE ((from_code = ? AND to_code = ?) OR (from_code = ? AND to_code = ?))
            AND id > ?
            ORDER BY id ASC
        ");
        $stmt->execute([$userA, $userB, $userB, $userA, $deletedAt]);
        $visibleMessages = $stmt->fetchAll();

        $this->assertCount(1, $visibleMessages);
        $this->assertEquals('پیام بعد از حذف', $visibleMessages[0]['message']);
    }
}
