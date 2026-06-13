<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    "success" => false,
    "error"   => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['error'] = "Device type is required.";
    echo json_encode($response);
    exit;
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['error'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

$userId    = 0;
$orderId   = 0;
$tipAmount = 0.00;

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId    = (int)($data['user_id']    ?? 0);
    $orderId   = (int)($data['order_id']   ?? 0);
    $tipAmount = (float)($data['tip_amount'] ?? 0.00);
} else {
    session_start();
    $userId    = (int)($_SESSION['user_id'] ?? 0);
    $orderId   = (int)($data['order_id']    ?? 0);
    $tipAmount = (float)($data['tip_amount'] ?? 0.00);
}

if ($userId <= 0) {
    http_response_code(401);
    $response['error'] = "Unauthorized.";
    echo json_encode($response);
    exit;
}

if ($orderId <= 0) {
    http_response_code(400);
    $response['error'] = "A valid order ID is required.";
    echo json_encode($response);
    exit;
}

$finalize = finalizeOrder($pdo, $userId, $orderId, $tipAmount);

if (!empty($finalize['error'])) {
    http_response_code(400);
    $response['error'] = $finalize['error'];
    echo json_encode($response);
    exit;
}

$response['success'] = true;

echo json_encode($response);
exit;
