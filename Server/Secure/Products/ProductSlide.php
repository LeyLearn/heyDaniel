<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";


$response = [
    'products'          => [],
    'same_day_eligible' => false,
    'message'           => null,
    'error'             => null
];


if (!isset($data['table'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$tables = $data['table'];
$validTables = ['Recommendations', 'RecentlyBought', 'Popular'];
$userId = 0;
$isSameDayEligible = false;
$taxRate = 0.00;

if(!in_array($userDeviceType, $validDeviceTypes)){
    http_response_code(400);
    echo json_encode(['error'   => 'Invalid device type.']);
    exit;
}

if (!in_array($tables, $validTables, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid table variable.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
    $taxRate = (float)($data['tax_rate'] ?? 0.00);
} else {
    session_start();
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
    $taxRate = (float)($_SESSION['tax-rate'] ?? 0.00);
}

$productSlide = pullingProducts($pdo, $isSameDayEligible, $tables, $userId, $taxRate);

if (!empty($productSlide['error'])) {
    http_response_code(500);
    echo json_encode($productSlide['error']);
    exit;
}

if (!empty($productSlide['message'])) {
    $response['message'] = $productSlide['message'];
    echo json_encode($response);
    exit;
}

$response['products'] = $productSlide['products'];
$response['same_day_eligible'] = $isSameDayEligible;

echo json_encode($response);
exit;
