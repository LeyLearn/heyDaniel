<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    "success" => false,
    "message" => null,
    "error"   => null
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

$userId    = 0;
$orderId   = 0;
$tipAmount = 0.00;

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId    = resolveMobileUserId($data);
    $orderId   = (int)($data['order_id']   ?? 0);
    $tipAmount = (float)($data['tip_amount'] ?? 0.00);
} else {
    session_start();
    $userId    = (int)($_SESSION['user_id'] ?? 0);
    $orderId   = (int)($data['order_id']    ?? 0);
    $tipAmount = (float)($data['tip_amount'] ?? 0.00);
}

// Release the session write lock before finalizeOrder()'s Stripe API calls
// (capture + possible tip charge) — see Checkout.php for the same fix.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if ($userId <= 0) {
    http_response_code(401);
    $response['message'] = "Unauthorized.";
    echo json_encode($response);
    exit;
}

if ($orderId <= 0) {
    http_response_code(400);
    $response['message'] = "A valid order ID is required.";
    echo json_encode($response);
    exit;
}

$finalize = finalizeOrder($pdo, $userId, $orderId, $tipAmount);

if (!empty($finalize['error'])) {
    http_response_code(500);
    $response['error'] = $finalize['error'];
    echo json_encode($response);
    exit;
}

$response['success'] = true;

echo json_encode($response);
exit;
