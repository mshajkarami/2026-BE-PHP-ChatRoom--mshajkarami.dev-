<?php
/**
 * ═════════════════════════════════════════════════════════════════════
 * ChatRoom Backend Web Unit Test Runner & Dashboard
 * داشبورد مرورگری اجرای زنده تست‌های یونیت بک‌اند چت‌روم
 * ═════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/Framework/TestCase.php';
require_once __DIR__ . '/Framework/DatabaseHarness.php';
require_once __DIR__ . '/Framework/TestRunner.php';

use Tests\Framework\TestRunner;

$runner = new TestRunner();
$unitDir = __DIR__ . '/Unit';
$report = $runner->runAll($unitDir);

$allPassed = ($report['failed'] === 0 && $report['errors'] === 0);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>داشبورد تست‌های یونیت چت‌روم یکپارچه</title>
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --primary: #6366f1;
            --border: #334155;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, -apple-system, 'Segoe UI', Tahoma, sans-serif; }
        body { background-color: var(--bg-color); color: var(--text-main); padding: 24px; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; }
        header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--border); }
        h1 { font-size: 1.6rem; color: #fff; }
        .badge { padding: 6px 14px; border-radius: 9999px; font-weight: 600; font-size: 0.9rem; }
        .badge-success { background-color: rgba(16, 185, 129, 0.2); color: var(--success); border: 1px solid var(--success); }
        .badge-fail { background-color: rgba(239, 68, 68, 0.2); color: var(--error); border: 1px solid var(--error); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background-color: var(--card-bg); padding: 18px; border-radius: 12px; border: 1px solid var(--border); text-align: center; }
        .stat-val { font-size: 2rem; font-weight: 700; margin-bottom: 4px; }
        .stat-label { color: var(--text-muted); font-size: 0.85rem; }
        .suite-card { background-color: var(--card-bg); border-radius: 12px; border: 1px solid var(--border); margin-bottom: 20px; overflow: hidden; }
        .suite-header { padding: 14px 20px; background-color: rgba(255,255,255,0.03); border-bottom: 1px solid var(--border); font-weight: 600; display: flex; justify-content: space-between; }
        .test-item { padding: 12px 20px; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; align-items: center; justify-content: space-between; font-size: 0.95rem; }
        .test-item:last-child { border-bottom: none; }
        .test-info { display: flex; align-items: center; gap: 12px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }
        .dot-pass { background-color: var(--success); box-shadow: 0 0 8px var(--success); }
        .dot-fail { background-color: var(--error); box-shadow: 0 0 8px var(--error); }
        .test-meta { color: var(--text-muted); font-size: 0.85rem; }
        .error-box { background-color: rgba(239, 68, 68, 0.1); border: 1px solid var(--error); color: #fca5a5; padding: 10px 14px; border-radius: 8px; margin: 8px 20px 14px 20px; font-size: 0.85rem; direction: ltr; text-align: left; font-family: monospace; }
        .btn-refresh { background-color: var(--primary); color: #fff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; border: none; cursor: pointer; }
        .btn-refresh:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div>
                <h1>🧪 تست‌های یونیت بک‌اند چت‌روم مشترک</h1>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 4px;">سیستم اعتبارسنجی خودکار مدل‌ها، پیکربندی چندمستأجره و اندپوینت‌ها</p>
            </div>
            <div>
                <a href="" class="btn-refresh">🔄 اجرای مجدد تست‌ها</a>
            </div>
        </header>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-val" style="color: <?= $allPassed ? 'var(--success)' : 'var(--error)' ?>">
                    <?= $allPassed ? '۱۰۰٪' : round(($report['passed'] / max(1, $report['total_tests'])) * 100) . '٪' ?>
                </div>
                <div class="stat-label">وضعیت کلی موفقیت</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: var(--primary)"><?= $report['total_tests'] ?></div>
                <div class="stat-label">کل تست‌ها</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: var(--success)"><?= $report['passed'] ?></div>
                <div class="stat-label">موفق (Passed)</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: var(--error)"><?= $report['failed'] + $report['errors'] ?></div>
                <div class="stat-label">ناموفق / خطا</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: var(--warning)"><?= $report['total_assertions'] ?></div>
                <div class="stat-label">کل Assertions</div>
            </div>
            <div class="stat-card">
                <div class="stat-val" style="color: #38bdf8"><?= $report['time'] ?>s</div>
                <div class="stat-label">زمان کل اجرا</div>
            </div>
        </div>

        <?php foreach ($report['suites'] as $suiteName => $suite): ?>
            <div class="suite-card">
                <div class="suite-header">
                    <span>📦 مجموعه تست: <strong><?= htmlspecialchars($suiteName) ?></strong></span>
                    <span style="color: var(--text-muted); font-size: 0.85rem;"><?= count($suite['tests']) ?> تست</span>
                </div>
                <?php foreach ($suite['tests'] as $test): ?>
                    <div class="test-item">
                        <div class="test-info">
                            <span class="status-dot <?= $test['status'] === 'PASSED' ? 'dot-pass' : 'dot-fail' ?>"></span>
                            <span><?= htmlspecialchars($test['name']) ?></span>
                        </div>
                        <div class="test-meta">
                            <span><?= $test['assertions'] ?> assertions</span> &bull; 
                            <span><?= $test['time'] ?> ثانیه</span>
                        </div>
                    </div>
                    <?php if (!empty($test['error'])): ?>
                        <div class="error-box">
                            <?= htmlspecialchars($test['error']) ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
