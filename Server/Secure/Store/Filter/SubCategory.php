<?php

include_once __DIR__ . "/../../../Connect.php";
include_once __DIR__ . "/../../../Function/Components.php";

$response = [
    'sub_categories' => [],
    'message'        => null,
    'error'          => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}
if (!isset($data['main_category']) || $data['main_category'] === "") {
    http_response_code(400);
    $response['message'] = "Main category is required.";
    echo json_encode($response);
    exit;
}
$mainCategory = $data['main_category'];
$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$taxRate = 0.00;
$isSameDayEligible = false;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
} else {
    session_start();
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
}

$subCategories = subCategories($pdo, $mainCategory, $isSameDayEligible);

if (!empty($subCategories['error'])) {
    http_response_code(500);
    $response['error'] = $subCategories['error'];
    echo json_encode($response);
    exit;
}

$response['sub_categories'] = $subCategories['sub_categories'];

echo json_encode($response);

exit;
