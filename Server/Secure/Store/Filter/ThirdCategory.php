<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    'third_categories' => [],
    'error'      => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['error'] = "Device type is required.";
    echo json_encode($response);
    exit;
}
if (!isset($data['sub_category']) || $data['sub_category'] === "") {
    http_response_code(400);
    $response['error'] = "Sub category is required.";
    echo json_encode($response);
    exit;
}
$subCategory = $data['sub_category'];
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

$thirdCategories = thirdCategories($pdo, $subCategory, $isSameDayEligible);

if (!empty($thirdCategories['error'])) {
    http_response_code(400);
    $response['error'] = $thirdCategories['error'];
    echo json_encode($response);
    exit;
}

$response['third_categories'] = $thirdCategories['third_categories'];

echo json_encode($response);

exit;
