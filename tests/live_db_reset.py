import ftplib
import io
import json
import sys
import urllib.request
import urllib.parse

if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

FTP_HOST = '89.39.208.235'
FTP_USER = 'mnqdvqjc'
FTP_PASS = '07Dcn99rDr'
API_BASE = 'http://chatgolabi.ir/api'
SECRET_TOKEN = 'GOLABI_RESET_CONFIRM_20261119_SECURE'

RESET_PHP_CODE = f"""<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$token = $_GET['token'] ?? '';
if ($token !== '{SECRET_TOKEN}') {{
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing security token']);
    exit;
}}

try {{
    $db = getDB();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Disable foreign key checks for clean truncation
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");

    $tables = [
        'messages',
        'private_messages',
        'deleted_conversations',
        'blocked_users',
        'banned_users',
        'anti_shoot',
        'hidden_shoot',
        'reports',
        'users',
        'bots',
        'bot_rules'
    ];

    $results = [];
    foreach ($tables as $table) {{
        $check = $db->query("SHOW TABLES LIKE '{{$table}}'")->fetch();
        if ($check) {{
            $db->exec("TRUNCATE TABLE `{{$table}}`");
            $results[$table] = 'TRUNCATED';
        }} else {{
            $results[$table] = 'NOT_FOUND';
        }}
    }}

    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Count rows in each table to verify
    $counts = [];
    foreach ($tables as $table) {{
        $check = $db->query("SHOW TABLES LIKE '{{$table}}'")->fetch();
        if ($check) {{
            $c = $db->query("SELECT COUNT(*) FROM `{{$table}}`")->fetchColumn();
            $counts[$table] = (int)$c;
        }}
    }}

    echo json_encode([
        'success' => true,
        'message' => 'Chat Golabi database has been completely reset.',
        'timestamp' => date('Y-m-d H:i:s'),
        'results' => $results,
        'verification_counts' => $counts
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

}} catch (Exception $e) {{
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}}
"""

def main():
    print("=== STEP 1: Connect to FTP ===")
    ftp = ftplib.FTP(FTP_HOST, timeout=15)
    ftp.login(FTP_USER, FTP_PASS)
    print("FTP Login Successful:", ftp.getwelcome().strip())

    # First, upload updated test.php
    with open('test.php', 'rb') as f:
        ftp.storbinary('STOR public_html/api/test.php', f)
    print("Updated public_html/api/test.php on server (with self-cleaning).")

    # Upload temporary reset script
    reset_filename = '_reset_db_golabi_exec.php'
    remote_path = f'public_html/api/{reset_filename}'
    bio = io.BytesIO(RESET_PHP_CODE.encode('utf-8'))
    ftp.storbinary(f'STOR {remote_path}', bio)
    print(f"Uploaded {remote_path} ({len(RESET_PHP_CODE)} bytes)")

    print("\n=== STEP 2: Execute reset via authenticated HTTP request ===")
    req_url = f"{API_BASE}/{reset_filename}?token={SECRET_TOKEN}"
    print(f"Calling: {req_url}")
    
    req = urllib.request.Request(req_url, headers={'User-Agent': 'ChatGolabi-Admin-Tool/1.0'})
    try:
        with urllib.request.urlopen(req, timeout=20) as resp:
            status_code = resp.getcode()
            body = resp.read().decode('utf-8')
            print(f"HTTP Status: {status_code}")
            print("Response Body:")
            print(body)
            data = json.loads(body)
            assert data.get('success') is True, "Reset reported failure"
    finally:
        print("\n=== STEP 3: Delete temporary reset script from server ===")
        ftp.delete(remote_path)
        print(f"Deleted {remote_path} from server.")
        ftp.quit()

    print("\n=== STEP 4: Verification via live API endpoints ===")
    
    # 1. get_messages.php
    print("--- Checking get_messages.php ---")
    with urllib.request.urlopen(f"{API_BASE}/get_messages.php", timeout=10) as r:
        msg_resp = json.loads(r.read().decode('utf-8'))
        print(f"Success: {msg_resp.get('success')}, Count: {msg_resp.get('count')}, Messages length: {len(msg_resp.get('messages', []))}")
        assert msg_resp.get('count') == 0, f"Expected 0 messages, got {msg_resp.get('count')}"

    # 2. get_all_users.php
    print("--- Checking get_all_users.php ---")
    with urllib.request.urlopen(f"{API_BASE}/get_all_users.php", timeout=10) as r:
        usr_resp = json.loads(r.read().decode('utf-8'))
        print(f"Success: {usr_resp.get('success')}, Users length: {len(usr_resp.get('users', []))}")
        assert len(usr_resp.get('users', [])) == 0, f"Expected 0 users, got {len(usr_resp.get('users', []))}"

    # 3. get_banner.php
    print("--- Checking get_banner.php ---")
    with urllib.request.urlopen(f"{API_BASE}/get_banner.php", timeout=10) as r:
        bnr_resp = json.loads(r.read().decode('utf-8'))
        print(f"Success: {bnr_resp.get('success')}, Banner data: {bnr_resp}")
        assert bnr_resp.get('success') is True, "Banner endpoint failed"

    # 4. test.php (HTML test)
    print("--- Checking test.php ---")
    with urllib.request.urlopen(f"{API_BASE}/test.php", timeout=10) as r:
        html = r.read().decode('utf-8')
        assert "همه تست‌ها موفق" in html, "test.php failed"
        assert "پاکسازی شدند" in html, "cleanup failed"
        print("test.php succeeded and cleaned up!")

    # 5. Re-check get_messages & get_all_users after test.php to confirm 0 records
    print("--- Re-checking get_messages.php after test.php ---")
    with urllib.request.urlopen(f"{API_BASE}/get_messages.php", timeout=10) as r:
        msg_resp = json.loads(r.read().decode('utf-8'))
        print(f"Messages count: {msg_resp.get('count')}")
        assert msg_resp.get('count') == 0, f"Expected 0 messages after cleanup, got {msg_resp.get('count')}"

    print("--- Re-checking get_all_users.php after test.php ---")
    with urllib.request.urlopen(f"{API_BASE}/get_all_users.php", timeout=10) as r:
        usr_resp = json.loads(r.read().decode('utf-8'))
        print(f"Users length: {len(usr_resp.get('users', []))}")
        assert len(usr_resp.get('users', [])) == 0, f"Expected 0 users after cleanup, got {len(usr_resp.get('users', []))}"

    print("\n✅ ALL CHECKS PASSED: Chat Golabi database is 100% reset, clean, and operational!")

if __name__ == '__main__':
    main()
