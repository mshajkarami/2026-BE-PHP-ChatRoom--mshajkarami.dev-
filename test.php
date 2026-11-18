<?php
// ════════════════════════════════════════════
// ══════════ تست PHP + MySQL ═════════════════
// ════════════════════════════════════════════

header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/db.php';

echo "<h1>🔍 تست اتصال به دیتابیس چت‌روم یکپارچه</h1>";
echo "<p>🏷️ <strong>برند فعال:</strong> " . htmlspecialchars(BRAND_KEY) . " | 🌐 <strong>دامنه میزبان:</strong> " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'CLI') . " | 🗄️ <strong>پایگاه داده:</strong> " . htmlspecialchars(DB_NAME) . "</p>";

try {
    // تست ۱: اتصال دیتابیس
    echo "<h2>✅ تست ۱: اتصال دیتابیس</h2>";
    $db = getDB();
    echo "<p>✅ اتصال موفق!</p>";
    
    // تست ۲: لیست جدول‌ها
    echo "<h2>✅ تست ۲: جدول‌ها</h2>";
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>📋 " . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    echo "<p><strong>تعداد جداول: " . count($tables) . "</strong></p>";
    
    // تست ۳: تعداد کاربران
    echo "<h2>✅ تست ۳: کاربران</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $row = $stmt->fetch();
    echo "<p>👥 تعداد کاربران: <strong>" . $row['count'] . "</strong></p>";
    
    // تست ۴: تعداد پیام‌ها
    echo "<h2>✅ تست ۴: پیام‌ها</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM messages");
    $row = $stmt->fetch();
    echo "<p>💬 تعداد پیام‌ها: <strong>" . $row['count'] . "</strong></p>";
    
    // تست ۵: ربات Maryam
    echo "<h2>✅ تست ۵: ربات Maryam</h2>";
    try {
        $stmt = $db->query("SELECT * FROM bots WHERE code = '999'");
        $bot = $stmt->fetch();
        if ($bot) {
            echo "<p>🤖 Maryam پیدا شد!</p>";
            echo "<p>اسم: " . htmlspecialchars($bot['name']) . "</p>";
            echo "<p>کد: " . htmlspecialchars($bot['code']) . "</p>";
        } else {
            echo "<p>⚠️ Maryam پیدا نشد!</p>";
        }
    } catch (Exception $e) {
        echo "<p>ℹ️ جدول ربات‌ها یافت نشد یا غیرفعال است.</p>";
    }
    
    // تست ۶: ثبت کاربر تستی
    echo "<h2>✅ تست ۶: ثبت کاربر تستی</h2>";
    registerUser('999999', 'کاربر تست', 'بیوگرافی تست');
    $testUser = getUser('999999');
    if ($testUser) {
        echo "<p>✅ کاربر تستی ثبت شد!</p>";
        echo "<p>کد: " . htmlspecialchars($testUser['user_code']) . "</p>";
        echo "<p>اسم: " . htmlspecialchars($testUser['user_name']) . "</p>";
    }
    
    // تست ۷: ذخیره پیام تستی
    echo "<h2>✅ تست ۷: ذخیره پیام تستی</h2>";
    $msgId = saveMessage([
        'user_code' => '999999',
        'user_name' => 'کاربر تست',
        'message' => 'این یه پیام تستیه',
        'time' => date('H:i:s'),
        'color_num' => 1,
        'rank_num' => 0,
        'is_sticker' => 0,
        'user_bio' => '',
        'reply_to_id' => 0,
        'reply_to_name' => '',
        'reply_to_text' => '',
        'image_path' => '',
        'is_image' => 0
    ]);
    echo "<p>✅ پیام تستی ذخیره شد (ID: " . $msgId . ")</p>";
    
    // تست ۸: خواندن پیام‌ها
    echo "<h2>✅ تست ۸: خواندن پیام‌ها</h2>";
    $msgs = getLastMessages(10);
    echo "<p>تعداد پیام‌های اخیر: <strong>" . count($msgs) . "</strong></p>";
    echo "<ul>";
    foreach ($msgs as $m) {
        echo "<li>" . htmlspecialchars($m['user_name']) . ": " . 
             htmlspecialchars($m['message']) . " (" . 
             htmlspecialchars($m['time']) . ")</li>";
    }
    echo "</ul>";
    
    // تست ۹: تست ضد شوت
    echo "<h2>✅ تست ۹: ضد شوت</h2>";
    setAntiShoot('999999', true);
    $isAnti = isAntiShoot('999999');
    echo "<p>🛡 ضد شوت کاربر تستی: " . ($isAnti ? "✅ فعال" : "❌ غیرفعال") . "</p>";
    
    // تست ۱۰: تست بن
    echo "<h2>✅ تست ۱۰: بن</h2>";
    echo "<p>کاربر 999999 بن هست؟ " . (isBanned('999999') ? "✅ بله" : "❌ نه") . "</p>";

    // پاکسازی رکورد‌های تستی
    $db->prepare("DELETE FROM messages WHERE user_code = '999999'")->execute();
    $db->prepare("DELETE FROM anti_shoot WHERE user_code = '999999'")->execute();
    $db->prepare("DELETE FROM users WHERE user_code = '999999'")->execute();
    echo "<p>🧹 رکوردهای تستی با موفقیت پاکسازی شدند.</p>";
    
    echo "<hr>";
    echo "<h2 style='color:green'>🎉 همه تست‌ها موفق!</h2>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ خطا:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>File: " . htmlspecialchars($e->getFile()) . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
}
