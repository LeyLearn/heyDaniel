<?php

declare(strict_types=1);

// Sweeps for tip holds whose 3-day decision window has passed untouched
// and captures them as-is. Meant to be run on a schedule (e.g. hourly) via
// a system cron calling `php Server/Cron/CaptureHeldTips.php` - not
// reachable over HTTP, and not registered in index.php's route table.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden.\n");
}

require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/../Function/Components.php';

$result = captureExpiredTipHolds($pdo);

echo "Captured: {$result['captured']}, Failed: {$result['failed']}\n";
