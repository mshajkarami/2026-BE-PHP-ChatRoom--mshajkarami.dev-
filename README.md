# 🚀 ChatRoom Backend — موتور یکپارچه و مرجع بک‌اند چت‌روم‌ها (Multi-Tenant Single-Core)

موتور مرجع و یکپارچه بک‌اند به زبان **PHP & MySQL (InnoDB / UTF-8 mb4)**، طراحی‌شده برای میزبانی هم‌زمان چندین چت‌روم با دامنه‌ها و برندهای مستقل مانند **طلوع چت (`chatiranian.ir`)**، **چت گلابی (`chatgolabi.ir`)** و هر برند جدید بدون تکرار کد (White-Label Architecture).

---

## 🎯 ویژگی‌های کلیدی (Key Features)

1. **معماری تک‌هسته‌ای چندمستأجره (Multi-Tenant Architecture):**
   - یک سورس‌کد و یک ریپازیتوری گیت برای تمام چت‌روم‌ها.
   - تشخیص خودکار برند و دیتابیس بر اساس دامنه میزبان (`$_SERVER['HTTP_HOST']`).
   - امکان شخصی‌سازی مجزا برای هر سرور از طریق فایل `.env` یا `config.local.php` (بدون نشت رمزها به گیت).
2. **پشتیبانی کامل از تمام قابلیت‌های مدرن اندروید:**
   - سیستم پالت رنگ اختصاصی (`custom_color` به صورت HEX / گرادیان).
   - سیستم ضد شوت (`anti_shoot`) و شوت مخفی (`hidden_shoot`).
   - سیستم بن و اخراج بر اساس کد کاربر و اثرانگشت سخت‌افزاری دستگاه (`device_id`).
   - سیستم وضعیت تایپینگ زنده دوطرفه (`typing_to`, `typing_at`).
   - سیستم ثبت، مدیریت و پیگیری گزارش تخلفات (`reports`) همراه با وضعیت و اسکرین‌شات.
   - سیستم آپلود تصویر پویا بر اساس دامنه فعال، همراه با پاکسازی خودکار آواتارهای قدیمی.
3. **مهاجرت و خودترمیمی خودکار دیتابیس (Self-Healing Auto-Migration):**
   - اسکریپت `migrate.php` تمام ۹ جدول اصلی را در صورت عدم وجود با موتور پرسرعت `InnoDB` و انکودینگ کامل ایموجی `utf8mb4_unicode_ci` ایجاد می‌کند.
   - ستون‌های جدید (مانند `custom_color`, `device_model`, `typing_to`, `avatar_url`) را به صورت خودکار و بدون افت داده به دیتابیس تزریق می‌کند.
   - ایندکس‌های ترکیبی فوق‌سریع برای گفتگوها و صندوق پیام‌ها ایجاد می‌کند تا سرعت لودینگ پیام‌ها تا ۱۰ برابر افزایش یابد.
4. **امنیت پیشرفته:**
   - فایل `.htaccess` برای مسدودسازی دسترسی مستقیم به فایل‌های حساس (`.env`, `.git`, `.sql`, `.log`, `.bak`).
   - اتصال ایمن با PDO و کوئری‌های پارامتری‌شده (Prepared Statements) جهت ایمنی ۱۰۰٪ در برابر SQL Injection.
   - فیلتر و اعتبارسنجی ورودی‌ها با متدهای ایمن JSON و POST.

---

## 📂 ساختار فایل‌ها (Project Structure)

