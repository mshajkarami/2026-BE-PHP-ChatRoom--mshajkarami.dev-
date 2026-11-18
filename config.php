<?php
/**
 * ═════════════════════════════════════════════════════════════════════
 * Unified Multi-Tenant Backend Engine for Chat Rooms
 * موتور یکپارچه و چندمستأجره (Multi-Tenant) بک‌اند چت‌روم‌ها
 * Tolou Chat (chatiranian.ir) & Chat Golabi (chatgolabi.ir) & White-Label
 * ═════════════════════════════════════════════════════════════════════
 */

// 1. Timezone
if (!defined('TIMEZONE')) {
    define('TIMEZONE', 'Asia/Tehran');
}
date_default_timezone_set(TIMEZONE);

// 2. Load local environment (config.local.php or .env) if present
$localConfigPath = __DIR__ . '/config.local.php';
if (file_exists($localConfigPath)) {
    require_once $localConfigPath;
}

$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    $envLines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (is_array($envLines)) {
        foreach ($envLines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($k, $v) = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if (!getenv($k) && !isset($_ENV[$k])) {
                putenv("$k=$v");
                $_ENV[$k] = $v;
            }
        }
    }
}

// 3. Dynamic Host Detection & Tenant Auto-Resolution
$httpHost = strtolower($_SERVER['HTTP_HOST'] ?? '');
if (strpos($httpHost, ':') !== false) {
    $httpHost = explode(':', $httpHost)[0];
}

$brandKey = getenv('BRAND_KEY') ?: ($_ENV['BRAND_KEY'] ?? '');
if (empty($brandKey)) {
    if (strpos($httpHost, 'chatgolabi') !== false) {
        $brandKey = 'golabi';
    } elseif (strpos($httpHost, 'chatiranian') !== false || strpos($httpHost, 'tolou') !== false) {
        $brandKey = 'tolou';
    } else {
        $brandKey = 'golabi'; // Default fallback
    }
}

if (!defined('BRAND_KEY')) {
    define('BRAND_KEY', $brandKey);
}

// 4. Database Credentials Auto-Assignment
if (!defined('DB_HOST')) {
    define('DB_HOST', getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost'));
}

if (!defined('DB_NAME')) {
    if (getenv('DB_NAME')) {
        define('DB_NAME', getenv('DB_NAME'));
    } elseif (isset($_ENV['DB_NAME'])) {
        define('DB_NAME', $_ENV['DB_NAME']);
    } else {
        if (BRAND_KEY === 'golabi') {
            define('DB_NAME', 'mnqdvqjc_chatroom_db');
        } elseif (BRAND_KEY === 'tolou') {
            define('DB_NAME', 'jbztdjmk_chatroom_db');
        } else {
            define('DB_NAME', 'mnqdvqjc_chatroom_db');
        }
    }
}

if (!defined('DB_USER')) {
    if (getenv('DB_USER')) {
        define('DB_USER', getenv('DB_USER'));
    } elseif (isset($_ENV['DB_USER'])) {
        define('DB_USER', $_ENV['DB_USER']);
    } else {
        if (BRAND_KEY === 'golabi') {
            define('DB_USER', 'mnqdvqjc_chatuser');
        } elseif (BRAND_KEY === 'tolou') {
            define('DB_USER', 'jbztdjmk_chatroom_user');
        } else {
            define('DB_USER', 'mnqdvqjc_chatuser');
        }
    }
}

if (!defined('DB_PASS')) {
    if (getenv('DB_PASS')) {
        define('DB_PASS', getenv('DB_PASS'));
    } elseif (isset($_ENV['DB_PASS'])) {
        define('DB_PASS', $_ENV['DB_PASS']);
    } else {
        if (BRAND_KEY === 'golabi') {
            define('DB_PASS', 'Amirali.85@2026');
        } elseif (BRAND_KEY === 'tolou') {
            define('DB_PASS', 'Amirali.85');
        } else {
            define('DB_PASS', 'Amirali.85@2026');
        }
    }
}

if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
}

// 5. Uploads Directory & Dynamic URL Resolution
if (!defined('UPLOAD_DIR')) {
    $parentUploads = dirname(__DIR__) . '/uploads';
    if (is_dir($parentUploads)) {
        define('UPLOAD_DIR', $parentUploads . '/');
    } else {
        define('UPLOAD_DIR', __DIR__ . '/uploads/');
    }
}

if (!defined('UPLOAD_BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (BRAND_KEY === 'tolou' ? 'chatiranian.ir' : 'chatgolabi.ir');
    
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    if ($scriptDir === '/' || $scriptDir === '\\' || empty($scriptDir)) {
        $baseUrl = $protocol . $host . '/uploads/';
    } else {
        if (is_dir(dirname(__DIR__) . '/uploads')) {
            $baseUrl = $protocol . $host . '/uploads/';
        } else {
            $baseUrl = $protocol . $host . rtrim($scriptDir, '/\\') . '/uploads/';
        }
    }
    define('UPLOAD_BASE_URL', $baseUrl);
}

// 6. Database Connection Factory (Singleton with UTF-8 mb4)
if (!function_exists('getDB')) {
    function getDB() {
        static $pdo = null;
        if ($pdo !== null) {
            return $pdo;
        }

        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            return $pdo;
        } catch (PDOException $e) {
            sendJSON([
                'success' => false,
                'message' => 'Database connection failed: ' . $e->getMessage()
            ]);
        }
    }
}

// 7. Core HTTP / JSON Helpers
if (!function_exists('sendJSON')) {
    function sendJSON($data) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        }
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('getInput')) {
    function getInput() {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        if ($data === null || !is_array($data)) {
            return $_POST;
        }
        return array_merge($_POST, $data);
    }
}

if (!function_exists('requirePost')) {
    function requirePost() {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            sendJSON(['success' => true]);
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            sendJSON([
                'success' => false,
                'message' => 'Only POST method allowed'
            ]);
        }
    }
}

// 8. Brand-Aware Banner Path Resolver
if (!function_exists('getBannerFilePath')) {
    function getBannerFilePath() {
        $brand = defined('BRAND_KEY') ? BRAND_KEY : 'default';
        $brandFile = __DIR__ . '/banner_' . $brand . '.json';
        if (file_exists($brandFile)) {
            return $brandFile;
        }
        return __DIR__ . '/banner.json';
    }
}
