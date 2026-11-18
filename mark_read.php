<?php
require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();
$myCode = trim($input['my_code'] ?? '');
$otherCode = trim($input['other_code'] ?? '');

if (empty($myCode) || empty($otherCode)) {
    sendJSON([
        'success' => false,
        'message' => 'my_code and other_code are required'
    ]);
}

try {
    $count = markAsRead($myCode, $otherCode);
    sendJSON([
        'success' => true,
        'message' => 'Marked as read',
        'updated_count' => $count
    ]);
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
