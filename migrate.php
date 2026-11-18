<?php
/**
 * ═════════════════════════════════════════════════════════════════════
 * Database Auto-Migration & Schema Self-Healing Tool
 * ابزار خودکار ارتقا و همگام‌سازی ساختار پایگاه داده برای چت‌روم‌ها
 * ═════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$isCli = (php_sapi_name() === 'cli');

$results = [
    'success' => true,
    'brand' => BRAND_KEY,
    'database' => DB_NAME,
    'tables_checked' => 0,
    'tables_created' => [],
    'columns_added' => [],
    'indexes_added' => [],
    'engine_converted' => [],
    'errors' => []
];

try {
    $db = getDB();

    // ۱. ساخت جداول پایه با موتور InnoDB و یونی‌کد کامل
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_code` VARCHAR(50) UNIQUE NOT NULL,
            `user_name` VARCHAR(100) NOT NULL,
            `user_bio` TEXT NULL,
            `avatar_url` VARCHAR(255) DEFAULT '',
            `rank_num` INT DEFAULT 0,
            `color_num` INT DEFAULT 0,
            `inbox_color` INT DEFAULT 0,
            `custom_color` VARCHAR(100) DEFAULT '',
            `device_id` VARCHAR(100) DEFAULT '',
            `device_model` VARCHAR(100) DEFAULT '',
            `typing_to` VARCHAR(50) DEFAULT '',
            `typing_at` BIGINT DEFAULT 0,
            `is_banned` INT DEFAULT 0,
            `ban_reason` VARCHAR(255) DEFAULT '',
            `last_seen` BIGINT DEFAULT 0,
            `created_at` BIGINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'messages' => "CREATE TABLE IF NOT EXISTS `messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_code` VARCHAR(50) NOT NULL,
            `user_name` VARCHAR(100) NOT NULL,
            `message` TEXT NOT NULL,
            `time` VARCHAR(50) DEFAULT '',
            `color_num` INT DEFAULT 0,
            `rank_num` INT DEFAULT 0,
            `custom_color` VARCHAR(100) DEFAULT '',
            `is_sticker` INT DEFAULT 0,
            `user_bio` TEXT NULL,
            `reply_to_id` INT DEFAULT 0,
            `reply_to_name` VARCHAR(100) DEFAULT '',
            `reply_to_message` TEXT NULL,
            `image_path` VARCHAR(255) DEFAULT '',
            `is_image` INT DEFAULT 0,
            `avatar_url` VARCHAR(255) DEFAULT '',
            `server_id` VARCHAR(50) DEFAULT '',
            `client_id` VARCHAR(50) DEFAULT '',
            `created_at` BIGINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'private_messages' => "CREATE TABLE IF NOT EXISTS `private_messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `from_code` VARCHAR(50) NOT NULL,
            `to_code` VARCHAR(50) NOT NULL,
            `from_name` VARCHAR(100) NOT NULL,
            `to_name` VARCHAR(100) NOT NULL,
            `message` TEXT NOT NULL,
            `time` VARCHAR(50) DEFAULT '',
            `color_num` INT DEFAULT 0,
            `rank_num` INT DEFAULT 0,
            `custom_color` VARCHAR(100) DEFAULT '',
            `is_sticker` INT DEFAULT 0,
            `is_read` INT DEFAULT 0,
            `user_bio` TEXT NULL,
            `reply_to_id` INT DEFAULT 0,
            `reply_to_name` VARCHAR(100) DEFAULT '',
            `reply_to_message` TEXT NULL,
            `image_path` VARCHAR(255) DEFAULT '',
            `is_image` INT DEFAULT 0,
            `avatar_url` VARCHAR(255) DEFAULT '',
            `server_id` VARCHAR(50) DEFAULT '',
            `client_id` VARCHAR(50) DEFAULT '',
            `created_at` BIGINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'deleted_conversations' => "CREATE TABLE IF NOT EXISTS `deleted_conversations` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_code` VARCHAR(50) NOT NULL,
            `other_code` VARCHAR(50) DEFAULT '',
            `target_code` VARCHAR(50) DEFAULT '',
            `deleted_at` BIGINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'blocked_users' => "CREATE TABLE IF NOT EXISTS `blocked_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_code` VARCHAR(50) NOT NULL,
            `user_name` VARCHAR(100) DEFAULT '',
            `blocked_by_code` VARCHAR(50) NOT NULL,
            `blocked_at` BIGINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'banned_users' => "CREATE TABLE IF NOT EXISTS `banned_users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_code` VARCHAR(50) NOT NULL,
            `user_name` VARCHAR(100) DEFAULT '',
            `rank_num` INT DEFAULT 0,
            `admin_code` VARCHAR(50) DEFAULT '',
            `admin_name` VARCHAR(100) DEFAULT '',
            `admin_rank` INT DEFAULT 0,
            `reason` VARCHAR(255) DEFAULT '',
            `user_message` TEXT NULL,
            `device_id` VARCHAR(100) DEFAULT '',
            `banned_by_code` VARCHAR(50) DEFAULT '',
            `date` VARCHAR(50) DEFAULT '',
            `time` VARCHAR(50) DEFAULT '',
            `banned_at` BIGINT DEFAULT 0,
            `expire_at` BIGINT DEFAULT 0,
            `created_at` BIGINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'anti_shoot' => "CREATE TABLE IF NOT EXISTS `anti_shoot` (
            `user_code` VARCHAR(50) PRIMARY KEY,
            `is_active` INT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'hidden_shoot' => "CREATE TABLE IF NOT EXISTS `hidden_shoot` (
            `user_code` VARCHAR(50) PRIMARY KEY,
            `is_active` INT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'reports' => "CREATE TABLE IF NOT EXISTS `reports` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `reporter_code` VARCHAR(50) DEFAULT '',
            `reporter_name` VARCHAR(100) DEFAULT '',
            `reported_code` VARCHAR(50) DEFAULT '',
            `reported_name` VARCHAR(100) DEFAULT '',
            `message_text` TEXT NULL,
            `reason` VARCHAR(255) DEFAULT '',
            `date` VARCHAR(50) DEFAULT '',
            `time` VARCHAR(50) DEFAULT '',
            `message_id` INT DEFAULT 0,
            `is_private` INT DEFAULT 0,
            `status` VARCHAR(20) DEFAULT 'new',
            `screenshot_path` VARCHAR(255) DEFAULT '',
            `created_at` BIGINT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'bots' => "CREATE TABLE IF NOT EXISTS `bots` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `code` VARCHAR(50) UNIQUE NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `is_active` INT DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];

    foreach ($tables as $tName => $tSql) {
        $results['tables_checked']++;
        $tableExists = $db->query("SHOW TABLES LIKE '$tName'")->rowCount() > 0;
        $db->exec($tSql);
        if (!$tableExists) {
            $results['tables_created'][] = $tName;
        }

        // تبدیل موتور به InnoDB
        try {
            $statusStmt = $db->query("SHOW TABLE STATUS WHERE Name = '$tName'");
            $status = $statusStmt->fetch();
            if ($status && strtolower($status['Engine'] ?? '') !== 'innodb') {
                $db->exec("ALTER TABLE `$tName` ENGINE = InnoDB");
                $results['engine_converted'][] = $tName;
            }
        } catch (Exception $e) {}
    }

    // ۲. بررسی و افزودن ستون‌های مفقود (Self-Healing Columns)
    $requiredColumns = [
        'users' => [
            'custom_color' => "VARCHAR(100) DEFAULT ''",
            'inbox_color' => "INT DEFAULT 0",
            'avatar_url' => "VARCHAR(255) DEFAULT ''",
            'device_model' => "VARCHAR(100) DEFAULT ''",
            'typing_to' => "VARCHAR(50) DEFAULT ''",
            'typing_at' => "BIGINT DEFAULT 0",
            'is_banned' => "INT DEFAULT 0",
            'ban_reason' => "VARCHAR(255) DEFAULT ''",
            'created_at' => "BIGINT DEFAULT 0"
        ],
        'messages' => [
            'custom_color' => "VARCHAR(100) DEFAULT ''",
            'avatar_url' => "VARCHAR(255) DEFAULT ''",
            'reply_to_id' => "INT DEFAULT 0",
            'reply_to_name' => "VARCHAR(100) DEFAULT ''",
            'reply_to_message' => "TEXT NULL",
            'is_sticker' => "INT DEFAULT 0",
            'image_path' => "VARCHAR(255) DEFAULT ''",
            'is_image' => "INT DEFAULT 0",
            'server_id' => "VARCHAR(50) DEFAULT ''",
            'client_id' => "VARCHAR(50) DEFAULT ''",
            'created_at' => "BIGINT DEFAULT 0"
        ],
        'private_messages' => [
            'custom_color' => "VARCHAR(100) DEFAULT ''",
            'avatar_url' => "VARCHAR(255) DEFAULT ''",
            'reply_to_id' => "INT DEFAULT 0",
            'reply_to_name' => "VARCHAR(100) DEFAULT ''",
            'reply_to_message' => "TEXT NULL",
            'is_sticker' => "INT DEFAULT 0",
            'is_read' => "INT DEFAULT 0",
            'image_path' => "VARCHAR(255) DEFAULT ''",
            'is_image' => "INT DEFAULT 0",
            'server_id' => "VARCHAR(50) DEFAULT ''",
            'client_id' => "VARCHAR(50) DEFAULT ''",
            'created_at' => "BIGINT DEFAULT 0"
        ],
        'deleted_conversations' => [
            'other_code' => "VARCHAR(50) DEFAULT ''",
            'target_code' => "VARCHAR(50) DEFAULT ''"
        ],
        'banned_users' => [
            'rank_num' => "INT DEFAULT 0",
            'admin_code' => "VARCHAR(50) DEFAULT ''",
            'admin_name' => "VARCHAR(100) DEFAULT ''",
            'admin_rank' => "INT DEFAULT 0",
            'user_message' => "TEXT NULL",
            'device_id' => "VARCHAR(100) DEFAULT ''",
            'reason' => "VARCHAR(255) DEFAULT ''",
            'banned_by_code' => "VARCHAR(50) DEFAULT ''",
            'date' => "VARCHAR(50) DEFAULT ''",
            'time' => "VARCHAR(50) DEFAULT ''",
            'banned_at' => "BIGINT DEFAULT 0",
            'expire_at' => "BIGINT DEFAULT 0",
            'created_at' => "BIGINT DEFAULT 0"
        ],
        'reports' => [
            'status' => "VARCHAR(20) DEFAULT 'new'",
            'screenshot_path' => "VARCHAR(255) DEFAULT ''",
            'created_at' => "BIGINT DEFAULT 0"
        ]
    ];

    foreach ($requiredColumns as $table => $cols) {
        $stmtCols = $db->query("SHOW COLUMNS FROM `$table`");
        $existingCols = array_map(function($c) { return strtolower($c['Field']); }, $stmtCols->fetchAll());

        foreach ($cols as $colName => $colDef) {
            if (!in_array(strtolower($colName), $existingCols)) {
                $db->exec("ALTER TABLE `$table` ADD COLUMN `$colName` $colDef");
                $results['columns_added'][] = "$table.$colName";
            }
        }
    }

    // ۳. بررسی و اضافه کردن ایندکس‌های کلیدی جهت پرفورمنس
    $indexes = [
        'private_messages' => [
            'idx_from_to_id' => '(`from_code`, `to_code`, `id`)',
            'idx_to_from_id' => '(`to_code`, `from_code`, `id`)',
            'idx_unread_lookup' => '(`to_code`, `is_read`, `from_code`, `id`)'
        ],
        'messages' => [
            'idx_msg_user' => '(`user_code`, `id`)'
        ],
        'users' => [
            'idx_user_device' => '(`device_id`)'
        ],
        'blocked_users' => [
            'idx_block_pair' => '(`blocked_by_code`, `user_code`)'
        ]
    ];

    foreach ($indexes as $table => $idxList) {
        $stmtIdx = $db->query("SHOW INDEX FROM `$table`");
        $existingIdx = array_unique(array_map(function($i) { return $i['Key_name']; }, $stmtIdx->fetchAll()));

        foreach ($idxList as $idxName => $colsExpr) {
            if (!in_array($idxName, $existingIdx)) {
                try {
                    $db->exec("ALTER TABLE `$table` ADD INDEX `$idxName` $colsExpr");
                    $results['indexes_added'][] = "$table.$idxName";
                } catch (Exception $e) {
                    $results['errors'][] = "Index $idxName: " . $e->getMessage();
                }
            }
        }
    }

} catch (Exception $e) {
    $results['success'] = false;
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
