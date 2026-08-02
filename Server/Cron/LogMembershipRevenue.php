<?php

declare(strict_types=1);

// Logs the next month's HeyDaniel+ charge (OrderSent.IsMembershipCharge)
// for every still-active member whose last logged charge is over a month
// old. Meant to be run daily via a system cron calling
// `php Server/Cron/LogMembershipRevenue.php` - not reachable over HTTP, and
// not registered in index.php's route table. Deliberately independent of
// Stripe's own billing/webhooks - the fee itself lives in the
// MembershipSettings table (see getMembershipMonthlyFee() in
// Components.php), editable without a code change.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Forbidden.\n");
}

require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/../Function/Components.php';

$result = logMonthlyMembershipRevenue($pdo);

echo "Logged: {$result['logged']}, Failed: {$result['failed']}\n";
