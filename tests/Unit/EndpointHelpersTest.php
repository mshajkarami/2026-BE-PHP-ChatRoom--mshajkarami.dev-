<?php
namespace Tests\Unit;

use Tests\Framework\TestCase;

class EndpointHelpersTest extends TestCase {

    public function testJsonEncodingDoesNotEscapePersianUnicode(): void {
        $payload = [
            'success' => true,
            'message' => 'پیام با حروف فارسی و اموجی 🚀',
            'user' => [
                'name' => 'علیرضا',
                'bio' => 'بیوگرافی فارسی'
            ]
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        
        $this->assertStringContainsString('پیام با حروف فارسی', $json);
        $this->assertStringContainsString('علیرضا', $json);
        $this->assertStringContainsString('🚀', $json);
        $this->assertFalse(strpos($json, '\u06') !== false, "Unicode must not be escaped into backslash sequences");
    }

    public function testInputParserHandlesBothJsonAndPost(): void {
        $postData = ['action' => 'login', 'name' => 'User1'];
        $rawJson = '{"user_code":"144","device_id":"abc-123"}';

        $decoded = json_decode($rawJson, true);
        $this->assertNotNull($decoded);

        $merged = array_merge($postData, $decoded);
        $this->assertEquals('login', $merged['action']);
        $this->assertEquals('144', $merged['user_code']);
        $this->assertEquals('abc-123', $merged['device_id']);
    }

    public function testBannerLeakPreventionInUserBio(): void {
        // اگر قبلاً باگ بنر در بیوگرافی کاربر ذخیره شده باشد، باید فیلتر شود
        $leakedBio1 = "BANNER_B64:eyJ0ZXh0Ijoi2KjZhtmNIn0=";
        $leakedBio2 = "BANNER:قوانین اتاق چت";
        $cleanBio = "سلام این بیوگرافی عادی من است";

        $filter = function($bio) {
            if (strpos($bio, 'BANNER_B64:') === 0 || strpos($bio, 'BANNER:') === 0) {
                return '';
            }
            return $bio;
        };

        $this->assertEquals('', $filter($leakedBio1), "Base64 banner leaked in bio must be sanitized to empty");
        $this->assertEquals('', $filter($leakedBio2), "Plain banner leaked in bio must be sanitized to empty");
        $this->assertEquals($cleanBio, $filter($cleanBio), "Clean user bio must be preserved");
    }

    public function testRankRangeValidation(): void {
        $validateRank = function($rank) {
            $r = (int)$rank;
            return ($r >= 0 && $r <= 200);
        };

        $this->assertTrue($validateRank(0));
        $this->assertTrue($validateRank(50));
        $this->assertTrue($validateRank(100)); // مدیر کل
        $this->assertTrue($validateRank(200)); // حداکثر رتبه
        $this->assertFalse($validateRank(-1));
        $this->assertFalse($validateRank(201));
    }

    public function testColorRangeValidation(): void {
        $validateColor = function($color) {
            $c = (int)$color;
            return ($c >= 0 && $c <= 50);
        };

        $this->assertTrue($validateColor(0));
        $this->assertTrue($validateColor(25));
        $this->assertTrue($validateColor(50));
        $this->assertFalse($validateColor(-1));
        $this->assertFalse($validateColor(51));
    }

    public function testHexAndGradientCustomColorValidation(): void {
        $isValidCustomColor = function($color) {
            $c = trim($color);
            if (empty($c)) return true;
            // بررسی الگوهای معتبر: Hex 3/6/8 رقمی یا linear-gradient
            if (preg_match('/^#([a-fA-F0-9]{3}|[a-fA-F0-9]{6}|[a-fA-F0-9]{8})$/', $c)) return true;
            if (strpos($c, 'linear-gradient') === 0 || strpos($c, 'radial-gradient') === 0) return true;
            return false;
        };

        $this->assertTrue($isValidCustomColor('#FFF'));
        $this->assertTrue($isValidCustomColor('#E91E63'));
        $this->assertTrue($isValidCustomColor('#FF5722FF'));
        $this->assertTrue($isValidCustomColor('linear-gradient(45deg, #f06, #4a90e2)'));
        $this->assertFalse($isValidCustomColor('javascript:alert(1)'));
        $this->assertFalse($isValidCustomColor('<script>'));
        $this->assertFalse($isValidCustomColor('#GGG'));
    }
}