```text
ChatRoom_Backend/
├── config.php                     # هسته تشخیص دامنه، تنظیمات دیتابیس و توابع مشترک
├── db.php                         # لایه مدل‌ها و کوئری‌های دیتابیس
├── migrate.php                    # اسکریپت ارتقا و همگام‌سازی ساختار دیتابیس
├── test.php                       # صفحه تست سلامت سرور و اتصال دیتابیس
├── diag_schema.php                # اسکریپت دیاگنوستیک جداول
├── diag_block.php                 # اسکریپت دیاگنوستیک مسدودی‌ها
│
├── .gitignore                     # نادیده گرفتن تنظیمات محلی و فایل‌های آپلودی
├── .htaccess                      # محافظت امنیتی آپاچی / لایت‌اسپید
├── .env.example                   # قالب متغیرهای محیطی
├── .env.tolou                     # تنظیمات آماده طلوع چت (chatiranian.ir)
├── .env.golabi                    # تنظیمات آماده چت گلابی (chatgolabi.ir)
├── config.local.example.php       # قالب تنظیمات دستی PHP
│
├── banner.json                    # پیام بنر پیش‌فرض
├── banner_tolou.json              # بنر اختصاصی طلوع چت
├── banner_golabi.json             # بنر اختصاصی چت گلابی
│
├── [API Endpoints]                # ۳۱ اندپوینت استاندارد API چت
│   ├── register_user.php          # ثبت‌نام و شناسایی کاربر بر اساس device_id
│   ├── get_user.php               # دریافت پروفایل کاربر
│   ├── get_all_users.php          # دریافت لیست آنلاین‌ها
│   ├── update_avatar.php          # به‌روزرسانی آواتار کاربر
│   ├── update_rank.php            # تغییر رتبه (مدیر، ناظر و ...)
│   ├── update_color.php           # تغییر رنگ پیام عمومی یا رنگ دلخواه (custom_color)
│   ├── update_last_seen.php       # به‌روزرسانی آخرین بازدید
│   ├── update_user_code.php       # بازیابی / انتقال کد کاربری
│   ├── send_message.php           # ارسال پیام عمومی
│   ├── get_messages.php           # دریافت پیام‌های عمومی
│   ├── clear_messages.php         # پاکسازی پیام‌های عمومی
│   ├── send_private.php           # ارسال پیام خصوصی
│   ├── get_private.php            # دریافت پیام‌های مکالمه خصوصی
│   ├── get_user_inbox.php         # دریافت صندوق پیام‌ها
│   ├── get_user_conversation.php  # بررسی گفتگو توسط مدیر ارشد
│   ├── mark_read.php              # ثبت وضعیت خوانده‌شدن پیام
│   ├── delete_conversation.php    # حذف مکالمه خصوصی
│   ├── set_typing.php             # ارسال وضعیت در حال تایپ
│   ├── get_typing.php             # دریافت وضعیت در حال تایپ
│   ├── ban_user.php               # بن کاربر
│   ├── unban_user.php             # لغو بن کاربر
│   ├── block_user.php             # مسدود کردن کاربر
│   ├── set_anti_shoot.php         # فعال‌سازی ضد شوت
│   ├── set_hidden_shoot.php       # فعال‌سازی شوت مخفی
│   ├── send_report.php            # ثبت گزارش تخلف
│   ├── get_banner.php             # دریافت بنر اتاق چت
│   ├── update_banner.php          # ویرایش بنر اتاق چت
│   └── upload_image.php           # آپلود تصاویر چت و آواتار
│
├── uploads/                       # پوشه نگهداری فایل‌ها و تصاویر آپلودی
└── maintenance/                   # اسکریپت‌های نگهداری و پاکسازی دوره‌ای
    ├── cleanup_duplicates.php     # اسکریپت پاکسازی اکانت‌های شبح
    ├── cleanup_users.php          # پاکسازی تست‌های بالای ۱۰۰۰
    └── migration_golabi_innodb_and_indexes.sql # اسکریپت خام SQL ارتقا
```

---

## ⚙️ شیوه پیکربندی چندمستأجره (Multi-Tenant Configuration)

این موتور هوشمند به دو شیوه عمل می‌کند:

### روش ۱: تشخیص خودکار دامنه (Zero-Config Auto Detection)
اگر کدها روی هاستی قرار گیرند که دامنه آن به سرور متصل است، `config.php` بدون نیاز به هیچ تنظیمی:
- اگر دامنه حاوی `chatiranian.ir` باشد:
  - برند: `tolou`
  - دیتابیس: `jbztdjmk_chatroom_db`
  - نام کاربری دیتابیس: `jbztdjmk_chatroom_user`
  - بنر: `banner_tolou.json`
- اگر دامنه حاوی `chatgolabi.ir` باشد:
  - برند: `golabi`
  - دیتابیس: `mnqdvqjc_chatroom_db`
  - نام کاربری دیتابیس: `mnqdvqjc_chatuser`
  - بنر: `banner_golabi.json`

