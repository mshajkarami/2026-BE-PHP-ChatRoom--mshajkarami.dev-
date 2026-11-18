<?php
require_once __DIR__ . '/db.php';
$db = getDB();
$ct = $db->query("SHOW CREATE TABLE private_messages")->fetch();
$cnt = $db->query("SELECT COUNT(*) as c FROM private_messages")->fetch();
$last = $db->query("SELECT * FROM private_messages ORDER BY id DESC LIMIT 5")->fetchAll();
echo json_encode(['create_table' => $ct, 'count' => $cnt, 'last' => $last]);
