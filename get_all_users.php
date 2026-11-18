<?php
require_once __DIR__ . '/db.php';

$input = getInput();

// Read limit from GET query parameter or POST/JSON input
$limit = 200; // Default to 200 if not specified
if (isset($_GET['limit'])) {
    $limit = (int)$_GET['limit'];
} elseif (isset($input['limit'])) {
    $limit = (int)$input['limit'];
}

// Allow up to 1000 users per request, minimum 1
if ($limit <= 0) {
    $limit = 200;
} elseif ($limit > 1000) {
    $limit = 1000;
}

try {
    $db = getDB();
    $totalCount = 0;
    try {
        $stmtTotal = $db->query("SELECT COUNT(*) FROM users");
        $totalCount = (int)$stmtTotal->fetchColumn();
    } catch (Exception $ignored) {}

    $users = getAllUsers($limit);
    $managers = getUsersWithRank();
    
    sendJSON([
        'success' => true,
        'count' => count($users),
        'total_count' => $totalCount,
        'users' => $users,
        'managers_count' => count($managers)
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}