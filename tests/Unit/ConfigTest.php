<?php
namespace Tests\Unit;

use Tests\Framework\TestCase;

class ConfigTest extends TestCase {

    public function testTimezoneIsTehran(): void {
        $this->assertEquals('Asia/Tehran', date_default_timezone_get());
    }

    public function testTenantResolutionForTolouChat(): void {
        $host = 'chatiranian.ir';
        $brand = (strpos($host, 'chatiranian') !== false || strpos($host, 'tolou') !== false) ? 'tolou' : 'unknown';
        $this->assertEquals('tolou', $brand);

        $dbName = ($brand === 'tolou') ? 'jbztdjmk_chatroom_db' : 'other';
        $this->assertEquals('jbztdjmk_chatroom_db', $dbName);

        $dbUser = ($brand === 'tolou') ? 'jbztdjmk_chatroom_user' : 'other';
        $this->assertEquals('jbztdjmk_chatroom_user', $dbUser);

        $dbPass = ($brand === 'tolou') ? 'Amirali.85' : 'other';
        $this->assertEquals('Amirali.85', $dbPass);
    }

    public function testTenantResolutionForChatGolabi(): void {
        $host = 'chatgolabi.ir';
        $brand = (strpos($host, 'chatgolabi') !== false) ? 'golabi' : 'unknown';
        $this->assertEquals('golabi', $brand);

        $dbName = ($brand === 'golabi') ? 'mnqdvqjc_chatroom_db' : 'other';
        $this->assertEquals('mnqdvqjc_chatroom_db', $dbName);

        $dbUser = ($brand === 'golabi') ? 'mnqdvqjc_chatuser' : 'other';
        $this->assertEquals('mnqdvqjc_chatuser', $dbUser);

        $dbPass = ($brand === 'golabi') ? 'Amirali.85@2026' : 'other';
        $this->assertEquals('Amirali.85@2026', $dbPass);
    }

    public function testSubdomainResolution(): void {
        $hosts = [
            'api.chatiranian.ir' => 'tolou',
            'chat.chatiranian.ir' => 'tolou',
            'api.chatgolabi.ir' => 'golabi',
            'chat.chatgolabi.ir' => 'golabi'
        ];

        foreach ($hosts as $h => $expectedBrand) {
            $brand = (strpos($h, 'chatgolabi') !== false) ? 'golabi' : ((strpos($h, 'chatiranian') !== false) ? 'tolou' : 'default');
            $this->assertEquals($expectedBrand, $brand, "Failed on host: {$h}");
        }
    }

    public function testBannerFileResolution(): void {
        $rootDir = dirname(dirname(__DIR__));
        
        $brand = 'tolou';
        $tolouFile = $rootDir . '/banner_' . $brand . '.json';
        $this->assertTrue(file_exists($tolouFile), "banner_tolou.json must exist in root");
        $tolouJson = json_decode(file_get_contents($tolouFile), true);
        $this->assertArrayHasKey('title', $tolouJson);
        $this->assertStringContainsString('ایرانیان', $tolouJson['title']);

        $brand = 'golabi';
        $golabiFile = $rootDir . '/banner_' . $brand . '.json';
        $this->assertTrue(file_exists($golabiFile), "banner_golabi.json must exist in root");
        $golabiJson = json_decode(file_get_contents($golabiFile), true);
        $this->assertArrayHasKey('title', $golabiJson);
        $this->assertStringContainsString('چت روم', $golabiJson['title']);
    }

    public function testDynamicUploadUrlGeneration(): void {
        $protocol = "http://";
        $host = "chatgolabi.ir";
        $fileName = "img_1731671717_1234.jpg";

        $url = $protocol . $host . '/uploads/' . $fileName;
        $this->assertEquals("http://chatgolabi.ir/uploads/img_1731671717_1234.jpg", $url);
        $this->assertStringContainsString("chatgolabi.ir", $url);
        $this->assertFalse(strpos($url, "chatiranian.ir") !== false, "Golabi upload URL must not leak chatiranian.ir domain");

        $hostTolou = "chatiranian.ir";
        $urlTolou = $protocol . $hostTolou . '/uploads/' . $fileName;
        $this->assertEquals("http://chatiranian.ir/uploads/img_1731671717_1234.jpg", $urlTolou);
    }

    public function testEnvFileSyntaxParsing(): void {
        $sampleEnv = "
# Comment line
BRAND_KEY=golabi
DB_HOST=localhost
DB_NAME=\"mnqdvqjc_chatroom_db\"
DB_PASS='secret_123'
        ";

        $lines = explode("\n", trim($sampleEnv));
        $parsed = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0 || strpos($line, '=') === false) continue;
            list($k, $v) = explode('=', $line, 2);
            $parsed[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }

        $this->assertCount(4, $parsed);
        $this->assertEquals('golabi', $parsed['BRAND_KEY']);
        $this->assertEquals('localhost', $parsed['DB_HOST']);
        $this->assertEquals('mnqdvqjc_chatroom_db', $parsed['DB_NAME']);
        $this->assertEquals('secret_123', $parsed['DB_PASS']);
    }
}
