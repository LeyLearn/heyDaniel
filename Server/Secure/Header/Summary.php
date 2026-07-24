<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    'table_source' => 'Cart',
    'subtotal'     => 0.00,
    'message'      => null,
    'error'        => null
];


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
    $userId              = resolveMobileUserId($data);
    $taxRate = (float)($data['tax_rate'] ?? 0.00);
    $hasActiveOrder = (bool)($data['has_active_order'] ?? false);
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $taxRate = (float)($_SESSION['tax_rate'] ?? 0.00);
    $hasActiveOrder = (bool)($_SESSION['has_active_order'] ?? false);
}

$summary = Summary($pdo, $userId, $hasActiveOrder, $taxRate);

if (!empty($summary['error'])) {
    http_response_code(500);
    $response['error'] = $summary['error'];
    echo json_encode($response);
    exit;
}

$response['subtotal'] = $summary['subtotal'];
$response['table_source'] = $summary['table_source'];

echo json_encode($response);

exit;
