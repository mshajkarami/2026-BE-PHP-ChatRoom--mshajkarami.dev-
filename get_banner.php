<?php
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

$bannerFile = getBannerFilePath();

if (file_exists($bannerFile)) {
    $content = file_get_contents($bannerFile);
    $data = json_decode($content, true);
    if (is_array($data) && isset($data['text'])) {
        sendJSON([
            'success' => true,
            'banner' => [
                'text' => $data['text'] ?? '',
                'link' => $data['link'] ?? '',
                'color' => $data['color'] ?? ''
            ]
        ]);
    }
}

// Fallback to user 1's bio from database
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT user_bio FROM users WHERE user_code = '1'");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row && !empty($row['user_bio'])) {
        $bio = $row['user_bio'];
        $jsonStr = null;
        if (strpos($bio, 'BANNER_B64:') === 0) {
            $jsonStr = base64_decode(substr($bio, strlen('BANNER_B64:')));
        } else if (strpos($bio, 'BANNER:') === 0) {
            $jsonStr = substr($bio, strlen('BANNER:'));
        }
        if ($jsonStr) {
            $data = json_decode($jsonStr, true);
            if (is_array($data) && isset($data['text'])) {
                sendJSON([
                    'success' => true,
                    'banner' => [
                        'text' => $data['text'] ?? '',
                        'link' => $data['link'] ?? '',
                        'color' => $data['color'] ?? ''
                    ]
                ]);
            }
        }
    }
} catch (Exception $e) {
    // Ignore DB error, proceed to default
}

sendJSON([
    'success' => true,
    'banner' => [
        'text' => 'سلام',
        'link' => '',
        'color' => '#9C27B0'
    ]
]);
