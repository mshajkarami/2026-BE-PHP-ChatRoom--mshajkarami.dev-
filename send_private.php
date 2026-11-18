<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$fromCode = trim($input['from_code'] ?? '');
$toCode = trim($input['to_code'] ?? '');
$message = $input['message'] ?? '';
$userAvatarUrl = $input['user_avatar_url'] ?? '';

if (empty($fromCode) || empty($toCode)) {
    sendJSON(['success' => false, 'message' => 'from_code and to_code are required']);
}

if (empty($message) && empty($input['image_path'])) {
    sendJSON(['success' => false, 'message' => 'message or image is required']);
}

if (isBanned($fromCode)) {
    sendJSON(['success' => false, 'message' => 'You are banned', 'is_banned' => true]);
}

try {
    if (isBlocked($toCode, $fromCode)) {
        sendJSON(['success' => false, 'message' => 'You are blocked by this user']);
    }

    $userBio = $input['user_bio'] ?? '';
    if (strpos($userBio, 'BANNER_B64:') === 0 || strpos($userBio, 'BANNER:') === 0) {
        $userBio = '';
    }

    $u = getUser($fromCode);

    $customColor = trim($input['custom_color'] ?? '');
    $colorNum = isset($input['color_num']) ? (int)$input['color_num'] : 0;
    $rankNum = isset($input['rank_num']) ? (int)$input['rank_num'] : 0;

    if (!empty($customColor)) {
        $colorNum = 0;
    } else if ($colorNum > 0) {
        $customColor = '';
    } else if ($u) {
        // Fallback for private messages must ONLY come from inbox_color (never public color_num or public custom_color)
        if (!empty($u['inbox_color'])) {
            $colorNum = (int)$u['inbox_color'];
            $customColor = '';
        } else {
            $colorNum = 0;
            $customColor = '';
        }
    }

    if ($rankNum <= 0 && $u && !empty($u['rank_num'])) {
        $rankNum = (int)$u['rank_num'];
    }

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO private_messages 
        (from_code, to_code, from_name, to_name, message, time, color_num, rank_num, is_sticker, is_read, user_bio, reply_to_id, reply_to_name, reply_to_text, image_path, is_image, user_avatar_url, custom_color, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $fromCode,
        $toCode,
        $input['from_name'] ?? '',
        $input['to_name'] ?? '',
        $message,
        $input['time'] ?? '',
        $colorNum,
        $rankNum,
        $input['is_sticker'] ?? 0,
        0,
        $userBio,
        $input['reply_to_id'] ?? 0,
        $input['reply_to_name'] ?? '',
        $input['reply_to_text'] ?? '',
        $input['image_path'] ?? '',
        $input['is_image'] ?? 0,
        $userAvatarUrl,
        $customColor,
        time() * 1000
    ]);

    $msgId = $db->lastInsertId();

    sendJSON([
        'success' => true,
        'message' => 'Private message sent',
        'message_id' => $msgId
    ]);

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
