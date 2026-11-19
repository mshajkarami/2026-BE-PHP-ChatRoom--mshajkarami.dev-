<?php
/**
 * ═════════════════════════════════════════════════════════════════════
 * Core Database & Model Operations Layer
 * لایه مرکزی عملیات دیتابیس و مدل‌های چت‌روم
 * ═════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

// ════════════════════════════════════════════
// ══════════ کاربران (Users) ═════════════════
// ════════════════════════════════════════════

function registerUser($userCode, $userName, $userBio = '', $deviceId = '', $deviceModel = '') {
    $db = getDB();

    if (!empty($deviceId)) {
        $stmt = $db->prepare("SELECT * FROM users WHERE device_id = ?");
        $stmt->execute([$deviceId]);
        $existingByDevice = $stmt->fetch();

        if ($existingByDevice) {
            $stmt = $db->prepare("UPDATE users SET user_name = ?, user_bio = ?, device_model = ?, last_seen = ? WHERE device_id = ?");
            $stmt->execute([$userName, $userBio, $deviceModel, time() * 1000, $deviceId]);
            return true;
        }
    }

    $stmt = $db->prepare("SELECT user_code FROM users WHERE user_code = ?");
    $stmt->execute([$userCode]);
    $exists = $stmt->fetch();

    if ($exists) {
        if (!empty($deviceId)) {
            $stmt = $db->prepare("UPDATE users SET user_name = ?, user_bio = ?, device_id = ?, device_model = ?, last_seen = ? WHERE user_code = ?");
            $stmt->execute([$userName, $userBio, $deviceId, $deviceModel, time() * 1000, $userCode]);
        } else {
            $stmt = $db->prepare("UPDATE users SET user_name = ?, user_bio = ?, device_model = ?, last_seen = ? WHERE user_code = ?");
            $stmt->execute([$userName, $userBio, $deviceModel, time() * 1000, $userCode]);
        }
    } else {
        $stmt = $db->prepare("INSERT INTO users (user_code, user_name, user_bio, device_id, device_model, last_seen, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userCode, $userName, $userBio, $deviceId, $deviceModel, time() * 1000, time() * 1000]);
    }

    return true;
}

function getUser($userCode) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE user_code = ?");
    $stmt->execute([$userCode]);
    return $stmt->fetch();
}

function getUserByDeviceId($deviceId) {
    if (empty($deviceId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE device_id = ?");
    $stmt->execute([$deviceId]);
    return $stmt->fetch();
}

function getAllUsers($limit = 50) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users ORDER BY last_seen DESC LIMIT ?");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function updateUserRank($userCode, $rank) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET rank_num = ? WHERE user_code = ?");
    $stmt->execute([$rank, $userCode]);
    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("SELECT user_code FROM users WHERE user_code = ?");
        $stmt->execute([$userCode]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO users (user_code, rank_num, last_seen, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userCode, $rank, time() * 1000, time() * 1000]);
        }
    }
    return true;
}

function updateUserColor($userCode, $color) {
    $db = getDB();
    $colorVal = max(0, (int)$color);
    if ($colorVal > 0) {
        $stmt = $db->prepare("UPDATE users SET color_num = ?, custom_color = '' WHERE user_code = ?");
        $stmt->execute([$colorVal, $userCode]);
    } else {
        $stmt = $db->prepare("UPDATE users SET color_num = 0, custom_color = '' WHERE user_code = ?");
        $stmt->execute([$userCode]);
    }
    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("SELECT user_code FROM users WHERE user_code = ?");
        $stmt->execute([$userCode]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO users (user_code, color_num, custom_color, last_seen, created_at) VALUES (?, ?, '', ?, ?)");
            $stmt->execute([$userCode, $colorVal, time() * 1000, time() * 1000]);
        }
    }
    try {
        if ($colorVal > 0) {
            $stmt = $db->prepare("UPDATE messages SET color_num = ?, custom_color = '' WHERE user_code = ? ORDER BY id DESC LIMIT 50");
            $stmt->execute([$colorVal, $userCode]);
        } else {
            $stmt = $db->prepare("UPDATE messages SET color_num = 0, custom_color = '' WHERE user_code = ? ORDER BY id DESC LIMIT 50");
            $stmt->execute([$userCode]);
        }
    } catch (Exception $e) {}
    return true;
}

function updateUserInboxColor($userCode, $color) {
    $db = getDB();
    $colorVal = max(0, (int)$color);
    $stmt = $db->prepare("UPDATE users SET inbox_color = ? WHERE user_code = ?");
    $stmt->execute([$colorVal, $userCode]);
    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("SELECT user_code FROM users WHERE user_code = ?");
        $stmt->execute([$userCode]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO users (user_code, inbox_color, last_seen, created_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userCode, $colorVal, time() * 1000, time() * 1000]);
        }
    }
    try {
        $stmt = $db->prepare("UPDATE private_messages SET color_num = ?, custom_color = '' WHERE from_code = ? ORDER BY id DESC LIMIT 50");
        $stmt->execute([$colorVal, $userCode]);
    } catch (Exception $e) {}
    return true;
}

function updateUserCustomColor($userCode, $customColor) {
    $db = getDB();
    $safeCustom = trim($customColor ?? '');
    if (!empty($safeCustom)) {
        $stmt = $db->prepare("UPDATE users SET custom_color = ?, color_num = 0 WHERE user_code = ?");
        $stmt->execute([$safeCustom, $userCode]);
    } else {
        $stmt = $db->prepare("UPDATE users SET custom_color = '' WHERE user_code = ?");
        $stmt->execute([$userCode]);
    }
    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("SELECT user_code FROM users WHERE user_code = ?");
        $stmt->execute([$userCode]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO users (user_code, custom_color, color_num, last_seen, created_at) VALUES (?, ?, 0, ?, ?)");
            $stmt->execute([$userCode, $safeCustom, time() * 1000, time() * 1000]);
        }
    }
    try {
        if (!empty($safeCustom)) {
            $stmt = $db->prepare("UPDATE messages SET custom_color = ?, color_num = 0 WHERE user_code = ?");
            $stmt->execute([$safeCustom, $userCode]);
        } else {
            $stmt = $db->prepare("UPDATE messages SET custom_color = '' WHERE user_code = ?");
            $stmt->execute([$userCode]);
        }
    } catch (Exception $e) {}
    return true;
}

function updateUserCode($oldCode, $newCode, $newName = null) {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS hidden_shoot (user_code VARCHAR(20) PRIMARY KEY, is_active INT DEFAULT 1)");
    $db->exec("CREATE TABLE IF NOT EXISTS anti_shoot (user_code VARCHAR(20) PRIMARY KEY, is_active INT DEFAULT 1)");

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE users SET user_code = ?, user_name = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE users SET user_code = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE messages SET user_code = ?, user_name = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE messages SET user_code = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE private_messages SET from_code = ?, from_name = ? WHERE from_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE private_messages SET from_code = ? WHERE from_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE private_messages SET to_code = ?, to_name = ? WHERE to_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE private_messages SET to_code = ? WHERE to_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE banned_users SET user_code = ?, user_name = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE banned_users SET user_code = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE IGNORE blocked_users SET user_code = ?, user_name = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE IGNORE blocked_users SET user_code = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    $stmt = $db->prepare("UPDATE IGNORE blocked_users SET blocked_by_code = ? WHERE blocked_by_code = ?");
    $stmt->execute([$newCode, $oldCode]);

    $stmt = $db->prepare("UPDATE IGNORE anti_shoot SET user_code = ? WHERE user_code = ?");
    $stmt->execute([$newCode, $oldCode]);

    $stmt = $db->prepare("UPDATE IGNORE hidden_shoot SET user_code = ? WHERE user_code = ?");
    $stmt->execute([$newCode, $oldCode]);

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE reports SET reporter_code = ?, reporter_name = ? WHERE reporter_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE reports SET reporter_code = ? WHERE reporter_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    if ($newName !== null) {
        $stmt = $db->prepare("UPDATE reports SET reported_code = ?, reported_name = ? WHERE reported_code = ?");
        $stmt->execute([$newCode, $newName, $oldCode]);
    } else {
        $stmt = $db->prepare("UPDATE reports SET reported_code = ? WHERE reported_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    }

    try {
        $stmt = $db->prepare("UPDATE IGNORE deleted_conversations SET user_code = ? WHERE user_code = ?");
        $stmt->execute([$newCode, $oldCode]);
        $stmt = $db->prepare("UPDATE IGNORE deleted_conversations SET other_code = ? WHERE other_code = ?");
        $stmt->execute([$newCode, $oldCode]);
    } catch (Exception $e) {}

    try {
        $stmt = $db->prepare("UPDATE users SET typing_to = ? WHERE typing_to = ?");
        $stmt->execute([$newCode, $oldCode]);
    } catch (Exception $e) {}

    $db->prepare("DELETE FROM users WHERE user_code = ?")->execute([$oldCode]);
    $db->prepare("DELETE FROM anti_shoot WHERE user_code = ?")->execute([$oldCode]);
    $db->prepare("DELETE FROM hidden_shoot WHERE user_code = ?")->execute([$oldCode]);
    $db->prepare("DELETE FROM banned_users WHERE user_code = ?")->execute([$oldCode]);
    $db->prepare("DELETE FROM blocked_users WHERE user_code = ? OR blocked_by_code = ?")->execute([$oldCode, $oldCode]);
    $db->prepare("DELETE FROM reports WHERE reported_code = ? OR reporter_code = ?")->execute([$oldCode, $oldCode]);
    try {
        $db->prepare("DELETE FROM deleted_conversations WHERE user_code = ? OR other_code = ?")->execute([$oldCode, $oldCode]);
    } catch (Exception $e) {}

    return true;
}

function searchUsers($query) {
    $db = getDB();
    $q = "%" . $query . "%";
    $stmt = $db->prepare("SELECT * FROM users WHERE user_name LIKE ? OR user_code LIKE ? ORDER BY last_seen DESC LIMIT 50");
    $stmt->execute([$q, $q]);
    return $stmt->fetchAll();
}

function getUsersWithRank() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE rank_num > 0 ORDER BY rank_num DESC, CAST(user_code AS UNSIGNED) ASC LIMIT 250");
    $stmt->execute();
    return $stmt->fetchAll();
}

// ════════════════════════════════════════════
// ══════════ پیام‌های عمومی (Public Chat) ═════
// ════════════════════════════════════════════

function saveMessage($msg) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO messages 
        (user_code, user_name, message, time, color_num, rank_num, is_sticker, user_bio, reply_to_id, reply_to_name, reply_to_message, image_path, is_image, avatar_url, custom_color, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $msg['user_code'] ?? '',
        $msg['user_name'] ?? '',
        $msg['message'] ?? '',
        $msg['time'] ?? '',
        $msg['color_num'] ?? 0,
        $msg['rank_num'] ?? 0,
        $msg['is_sticker'] ?? 0,
        $msg['user_bio'] ?? '',
        $msg['reply_to_id'] ?? 0,
        $msg['reply_to_name'] ?? '',
        $msg['reply_to_text'] ?? ($msg['reply_to_message'] ?? ''),
        $msg['image_path'] ?? '',
        $msg['is_image'] ?? 0,
        $msg['user_avatar_url'] ?? ($msg['avatar_url'] ?? ''),
        $msg['custom_color'] ?? '',
        time() * 1000
    ]);

    return $db->lastInsertId();
}

function getMessagesSince($sinceId = 0, $limit = 100) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM messages WHERE id > ? ORDER BY id ASC LIMIT ?");
    $stmt->bindValue(1, (int)$sinceId, PDO::PARAM_INT);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getLastMessages($limit = 100) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM messages ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll();
    return array_reverse($rows);
}

// ════════════════════════════════════════════
// ══════════ پیام‌های خصوصی (Private Chat) ════
// ════════════════════════════════════════════

function savePrivateMessage($msg) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO private_messages 
        (from_code, to_code, from_name, to_name, message, time, color_num, rank_num, is_sticker, is_read, user_bio, reply_to_id, reply_to_name, reply_to_message, image_path, is_image, avatar_url, custom_color, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $msg['from_code'] ?? '',
        $msg['to_code'] ?? '',
        $msg['from_name'] ?? '',
        $msg['to_name'] ?? '',
        $msg['message'] ?? '',
        $msg['time'] ?? '',
        $msg['color_num'] ?? 0,
        $msg['rank_num'] ?? 0,
        $msg['is_sticker'] ?? 0,
        $msg['is_read'] ?? 0,
        $msg['user_bio'] ?? '',
        $msg['reply_to_id'] ?? 0,
        $msg['reply_to_name'] ?? '',
        $msg['reply_to_text'] ?? ($msg['reply_to_message'] ?? ''),
        $msg['image_path'] ?? '',
        $msg['is_image'] ?? 0,
        $msg['user_avatar_url'] ?? ($msg['avatar_url'] ?? ''),
        $msg['custom_color'] ?? '',
        time() * 1000
    ]);

    return $db->lastInsertId();
}

function getConversation($code1, $code2, $sinceId = 0) {
    $db = getDB();
    
    $deletedAt = 0;
    try {
        $stmt = $db->prepare("SELECT deleted_at FROM deleted_conversations 
            WHERE user_code = ? AND (other_code = ? OR target_code = ?)");
        $stmt->execute([$code1, $code2, $code2]);
        $row = $stmt->fetch();
        $deletedAt = $row ? intval($row['deleted_at']) : 0;
    } catch (Exception $e) {}

    $minId = max((int)$sinceId, $deletedAt);

    if ($sinceId > 0) {
        $stmt = $db->prepare("SELECT * FROM private_messages 
            WHERE ((from_code = ? AND to_code = ?) OR (from_code = ? AND to_code = ?))
            AND id > ?
            ORDER BY id ASC
            LIMIT 200");
        $stmt->execute([$code1, $code2, $code2, $code1, $minId]);
        return $stmt->fetchAll();
    } else {
        $stmt = $db->prepare("SELECT * FROM (
            SELECT * FROM private_messages 
            WHERE ((from_code = ? AND to_code = ?) OR (from_code = ? AND to_code = ?))
            AND id > ?
            ORDER BY id DESC
            LIMIT 200
        ) sub ORDER BY id ASC");
        $stmt->execute([$code1, $code2, $code2, $code1, $minId]);
        return $stmt->fetchAll();
    }
}

function markAsRead($myCode, $fromCode) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE private_messages SET is_read = 1 WHERE to_code = ? AND from_code = ? AND is_read = 0");
    $stmt->execute([$myCode, $fromCode]);
    return $stmt->rowCount();
}

function getUnreadCount($myCode) {
    $db = getDB();
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM private_messages pm
            LEFT JOIN deleted_conversations dc 
                ON dc.user_code = ? AND (dc.other_code = pm.from_code OR dc.target_code = pm.from_code)
            WHERE pm.to_code = ? 
              AND pm.is_read = 0 
              AND (dc.deleted_at IS NULL OR pm.id > dc.deleted_at)
        ");
        $stmt->execute([$myCode, $myCode]);
        $row = $stmt->fetch();
        return intval($row['count'] ?? 0);
    } catch (Exception $e) {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM private_messages WHERE to_code = ? AND is_read = 0");
        $stmt->execute([$myCode]);
        $row = $stmt->fetch();
        return intval($row['count'] ?? 0);
    }
}

function getInboxLatest($myCode) {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT 
            CASE WHEN from_code = ? THEN to_code ELSE from_code END AS other_code,
            MAX(id) AS max_id
        FROM private_messages
        WHERE from_code = ? OR to_code = ?
        GROUP BY other_code
        ORDER BY max_id DESC
        LIMIT 250
    ");
    $stmt->execute([$myCode, $myCode, $myCode]);
    $latest = $stmt->fetchAll();
    
    if (empty($latest)) return [];
    
    $ids = [];
    foreach ($latest as $row) {
        $ids[] = intval($row['max_id']);
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM private_messages WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $messages = $stmt->fetchAll();
    
    $deleted = [];
    try {
        $stmt = $db->prepare("SELECT COALESCE(NULLIF(other_code, ''), target_code) AS other, deleted_at FROM deleted_conversations WHERE user_code = ?");
        $stmt->execute([$myCode]);
        while ($row = $stmt->fetch()) {
            $deleted[$row['other']] = intval($row['deleted_at']);
        }
    } catch (Exception $e) {}
    
    $result = [];
    foreach ($messages as $msg) {
        $other = ($msg['from_code'] == $myCode) ? $msg['to_code'] : $msg['from_code'];
        $deletedAt = $deleted[$other] ?? 0;
        if (intval($msg['id']) > $deletedAt) {
            $result[] = $msg;
        }
    }
    
    usort($result, function($a, $b) {
        return intval($b['id']) - intval($a['id']);
    });
    
    return $result;
}

function deleteConversation($myCode, $fromCode) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM private_messages WHERE (to_code = ? AND from_code = ?) OR (to_code = ? AND from_code = ?)");
    $stmt->execute([$myCode, $fromCode, $fromCode, $myCode]);
    return $stmt->rowCount();
}

// ════════════════════════════════════════════
// ══════════ بن و مسدودی (Ban & Block) ════════
// ════════════════════════════════════════════

function banUser($ban) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO banned_users 
        (user_code, user_name, rank_num, admin_code, admin_name, admin_rank, reason, user_message, date, time, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $ban['user_code'] ?? '',
        $ban['user_name'] ?? '',
        $ban['rank_num'] ?? 0,
        $ban['admin_code'] ?? '',
        $ban['admin_name'] ?? '',
        $ban['admin_rank'] ?? 0,
        $ban['reason'] ?? '',
        $ban['user_message'] ?? '',
        $ban['date'] ?? '',
        $ban['time'] ?? '',
        time() * 1000
    ]);

    $stmt = $db->prepare("UPDATE users SET is_banned = 1, ban_reason = ? WHERE user_code = ?");
    $stmt->execute([$ban['reason'] ?? '', $ban['user_code']]);

    return $db->lastInsertId();
}

function unbanUser($userCode) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM banned_users WHERE user_code = ?");
    $stmt->execute([$userCode]);

    $stmt = $db->prepare("UPDATE users SET is_banned = 0, ban_reason = '' WHERE user_code = ?");
    $stmt->execute([$userCode]);

    return true;
}

function isBanned($userCode) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM banned_users WHERE user_code = ?");
    $stmt->execute([$userCode]);
    $row = $stmt->fetch();
    return ($row['count'] ?? 0) > 0;
}

function getAllBanned() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM banned_users ORDER BY id DESC LIMIT 200");
    $stmt->execute();
    return $stmt->fetchAll();
}

function blockUser($myCode, $userCode, $userName) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM blocked_users WHERE blocked_by_code = ? AND user_code = ?");
    $stmt->execute([$myCode, $userCode]);
    if ($stmt->fetch()) return true;

    $stmt = $db->prepare("INSERT INTO blocked_users (user_code, user_name, blocked_by_code, blocked_at) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userCode, $userName, $myCode, time() * 1000]);
    return $db->lastInsertId();
}

function unblockUser($myCode, $userCode) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM blocked_users WHERE blocked_by_code = ? AND user_code = ?");
    $stmt->execute([$myCode, $userCode]);
    return $stmt->rowCount() > 0;
}

function isBlocked($myCode, $userCode) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM blocked_users WHERE blocked_by_code = ? AND user_code = ?");
    $stmt->execute([$myCode, $userCode]);
    return $stmt->fetch() !== false;
}

function getBlockedUsers($myCode) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM blocked_users WHERE blocked_by_code = ? ORDER BY id DESC LIMIT 200");
    $stmt->execute([$myCode]);
    return $stmt->fetchAll();
}

// ════════════════════════════════════════════
// ══════════ ضد شوت (Anti Shoot) ═════════════
// ════════════════════════════════════════════

function setAntiShoot($userCode, $active) {
    $db = getDB();
    if ($active) {
        $stmt = $db->prepare("INSERT INTO anti_shoot (user_code, is_active) VALUES (?, 1) 
            ON DUPLICATE KEY UPDATE is_active = 1");
        $stmt->execute([$userCode]);
    } else {
        $stmt = $db->prepare("DELETE FROM anti_shoot WHERE user_code = ?");
        $stmt->execute([$userCode]);
    }
    return true;
}

function isAntiShoot($userCode) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM anti_shoot WHERE user_code = ? AND is_active = 1");
    $stmt->execute([$userCode]);
    $row = $stmt->fetch();
    return ($row['count'] ?? 0) > 0;
}

// ════════════════════════════════════════════
// ══════════ گزارش‌ها (Reports) ═══════════════
// ════════════════════════════════════════════

function saveReport($report) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO reports 
        (reporter_code, reporter_name, reported_code, reported_name, message_text, reason, date, time, message_id, is_private, status, screenshot_path, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $report['reporter_code'] ?? '',
        $report['reporter_name'] ?? '',
        $report['reported_code'] ?? '',
        $report['reported_name'] ?? '',
        $report['message_text'] ?? '',
        $report['reason'] ?? '',
        $report['date'] ?? '',
        $report['time'] ?? '',
        $report['message_id'] ?? 0,
        $report['is_private'] ?? 0,
        $report['status'] ?? 'new',
        $report['screenshot_path'] ?? '',
        time() * 1000
    ]);

    return $db->lastInsertId();
}

function getAllReports() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM reports ORDER BY id DESC LIMIT 200");
    $stmt->execute();
    return $stmt->fetchAll();
}

function getNewReportsCount() {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reports WHERE status = 'new'");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row['count'] ?? 0;
}

function updateReportStatus($reportId, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE reports SET status = ? WHERE id = ?");
    $stmt->execute([$status, $reportId]);
    return $stmt->rowCount() > 0;
}

function deleteReport($reportId) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM reports WHERE id = ?");
    $stmt->execute([$reportId]);
    return $stmt->rowCount() > 0;
}
