<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    'products' => [],
    'error'    => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['error'] = "Device type is required.";
    echo json_encode($response);
    exit;
}
if (!isset($data['search_term']) || $data['search_term'] === "") {
    http_response_code(400);
    $response['error'] = "Invalid or missing search term.";
    echo json_encode($response);
    exit;
}
$searchTerm = $data['search_term'];
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
    $taxRate = (float)($data['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $taxRate = (float)($_SESSION['tax_rate'] ?? 0.00);
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
}

$search = searchEngine($pdo, $searchTerm, $isSameDayEligible, $taxRate);

if (!empty($search['error'])) {
    http_response_code(400);
    $response['error'] = $search['error'];
    echo json_encode($response);
    exit;
}

$response['products'] = $search['products'];

echo json_encode($response);

exit;
