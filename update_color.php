<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = trim($input['user_code'] ?? '');
$color = isset($input['color']) ? (int)$input['color'] : -1;
$type = trim($input['type'] ?? 'chat');

if (empty($userCode)) {
    sendJSON(['success' => false, 'message' => 'user_code is required']);
}

try {
    if ($type === 'custom') {
        $customColor = trim($input['custom_color'] ?? '');
        updateUserCustomColor($userCode, $customColor);
        sendJSON([
            'success' => true,
            'message' => 'Custom color updated',
            'user' => getUser($userCode)
        ]);
    }

    if ($color < 0 || $color > 50) {
        sendJSON(['success' => false, 'message' => 'color must be between 0 and 50']);
    }

    if ($type === 'chat') {
        updateUserColor($userCode, $color);
    } else if ($type === 'inbox') {
        updateUserInboxColor($userCode, $color);
    }

    sendJSON([
        'success' => true,
        'message' => 'Color updated',
        'user' => getUser($userCode)
    ]);
} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
