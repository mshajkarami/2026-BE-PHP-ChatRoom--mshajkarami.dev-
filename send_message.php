<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = trim($input['user_code'] ?? '');
$userName = trim($input['user_name'] ?? '');
$message = $input['message'] ?? '';
$userAvatarUrl = $input['user_avatar_url'] ?? '';
$deviceModel = $input['device_model'] ?? '';

if (empty($userCode) || empty($userName)) {
    sendJSON(['success' => false, 'message' => 'user_code and user_name are required']);
}

if (empty($message) && empty($input['image_path'])) {
    sendJSON(['success' => false, 'message' => 'message or image is required']);
}

if (isBanned($userCode)) {
    sendJSON(['success' => false, 'message' => 'You are banned', 'is_banned' => true]);
}

try {
    $userBio = $input['user_bio'] ?? '';
    if (strpos($userBio, 'BANNER_B64:') === 0 || strpos($userBio, 'BANNER:') === 0) {
        $userBio = '';
    }

    $u = getUser($userCode);

    $customColor = trim($input['custom_color'] ?? '');
    $colorNum = isset($input['color_num']) ? (int)$input['color_num'] : 0;
    $rankNum = isset($input['rank_num']) ? (int)$input['rank_num'] : 0;

    if (!empty($customColor)) {
        $colorNum = 0;
    } else if ($colorNum > 0) {
        $customColor = '';
    } else if ($u) {
        if (!empty($u['custom_color'])) {
            $customColor = $u['custom_color'];
            $colorNum = 0;
        } else if (!empty($u['color_num'])) {
            $colorNum = (int)$u['color_num'];
            $customColor = '';
        }
    }

    if ($rankNum <= 0 && $u && !empty($u['rank_num'])) {
        $rankNum = (int)$u['rank_num'];
    }

    registerUser($userCode, $userName, $userBio);

    $db = getDB();
    $stmt = $db->prepare("INSERT INTO messages 
        (user_code, user_name, message, time, color_num, rank_num, is_sticker, user_bio, reply_to_id, reply_to_name, reply_to_text, image_path, is_image, user_avatar_url, device_model, custom_color, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->execute([
        $userCode,
        $userName,
        $message,
        $input['time'] ?? '',
        $colorNum,
        $rankNum,
        $input['is_sticker'] ?? 0,
        $userBio,
        $input['reply_to_id'] ?? 0,
        $input['reply_to_name'] ?? '',
        $input['reply_to_text'] ?? '',
        $input['image_path'] ?? '',
        $input['is_image'] ?? 0,
        $userAvatarUrl,
        $deviceModel,
        $customColor,
        time() * 1000
    ]);

    $msgId = $db->lastInsertId();

    sendJSON([
        'success' => true,
        'message' => 'Message sent',
        'message_id' => $msgId
    ]);

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
