<?php

include_once __DIR__ . "/../../../Connect.php";
include_once __DIR__ . "/../../../Function/Components.php";

$response = [
    'products'          => [],
    'similar_products'  => [],
    'available_filters' => [],
    'message'           => null,
    'error'             => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}

if (!isset($data['filter']) || !is_array($data['filter']) || empty($data['filter'])) {
    http_response_code(400);
    $response['message'] = "Filter is required.";
    echo json_encode($response);
    exit;
}

$userDeviceType    = $data['device_type'];
$validDeviceTypes  = ['iOS', 'Android', 'Web'];
$userId            = 0;
$taxRate           = 0.00;
$isSameDayEligible = false;
$hasActiveOrder    = false;
$filter            = $data['filter'];
$limit             = (int)($data['limit'] ?? 16);

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId            = resolveMobileUserId($data);
    $taxRate           = (float)($data['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
    $hasActiveOrder    = (bool)($data['has_active_order'] ?? false);
} else {
    session_start();
    $userId            = (int)($_SESSION['user_id'] ?? 0);
    $taxRate           = (float)($_SESSION['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
    $hasActiveOrder    = (bool)($_SESSION['has_active_order'] ?? false);
}

$filterStore = filterStore($pdo, $userId, $hasActiveOrder, $isSameDayEligible, $taxRate, $filter, $limit);

if (!empty($filterStore['error'])) {
    http_response_code(500);
    $response['error'] = $filterStore['error'];
    echo json_encode($response);
    exit;
}

$response['products']          = $filterStore['products'];
$response['similar_products']  = $filterStore['similar_products'];
$response['available_filters'] = $filterStore['available_filters'];

echo json_encode($response);
exit;
