<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = $input['user_code'] ?? '';
$active = isset($input['active']) ? (int)$input['active'] : 1;

if (empty($userCode)) {
    sendJSON(['success' => false, 'message' => 'user_code is required']);
}

try {
    setAntiShoot($userCode, $active == 1);

    sendJSON([
        'success' => true,
        'message' => $active == 1 ? 'Anti-shoot activated' : 'Anti-shoot deactivated',
        'is_anti_shoot' => isAntiShoot($userCode)
    ]);

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>