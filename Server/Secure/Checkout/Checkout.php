<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    "success"  => false,
    "order_id" => null,
    "message"  => null,
    "error"    => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

$userId          = 0;
$taxRate         = 0.00;
$isSameDay       = false;
$tipAmount       = 0.00;
$paymentMethodId = '';
$address         = [];

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId          = resolveMobileUserId($data);
    $taxRate         = (float)($data['tax_rate']        ?? 0.00);
    $isSameDay       = (bool)($data['is_same_day']      ?? false);
    $tipAmount       = (float)($data['tip_amount']      ?? 0.00);
    $paymentMethodId = trim($data['payment_method_id']  ?? '');
    $address         = $data['address']                 ?? [];
} else {
    session_start();
    $userId          = (int)($_SESSION['user_id']            ?? 0);
    $taxRate         = (float)($_SESSION['tax_rate']          ?? 0.00);
    // isSameDay reflects what the shopper actually picked on the delivery
    // step, not just whether their zip happens to qualify — submitCheckout()
    // separately re-verifies membership before honoring it.
    $isSameDay       = ($data['delivery_method'] ?? '') === 'same-day' && (bool)($_SESSION['same_day_eligible'] ?? false);
    $tipAmount       = (float)($data['tip_amount']            ?? 0.00);
    $paymentMethodId = trim($data['payment_method_id']  ?? '');
    $address         = $data['address']                 ?? [];
}

// Every session value needed has been read at this point — release the
// write lock now instead of holding it through submitCheckout()'s Stripe
// API round-trips below, which would otherwise serialize any other
// concurrent request from this same browser session (e.g. cartIcon()
// polling) behind this one.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if ($userId <= 0) {
    http_response_code(401);
    $response['message'] = "Unauthorized.";
    echo json_encode($response);
    exit;
}

if (empty($paymentMethodId)) {
    http_response_code(400);
    $response['message'] = "Payment method is required.";
    echo json_encode($response);
    exit;
}

$requiredAddressFields = ['Address', 'City', 'State', 'ZipCode', 'Phone'];
foreach ($requiredAddressFields as $field) {
    if (empty($address[$field]) || !is_string($address[$field]) || trim($address[$field]) === '') {
        http_response_code(400);
        $response['message'] = "Missing or empty address field: $field";
        echo json_encode($response);
        exit;
    }
}

if (RateLimiter::tooManyAttempts($pdo, "checkout:{$userId}", 10, 600)) {
    http_response_code(429);
    $response['message'] = "Too many checkout attempts. Please wait a few minutes and try again.";
    echo json_encode($response);
    exit;
}

$checkout = submitCheckout($pdo, $userId, $isSameDay, $taxRate, $tipAmount, $paymentMethodId, $address);

if (!empty($checkout['error'])) {
    http_response_code(500);
    $response['error'] = $checkout['error'];
    echo json_encode($response);
    exit;
}

$response['success']  = true;
$response['order_id'] = $checkout['order_id'];

echo json_encode($response);
exit;
