<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    'saved_items' => [],
    'location'    => 'Cart',
    'message'     => null,
    'error'       => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}
$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$taxRate = 0.00;
$hasActiveOrder = false;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
    $taxRate = (float)($data['tax_rate'] ?? 0.00);
    $hasActiveOrder = (bool)($data['has_active_order'] ?? false);
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $taxRate = (float)($_SESSION['tax_rate'] ?? 0.00);
    $hasActiveOrder = (bool)($_SESSION['has_active_order'] ?? false);
}

$savedContent = savedContent($pdo, $userId, $hasActiveOrder, $taxRate);

if (!empty($savedContent['error'])) {
    http_response_code(400);
    $response['error'] = $savedContent['error'];
    echo json_encode($response);
    exit;
}

$response['saved_items'] = $savedContent['saved_items'];
$response['location'] = $savedContent['location'] ?? 'Cart';

echo json_encode($response);
exit;
