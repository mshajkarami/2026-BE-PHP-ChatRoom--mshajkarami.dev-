<?php
require_once __DIR__ . '/config.php';

requirePost();

$input = getInput();
$text = isset($input['text']) ? trim($input['text']) : '';
$link = isset($input['link']) ? trim($input['link']) : '';
$color = isset($input['color']) ? trim($input['color']) : '';

$bannerData = [
    'text' => $text,
    'link' => $link,
    'color' => $color,
    'updated_at' => round(microtime(true) * 1000)
];

// 1. Write directly to banner.json file (single source of truth for the room header)
$bannerFile = getBannerFilePath();
@file_put_contents($bannerFile, json_encode($bannerData, JSON_UNESCAPED_UNICODE));

// 2. Clean any leaked BANNER payloads from users table so it never appears in chat bubbles
try {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET user_bio = '' WHERE user_bio LIKE 'BANNER_B64:%' OR user_bio LIKE 'BANNER:%'");
    $stmt->execute();
    $stmt2 = $db->prepare("UPDATE messages SET user_bio = '' WHERE user_bio LIKE 'BANNER_B64:%' OR user_bio LIKE 'BANNER:%'");
    $stmt2->execute();
    $stmt3 = $db->prepare("UPDATE private_messages SET user_bio = '' WHERE user_bio LIKE 'BANNER_B64:%' OR user_bio LIKE 'BANNER:%'");
    $stmt3->execute();
} catch (Exception $e) {
}

sendJSON([
    'success' => true,
    'message' => 'Banner updated successfully',
    'banner' => $bannerData
]);
