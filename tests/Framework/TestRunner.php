<?php
namespace Tests\Framework;

class TestRunner {
    private array $results = [];
    private int $totalAssertions = 0;
    private int $passedTests = 0;
    private int $failedTests = 0;
    private int $errorTests = 0;
    private float $startTime = 0.0;
    private float $totalTime = 0.0;

    public function runAll(string $unitDir): array {
        $this->startTime = microtime(true);
        $this->results = [];
        $this->totalAssertions = 0;
        $this->passedTests = 0;
        $this->failedTests = 0;
        $this->errorTests = 0;

        $files = glob(rtrim($unitDir, '/\\') . '/*Test.php');
        foreach ($files as $file) {
            require_once $file;
            $className = 'Tests\\Unit\\' . basename($file, '.php');
            if (class_exists($className)) {
                $this->runClass($className);
            }
        }

        $this->totalTime = microtime(true) - $this->startTime;

        return [
            'total_tests' => $this->passedTests + $this->failedTests + $this->errorTests,
            'passed' => $this->passedTests,
            'failed' => $this->failedTests,
            'errors' => $this->errorTests,
            'total_assertions' => $this->totalAssertions,
            'time' => round($this->totalTime, 4),
            'suites' => $this->results
        ];
    }

    private function runClass(string $className): void {
        $reflection = new \ReflectionClass($className);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);
        $shortName = $reflection->getShortName();

        $this->results[$shortName] = [
            'class' => $shortName,
            'tests' => []
        ];

        foreach ($methods as $method) {
            if (strpos($method->getName(), 'test') === 0) {
                $testName = $method->getName();
                /** @var TestCase $instance */
                $instance = new $className();
                $instance->resetAssertionCount();

                $status = 'PASSED';
                $errorMsg = null;
                $t0 = microtime(true);

                try {
                    $instance->setUp();
                    $instance->$testName();
                    $instance->tearDown();
                    $this->passedTests++;
                } catch (AssertionException $e) {
                    $status = 'FAILED';
                    $errorMsg = $e->getMessage();
                    $this->failedTests++;
                } catch (\Throwable $e) {
                    $status = 'ERROR';
                    $errorMsg = $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine();
                    $this->errorTests++;
                }

                $duration = round(microtime(true) - $t0, 4);
                $assertions = $instance->getAssertionCount();
                $this->totalAssertions += $assertions;

                $this->results[$shortName]['tests'][] = [
                    'name' => $testName,
                    'status' => $status,
                    'assertions' => $assertions,
                    'time' => $duration,
                    'error' => $errorMsg
                ];
            }
        }
    }

    public function printCliReport(array $report): int {
        echo PHP_EOL . "==========================================================" . PHP_EOL;
        echo " 🧪 ChatRoom Unified Backend — Unit Test Suite Runner" . PHP_EOL;
        echo "==========================================================" . PHP_EOL . PHP_EOL;

        foreach ($report['suites'] as $suiteName => $suite) {
            echo "📦 Suite: {$suiteName}" . PHP_EOL;
            foreach ($suite['tests'] as $test) {
                $icon = $test['status'] === 'PASSED' ? "✅ PASS" : ($test['status'] === 'FAILED' ? "❌ FAIL" : "⚠️ ERR");
                echo "   {$icon} {$test['name']} ({$test['assertions']} assertions, {$test['time']}s)" . PHP_EOL;
                if (!empty($test['error'])) {
                    echo "      └── 🔴 Error: {$test['error']}" . PHP_EOL;
                }
            }
            echo PHP_EOL;
        }

        echo "----------------------------------------------------------" . PHP_EOL;
        echo "📊 Total Tests: {$report['total_tests']} | Passed: {$report['passed']} | Failed: {$report['failed']} | Errors: {$report['errors']}" . PHP_EOL;
        echo "⚡ Total Assertions: {$report['total_assertions']} | Time: {$report['time']}s" . PHP_EOL;
        echo "==========================================================" . PHP_EOL;

        if ($report['failed'] === 0 && $report['errors'] === 0) {
            echo "🎉 ALL TESTS PASSED SUCCESSFULLY! (100% OK)" . PHP_EOL . PHP_EOL;
            return 0;
        } else {
            echo "❌ SOME TESTS FAILED!" . PHP_EOL . PHP_EOL;
            return 1;
        }
    }
}
