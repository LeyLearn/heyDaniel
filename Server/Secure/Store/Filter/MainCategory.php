<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    'categories' => [],
    'error'      => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['error'] = "Device type is required.";
    echo json_encode($response);
    exit;
}

$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$taxRate = 0.00;
$isSameDayEligible = false;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['error'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
} else {
    session_start();
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
}

$mainCategories = mainCategories($pdo, $isSameDayEligible);

if (!empty($mainCategories['error'])) {
    http_response_code(400);
    $response['error'] = $mainCategories['error'];
    echo json_encode($response);
    exit;
}

$response['categories'] = $mainCategories['categories'];

echo json_encode($response);

exit;
