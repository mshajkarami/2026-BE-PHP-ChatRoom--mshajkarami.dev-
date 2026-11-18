<?php
require_once __DIR__ . '/db.php';

$input = getInput();

$userCode = $input['user_code'] ?? ($_GET['user_code'] ?? '');
$deviceId = $input['device_id'] ?? ($_GET['device_id'] ?? '');

if (!empty($deviceId)) {
    $userByDevice = getUserByDeviceId($deviceId);
    if ($userByDevice) {
        $userCode = $userByDevice['user_code'];
    }
}

if (empty($userCode)) {
    sendJSON([
        'success' => false,
        'message' => 'user_code is required'
    ]);
}

try {
    $user = getUser($userCode);

    if (!$user) {
        sendJSON([
            'success' => false,
            'message' => 'User not found'
        ]);
    }

    $isBanned = isBanned($userCode);

    sendJSON([
        'success' => true,
        'user' => $user,
        'is_banned' => $isBanned,
        'is_anti_shoot' => isAntiShoot($userCode)
    ]);

} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>