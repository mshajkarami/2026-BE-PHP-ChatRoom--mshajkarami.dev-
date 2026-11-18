<?php
// ════════════════════════════════════════════
// ══════════ ثبت گزارش ══════════════════════
// ════════════════════════════════════════════

require_once __DIR__ . '/db.php';

requirePost();

$input = getInput();

$reporterCode = $input['reporter_code'] ?? '';
$reportedCode = $input['reported_code'] ?? '';
$reason = $input['reason'] ?? '';

if (empty($reporterCode) || empty($reportedCode)) {
    sendJSON([
        'success' => false,
        'message' => 'reporter_code and reported_code are required'
    ]);
}

if (empty($reason)) {
    sendJSON([
        'success' => false,
        'message' => 'reason is required'
    ]);
}

try {
    $reportId = saveReport([
        'reporter_code' => $reporterCode,
        'reporter_name' => $input['reporter_name'] ?? '',
        'reported_code' => $reportedCode,
        'reported_name' => $input['reported_name'] ?? '',
        'message_text' => $input['message_text'] ?? '',
        'reason' => $reason,
        'date' => $input['date'] ?? '',
        'time' => $input['time'] ?? '',
        'message_id' => $input['message_id'] ?? 0,
        'is_private' => $input['is_private'] ?? 0,
        'status' => 'new',
        'screenshot_path' => $input['screenshot_path'] ?? ''
    ]);
    
    sendJSON([
        'success' => true,
        'message' => 'Report submitted',
        'report_id' => $reportId
    ]);
    
} catch (Exception $e) {
    sendJSON([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>