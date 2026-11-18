<?php
/**
 * ═════════════════════════════════════════════════════════════════════
 * ChatRoom Backend CLI Unit Test Runner
 * اسکریپت اجرای تست‌های یونیت در محیط خط فرمان
 * ═════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/Framework/TestCase.php';
require_once __DIR__ . '/Framework/DatabaseHarness.php';
require_once __DIR__ . '/Framework/TestRunner.php';

use Tests\Framework\TestRunner;

$runner = new TestRunner();
$unitDir = __DIR__ . '/Unit';

$report = $runner->runAll($unitDir);
$exitCode = $runner->printCliReport($report);

exit($exitCode);
