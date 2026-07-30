<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    'collections' => [],
    'message'     => null,
    'error'       => null
];

if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

$userDeviceType = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$isSameDayEligible = false;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $isSameDayEligible = (bool)($data['same_day_eligible'] ?? false);
} else {
    session_start();
    $isSameDayEligible = (bool)($_SESSION['same_day_eligible'] ?? false);
}

$response['collections'] = homepageCollections($pdo, $isSameDayEligible);

echo json_encode($response);
exit;
