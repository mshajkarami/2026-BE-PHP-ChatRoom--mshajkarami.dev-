<?php
require_once __DIR__ . '/db.php';
$db = getDB();
$blocked = $db->query('SELECT * FROM blocked_users')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['blocked' => $blocked]);
