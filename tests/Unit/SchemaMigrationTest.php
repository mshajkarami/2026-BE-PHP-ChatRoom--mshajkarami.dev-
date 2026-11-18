<?php
namespace Tests\Unit;

use Tests\Framework\TestCase;
use Tests\Framework\DatabaseHarness;

class SchemaMigrationTest extends TestCase {

    public function testDatabaseHarnessInitializesAllTables(): void {
        $pdo = DatabaseHarness::createTestDatabase();
        $this->assertNotNull($pdo);

        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        $expectedTables = [
            'users',
            'messages',
            'private_messages',
            'deleted_conversations',
            'blocked_users',
            'banned_users',
            'anti_shoot',
            'hidden_shoot',
            'reports',
            'bots'
        ];

        foreach ($expectedTables as $expected) {
            $this->assertContains($expected, $tables, "Table {$expected} must exist in schema");
        }
    }

    public function testUserTableHasModernColumns(): void {
        $pdo = DatabaseHarness::getTestDB();
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $columns = array_map(function($col) { return $col['name']; }, $stmt->fetchAll());

        $required = [
            'user_code',
            'user_name',
            'user_bio',
            'avatar_url',
            'rank_num',
            'color_num',
            'inbox_color',
            'custom_color',
            'device_id',
            'device_model',
            'typing_to',
            'typing_at',
            'is_banned',
            'ban_reason',
            'last_seen',
            'created_at'
        ];

        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column users.{$col} must exist");
        }
    }

    public function testMessageTableHasModernColumns(): void {
        $pdo = DatabaseHarness::getTestDB();
        $stmt = $pdo->query("PRAGMA table_info(messages)");
        $columns = array_map(function($col) { return $col['name']; }, $stmt->fetchAll());

        $required = [
            'user_code',
            'user_name',
            'message',
            'color_num',
            'rank_num',
            'custom_color',
            'is_sticker',
            'user_bio',
            'reply_to_id',
            'reply_to_name',
            'reply_to_message',
            'image_path',
            'is_image',
            'avatar_url',
            'device_model',
            'created_at'
        ];

        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column messages.{$col} must exist");
        }
    }

    public function testPrivateMessageTableHasModernColumns(): void {
        $pdo = DatabaseHarness::getTestDB();
        $stmt = $pdo->query("PRAGMA table_info(private_messages)");
        $columns = array_map(function($col) { return $col['name']; }, $stmt->fetchAll());

        $required = [
            'from_code',
            'to_code',
            'from_name',
            'to_name',
            'message',
            'color_num',
            'rank_num',
            'custom_color',
            'is_sticker',
            'is_read',
            'user_bio',
            'reply_to_id',
            'reply_to_name',
            'reply_to_message',
            'image_path',
            'is_image',
            'avatar_url',
            'created_at'
        ];

        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column private_messages.{$col} must exist");
        }
    }

    public function testBannedUsersTableHasFullAuditColumns(): void {
        $pdo = DatabaseHarness::getTestDB();
        $stmt = $pdo->query("PRAGMA table_info(banned_users)");
        $columns = array_map(function($col) { return $col['name']; }, $stmt->fetchAll());

        $required = [
            'user_code',
            'user_name',
            'rank_num',
            'admin_code',
            'admin_name',
            'admin_rank',
            'reason',
            'user_message',
            'device_id',
            'banned_by_code',
            'date',
            'time',
            'banned_at',
            'expire_at',
            'created_at'
        ];

        foreach ($required as $col) {
            $this->assertContains($col, $columns, "Column banned_users.{$col} must exist");
        }
    }
}
