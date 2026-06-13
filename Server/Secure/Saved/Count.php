<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    'saved_count' => 0,
    'error' => null
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
$userId = 0;


if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['error'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
}

$savedCount = savedCount($pdo, $userId);

if (!empty($savedCount['error'])) {
    http_response_code(400);
    $response['error'] = $savedCount['error'];
    echo json_encode($response);
    exit;
}

$response['saved_count'] = $savedCount['saved_count'] ?? 0;


echo json_encode($response);
exit;
