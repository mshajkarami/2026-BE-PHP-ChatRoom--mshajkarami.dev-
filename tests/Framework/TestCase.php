<?php
namespace Tests\Framework;

class AssertionException extends \Exception {}

abstract class TestCase {
    private int $assertionCount = 0;

    public function setUp(): void {}
    public function tearDown(): void {}

    public function getAssertionCount(): int {
        return $this->assertionCount;
    }

    public function resetAssertionCount(): void {
        $this->assertionCount = 0;
    }

    protected function assertEquals($expected, $actual, string $message = ''): void {
        $this->assertionCount++;
        if ($expected != $actual) {
            $msg = $message ?: "Failed asserting that " . json_encode($actual) . " matches expected " . json_encode($expected);
            throw new AssertionException($msg);
        }
    }

    protected function assertSame($expected, $actual, string $message = ''): void {
        $this->assertionCount++;
        if ($expected !== $actual) {
            $msg = $message ?: "Failed asserting that " . json_encode($actual) . " is strictly identical to " . json_encode($expected);
            throw new AssertionException($msg);
        }
    }

    protected function assertNotEquals($expected, $actual, string $message = ''): void {
        $this->assertionCount++;
        if ($expected == $actual) {
            $msg = $message ?: "Failed asserting that " . json_encode($actual) . " is not equal to " . json_encode($expected);
            throw new AssertionException($msg);
        }
    }

    protected function assertTrue($condition, string $message = ''): void {
        $this->assertionCount++;
        if ($condition !== true) {
            $msg = $message ?: "Failed asserting that value is true";
            throw new AssertionException($msg);
        }
    }

    protected function assertFalse($condition, string $message = ''): void {
        $this->assertionCount++;
        if ($condition !== false) {
            $msg = $message ?: "Failed asserting that value is false";
            throw new AssertionException($msg);
        }
    }

    protected function assertNull($actual, string $message = ''): void {
        $this->assertionCount++;
        if ($actual !== null) {
            $msg = $message ?: "Failed asserting that value is null";
            throw new AssertionException($msg);
        }
    }

    protected function assertNotNull($actual, string $message = ''): void {
        $this->assertionCount++;
        if ($actual === null) {
            $msg = $message ?: "Failed asserting that value is not null";
            throw new AssertionException($msg);
        }
    }

    protected function assertArrayHasKey($key, array $array, string $message = ''): void {
        $this->assertionCount++;
        if (!array_key_exists($key, $array)) {
            $msg = $message ?: "Failed asserting that array contains key '{$key}'";
            throw new AssertionException($msg);
        }
    }

    protected function assertCount(int $expectedCount, $array, string $message = ''): void {
        $this->assertionCount++;
        $actualCount = is_countable($array) ? count($array) : 0;
        if ($actualCount !== $expectedCount) {
            $msg = $message ?: "Failed asserting that count {$actualCount} matches expected {$expectedCount}";
            throw new AssertionException($msg);
        }
    }

    protected function assertContains($needle, $haystack, string $message = ''): void {
        $this->assertionCount++;
        if (is_array($haystack)) {
            if (!in_array($needle, $haystack, true)) {
                $msg = $message ?: "Failed asserting that array contains needle";
                throw new AssertionException($msg);
            }
        } elseif (is_string($haystack)) {
            if (strpos($haystack, (string)$needle) === false) {
                $msg = $message ?: "Failed asserting that string contains '{$needle}'";
                throw new AssertionException($msg);
            }
        }
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void {
        $this->assertionCount++;
        if (strpos($haystack, $needle) === false) {
            $msg = $message ?: "Failed asserting that '{$haystack}' contains '{$needle}'";
            throw new AssertionException($msg);
        }
    }

    protected function assertGreaterThan($expected, $actual, string $message = ''): void {
        $this->assertionCount++;
        if ($actual <= $expected) {
            $msg = $message ?: "Failed asserting that {$actual} is greater than {$expected}";
            throw new AssertionException($msg);
        }
    }

    protected function assertGreaterThanOrEqual($expected, $actual, string $message = ''): void {
        $this->assertionCount++;
        if ($actual < $expected) {
            $msg = $message ?: "Failed asserting that {$actual} is greater than or equal to {$expected}";
            throw new AssertionException($msg);
        }
    }

    protected function assertMatchesRegularExpression(string $pattern, string $string, string $message = ''): void {
        $this->assertionCount++;
        if (!preg_match($pattern, $string)) {
            $msg = $message ?: "Failed asserting that '{$string}' matches pattern '{$pattern}'";
            throw new AssertionException($msg);
        }
    }
}
