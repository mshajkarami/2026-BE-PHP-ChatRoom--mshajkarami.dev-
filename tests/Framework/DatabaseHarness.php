<?php
namespace Tests\Framework;

use PDO;

class DatabaseHarness {
    private static ?PDO $pdo = null;

    public static function createTestDatabase(): PDO {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Register custom SQL functions for SQLite compatibility if needed
        $pdo->sqliteCreateFunction('UNIX_TIMESTAMP', function() {
            return time();
        });

        self::initSchema($pdo);
        self::$pdo = $pdo;
        return $pdo;
    }

    public static function getTestDB(): PDO {
        if (self::$pdo === null) {
            return self::createTestDatabase();
        }
        return self::$pdo;
    }

    public static function resetDatabase(): void {
        if (self::$pdo !== null) {
            self::initSchema(self::$pdo);
        }
    }

    private static function initSchema(PDO $pdo): void {
        $tables = [
            "DROP TABLE IF EXISTS users",
            "DROP TABLE IF EXISTS messages",
            "DROP TABLE IF EXISTS private_messages",
            "DROP TABLE IF EXISTS deleted_conversations",
            "DROP TABLE IF EXISTS blocked_users",
            "DROP TABLE IF EXISTS banned_users",
            "DROP TABLE IF EXISTS anti_shoot",
            "DROP TABLE IF EXISTS hidden_shoot",
            "DROP TABLE IF EXISTS reports",
            "DROP TABLE IF EXISTS bots",

            "CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_code TEXT UNIQUE NOT NULL,
                user_name TEXT NOT NULL,
                user_bio TEXT DEFAULT '',
                avatar_url TEXT DEFAULT '',
                rank_num INTEGER DEFAULT 0,
                color_num INTEGER DEFAULT 0,
                inbox_color INTEGER DEFAULT 0,
                custom_color TEXT DEFAULT '',
                device_id TEXT DEFAULT '',
                device_model TEXT DEFAULT '',
                typing_to TEXT DEFAULT '',
                typing_at INTEGER DEFAULT 0,
                is_banned INTEGER DEFAULT 0,
                ban_reason TEXT DEFAULT '',
                last_seen INTEGER DEFAULT 0,
                created_at INTEGER DEFAULT 0
            )",

            "CREATE TABLE messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_code TEXT NOT NULL,
                user_name TEXT NOT NULL,
                message TEXT NOT NULL,
                time TEXT DEFAULT '',
                color_num INTEGER DEFAULT 0,
                rank_num INTEGER DEFAULT 0,
                custom_color TEXT DEFAULT '',
                is_sticker INTEGER DEFAULT 0,
                user_bio TEXT DEFAULT '',
                reply_to_id INTEGER DEFAULT 0,
                reply_to_name TEXT DEFAULT '',
                reply_to_message TEXT DEFAULT '',
                reply_to_text TEXT DEFAULT '',
                image_path TEXT DEFAULT '',
                is_image INTEGER DEFAULT 0,
                avatar_url TEXT DEFAULT '',
                user_avatar_url TEXT DEFAULT '',
                device_model TEXT DEFAULT '',
                server_id TEXT DEFAULT '',
                client_id TEXT DEFAULT '',
                created_at INTEGER DEFAULT 0
            )",

            "CREATE TABLE private_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                from_code TEXT NOT NULL,
                to_code TEXT NOT NULL,
                from_name TEXT NOT NULL,
                to_name TEXT NOT NULL,
                message TEXT NOT NULL,
                time TEXT DEFAULT '',
                color_num INTEGER DEFAULT 0,
                rank_num INTEGER DEFAULT 0,
                custom_color TEXT DEFAULT '',
                is_sticker INTEGER DEFAULT 0,
                is_read INTEGER DEFAULT 0,
                user_bio TEXT DEFAULT '',
                reply_to_id INTEGER DEFAULT 0,
                reply_to_name TEXT DEFAULT '',
                reply_to_message TEXT DEFAULT '',
                reply_to_text TEXT DEFAULT '',
                image_path TEXT DEFAULT '',
                is_image INTEGER DEFAULT 0,
                avatar_url TEXT DEFAULT '',
                user_avatar_url TEXT DEFAULT '',
                server_id TEXT DEFAULT '',
                client_id TEXT DEFAULT '',
                created_at INTEGER DEFAULT 0
            )",

            "CREATE TABLE deleted_conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_code TEXT NOT NULL,
                other_code TEXT DEFAULT '',
                target_code TEXT DEFAULT '',
                deleted_at INTEGER DEFAULT 0
            )",

            "CREATE TABLE blocked_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_code TEXT NOT NULL,
                user_name TEXT DEFAULT '',
                blocked_by_code TEXT NOT NULL,
                blocked_at INTEGER DEFAULT 0
            )",

            "CREATE TABLE banned_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_code TEXT NOT NULL,
                user_name TEXT DEFAULT '',
                rank_num INTEGER DEFAULT 0,
                admin_code TEXT DEFAULT '',
                admin_name TEXT DEFAULT '',
                admin_rank INTEGER DEFAULT 0,
                reason TEXT DEFAULT '',
                user_message TEXT DEFAULT '',
                device_id TEXT DEFAULT '',
                banned_by_code TEXT DEFAULT '',
                date TEXT DEFAULT '',
                time TEXT DEFAULT '',
                banned_at INTEGER DEFAULT 0,
                expire_at INTEGER DEFAULT 0,
                created_at INTEGER DEFAULT 0
            )",

            "CREATE TABLE anti_shoot (
                user_code TEXT PRIMARY KEY,
                is_active INTEGER DEFAULT 1
            )",

            "CREATE TABLE hidden_shoot (
                user_code TEXT PRIMARY KEY,
                is_active INTEGER DEFAULT 1
            )",

            "CREATE TABLE reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reporter_code TEXT DEFAULT '',
                reporter_name TEXT DEFAULT '',
                reported_code TEXT DEFAULT '',
                reported_name TEXT DEFAULT '',
                message_text TEXT DEFAULT '',
                reason TEXT DEFAULT '',
                date TEXT DEFAULT '',
                time TEXT DEFAULT '',
                message_id INTEGER DEFAULT 0,
                is_private INTEGER DEFAULT 0,
                status TEXT DEFAULT 'new',
                screenshot_path TEXT DEFAULT '',
                created_at INTEGER DEFAULT 0
            )",

            "CREATE TABLE bots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                is_active INTEGER DEFAULT 1
            )"
        ];

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }
    }
}
