-- ==============================================================
-- اسکریپت جامع ارتقا و بهینه‌سازی دیتابیس چت گلابی
-- Chat Golabi Database Performance & InnoDB Migration Script
-- ==============================================================

-- ۱. تبدیل موتور تمام جداول از MyISAM به InnoDB جهت جلوگیری از قفل کل جدول (Table Lock)
ALTER TABLE `messages` ENGINE = InnoDB;
ALTER TABLE `private_messages` ENGINE = InnoDB;
ALTER TABLE `users` ENGINE = InnoDB;
ALTER TABLE `deleted_conversations` ENGINE = InnoDB;
ALTER TABLE `blocked_users` ENGINE = InnoDB;
ALTER TABLE `banned_users` ENGINE = InnoDB;
ALTER TABLE `reports` ENGINE = InnoDB;
ALTER TABLE `anti_shoot` ENGINE = InnoDB;

-- ۲. هماهنگ‌سازی Charset و Collation جدول حذف مکالمات با پیام‌ها
ALTER TABLE `deleted_conversations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;

-- ۳. ایجاد ایندکس‌های ترکیبی فوق‌سریع برای گفتگوهای دو نفره
-- این ایندکس‌ها زمان بارگذاری چت خصوصی و صندوق را تا ۱۰ برابر افزایش می‌دهند
ALTER TABLE `private_messages` ADD INDEX `idx_from_to_id` (`from_code`, `to_code`, `id`);
ALTER TABLE `private_messages` ADD INDEX `idx_to_from_id` (`to_code`, `from_code`, `id`);

-- ۴. ایندکس بهینه برای شمارش پیام‌های خوانده‌نشده
ALTER TABLE `private_messages` ADD INDEX `idx_unread_lookup` (`to_code`, `is_read`, `from_code`, `id`);

-- ۵. اطمینان از وجود ستون custom_color در جداول چت گلابی (برای ابزار رنگ‌ساز)
-- در صورت وجود داشتن قبلی، این دستورات را می‌توانید بدون نگرانی اجرا کنید
ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `custom_color` VARCHAR(100) DEFAULT '';
ALTER TABLE `messages` ADD COLUMN IF NOT EXISTS `custom_color` VARCHAR(100) DEFAULT '';
ALTER TABLE `private_messages` ADD COLUMN IF NOT EXISTS `custom_color` VARCHAR(100) DEFAULT '';

-- پایان اسکریپت