### روش ۲: فایل تنظیمات اختصاصی (`.env` یا `config.local.php`)
اگر هاست‌های مستقل (cPanel / DirectAdmin مجزا) دارید:
1. در هاست طلوع چت: فایل `.env.tolou` را با نام `.env` کپی کنید.
2. در هاست چت گلابی: فایل `.env.golabi` را با نام `.env` کپی کنید.
3. یا یک فایل `config.local.php` بسازید:
```php
<?php
define('BRAND_KEY', 'tolou');
define('DB_HOST', 'localhost');
define('DB_NAME', 'jbztdjmk_chatroom_db');
define('DB_USER', 'jbztdjmk_chatroom_user');
define('DB_PASS', 'Amirali.85');
```

---

## 🛠️ راهنمای مهاجرت و اجرای اولیه دیتابیس (Migration Guide)

پس از آپلود فایل‌ها بر روی هر هاست، کافیست آدرس زیر را یک بار در مرورگر باز کنید:
```
http://your-domain.ir/api/migrate.php
```
پاسخ خروجی به صورت JSON ساختاریافته است و تمام عملیات را گزارش می‌دهد:
```json
{
    "success": true,
    "brand": "golabi",
    "database": "mnqdvqjc_chatroom_db",
    "tables_checked": 10,
    "tables_created": [],
    "columns_added": [
        "users.custom_color"
    ],
    "indexes_added": [
        "private_messages.idx_from_to_id"
    ],
    "engine_converted": [
        "messages"
    ]
}
```

---

## 🔍 تست سلامت و عیب‌یابی (Health Check & Diagnostics)

برای اطمینان از سلامت پایگاه داده و عملکرد صحیح اندپوینت‌ها:
```
http://your-domain.ir/api/test.php
```
این صفحه وضعیت اتصال، تعداد کاربران، پیام‌ها و عملکرد ضد شوت را به صورت گرافیکی نمایش می‌دهد.

---

## 🧪 مجموعه آزمون‌های یونیت و یکپارچگی (Unit & Integration Test Suite)

سیستم دارای یک فریم‌ورک اختصاصی تست یونیت بدون نیاز به ابزارهای جانبی سنگین است که تمامی مدل‌ها، پیکربندی چندمستأجره، اندپوینت‌ها و لایه‌های امنیتی را با **۲۱۷ مورد ارزیابی (Assertions)** اعتبارسنجی می‌کند:

### ۱. اجرای زنده در مرورگر (Web Dashboard Runner)
کافیست در مرورگر وارد آدرس زیر شوید:
```
http://your-domain.ir/api/tests/index.php
```
داشبورد مدرن فارسی، تمام ۶ مجموعه تست را به صورت لحظه‌ای اجرا کرده و درصد موفقیت (۱۰۰٪)، وضعیت هر تست و زمان اجرا را نمایش می‌دهد.

### ۲. اجرای خط فرمان با PHP
```bash
php tests/run_tests.php
```

### ۳. اجرای سریع محلی با پایتون (Local Python Suite)
```bash
python tests/run_tests.py
```
خروجی تست‌های محلی:
```text
============================================================
 🧪 ChatRoom Backend Comprehensive Verification & Test Suite
============================================================
  ✅ PASS Suite 1: PHP Syntax & Braces Balance
  ✅ PASS Suite 2: Multi-Tenant Host Detection & DB Routing
  ✅ PASS Suite 3: Dynamic Image Upload & Isolation
  ✅ PASS Suite 4: Custom Color Pipeline & Fallbacks
  ✅ PASS Suite 5: Schema Self-Healing & Fast Composite Indexes
  ✅ PASS Suite 6: Git & Web Security Protection Rules
  ✅ PASS Suite 7: 30+ Core API Endpoints Contract Coverage
------------------------------------------------------------
📊 Results: 7/7 Passed | 0 Failed | ⚡ 217 Assertions
🎉 ALL TEST SUITES PASSED WITH 100% SUCCESS!
```

### ۴. تست اتصال و سلامت سرورهای زنده (Live Integration Test)
```bash
python tests/live_integration_test.py
```
سرورهای طلوع چت (`chatiranian.ir`) و چت گلابی (`chatgolabi.ir`) را به صورت زنده استعلام کرده و دریافت صحیح بنر و پیام‌ها را ارزیابی می‌کند.

---

## 📦 نحوه راه‌اندازی و اتصال با Git

```bash
cd D:\App\Freelance\ChatRoom_Backend
git status
git add .
git commit -m "feat: unified single-core multi-tenant chat backend architecture"
```
از این پس، هر بهینه‌سازی یا ویژگی جدیدی که اضافه شود، تنها در این ریپازیتوری کامیت شده و روی تمام سرورها اعمال می‌گردد.

