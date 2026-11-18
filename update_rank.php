<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$userCode = $input['user_code'] ?? '';
$rank = isset($input['rank']) ? (int)$input['rank'] : -1;

if (empty($userCode)) {
    sendJSON(['success' => false, 'message' => 'user_code is required']);
}

if ($rank < 0 || $rank > 200) {
    sendJSON(['success' => false, 'message' => 'rank must be between 0 and 200']);
}

try {
    updateUserRank($userCode, $rank);

    sendJSON([
        'success' => true,
        'message' => 'Rank updated',
        'user' => getUser($userCode)
    ]);

} catch (Exception $e) {
    sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>