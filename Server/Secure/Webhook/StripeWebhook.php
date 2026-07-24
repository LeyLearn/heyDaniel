<?php

// Direct Stripe callback endpoint — not routed through index.php's
// {action, device_type} contract, since Stripe posts its own raw event
// payload with a signature header instead.
declare(strict_types=1);

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

header('Content-Type: application/json; charset=utf-8');

$payload        = @file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhookSecret  = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

if (empty($webhookSecret)) {
    error_log("StripeWebhook: STRIPE_WEBHOOK_SECRET is not configured.");
    http_response_code(500);
    echo json_encode(['error' => 'Webhook not configured']);
    exit;
}

\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

try {
    $event = \Stripe\Webhook::constructEvent($payload, $signatureHeader, $webhookSecret);
} catch (\UnexpectedValueException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// A subscription only ever leaves 'active'/'trialing' through one of these
// two events — a renewal failing (payment_failed) or the subscription
// actually ending (deleted, e.g. after Stripe's own retry schedule gives
// up, or a cancellation made directly in the Stripe dashboard). Either way
// the membership needs to drop even though nobody clicked "cancel" in-app.
switch ($event->type) {
    case 'invoice.payment_failed':
        $invoice = $event->data->object;
        if (!empty($invoice->subscription)) {
            revokeMembershipBySubscriptionId($pdo, $invoice->subscription);
        }
        break;

    case 'customer.subscription.deleted':
        $subscription = $event->data->object;
        revokeMembershipBySubscriptionId($pdo, $subscription->id);
        break;
}

http_response_code(200);
echo json_encode(['received' => true]);
exit;

function revokeMembershipBySubscriptionId(\PDO $db, string $subscriptionId): void
{
    try {
        $stmt = $db->prepare("SELECT UserId FROM CustomerPaymentMethod WHERE StripeSubscriptionId = ? LIMIT 1");
        $stmt->execute([$subscriptionId]);
        $userId = $stmt->fetchColumn();

        if (!$userId) {
            return;
        }

        $stmt = $db->prepare("UPDATE Users SET IsMember = 0 WHERE Id = ?");
        $stmt->execute([$userId]);

        $stmt = $db->prepare("UPDATE CustomerPaymentMethod SET StripeSubscriptionId = NULL WHERE UserId = ?");
        $stmt->execute([$userId]);

        createNotification($db, (int)$userId, 'membership', 'Membership ended', 'Your HeyDaniel+ membership has ended.');
    } catch (\PDOException $e) {
        error_log("DB Error in revokeMembershipBySubscriptionId: " . $e->getMessage());
    }
}
