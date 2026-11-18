import ftplib
import io
import json
import sys
import urllib.request

if sys.platform == 'win32':
    sys.stdout.reconfigure(encoding='utf-8')

php_code = """<?php
require_once __DIR__ . '/db.php';
try {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO users (user_code, user_name, user_bio, rank_num, color_num, custom_color, is_owner, last_seen, created_at)
        VALUES ('1', 'امیرعلی صاعدی', 'مالک چت گلابی 🍐', 200, 50, '#8bc34a', 1, UNIX_TIMESTAMP()*1000, UNIX_TIMESTAMP()*1000)
        ON DUPLICATE KEY UPDATE is_owner = 1, rank_num = 200, user_name = 'امیرعلی صاعدی'");
    $stmt->execute();
    
    $stmt2 = $db->prepare("INSERT INTO anti_shoot (user_code, is_active) VALUES ('1', 1) ON DUPLICATE KEY UPDATE is_active = 1");
    $stmt2->execute();
    
    echo json_encode(['success' => true, 'user' => getUser('1')]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
"""

ftp = ftplib.FTP('89.39.208.235', timeout=15)
ftp.login('mnqdvqjc', '07Dcn99rDr')
ftp.storbinary('STOR public_html/api/_seed_owner.php', io.BytesIO(php_code.encode('utf-8')))

req = urllib.request.Request('http://chatgolabi.ir/api/_seed_owner.php', headers={'User-Agent': 'Admin'})
with urllib.request.urlopen(req) as r:
    print(r.read().decode('utf-8'))

ftp.delete('public_html/api/_seed_owner.php')
ftp.quit()
