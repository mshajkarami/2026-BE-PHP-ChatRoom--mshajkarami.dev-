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
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS hidden_shoot (user_code VARCHAR(20) PRIMARY KEY, is_active INT DEFAULT 1)");
    
    if ($active == 1) {
        $stmt = $db->prepare("INSERT INTO hidden_shoot (user_code, is_active) VALUES (?, 1) ON DUPLICATE KEY UPDATE is_active = 1");
        $stmt->execute([$userCode]);
    } else {
        $stmt = $db->prepare("DELETE FROM hidden_shoot WHERE user_code = ?");
        $stmt->execute([$userCode]);
    }

    sendJSON([
        'success' => true,
        'message' => $active == 1 ? 'Hidden shoot activated' : 'Hidden shoot deactivated'
    ]);

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
