<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    'table_source' => null,
    'subtotal'     => 0.00,
    'quantity'     => 0,
    'total_count'    => 0,
    'error'        => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['error'] = "Device type is required.";
    echo json_encode($response);
    exit;
}
if (!isset($data['product_id']) || !is_int($data['product_id']) || $data['product_id'] <= 0) {
    http_response_code(400);
    $response['error'] = "Invalid or missing product ID.";
    echo json_encode($response);
    exit;
}
$productId = $data['product_id'];
$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$taxRate = 0.00;
$hasActiveOrder = false;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['error'] = "Invalid device type.";
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

$addProduct = addProduct($pdo, $productId, $userId, $hasActiveOrder, $taxRate);

if (!empty($)) {
    http_response_code(400);
    echo json_encode($response);
    exit;
}
if (!empty($)) {
    $response['message'] = $;
    echo json_encode($response);
    exit;
}

$response['table_source'] = $addProduct['table_source'] ?? null;
$response['subtotal'] = $addProduct['subtotal'] ?? 0.00;
$response['quantity'] = $addProduct['quantity'] ?? 0;
$response['total_count'] = $addProduct['total_count'] ?? 0;

echo json_encode($response);
exit;
