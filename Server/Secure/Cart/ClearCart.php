<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    'success' => false,
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

$clearCart = clearCart($pdo, $userId);

if (!empty($clearCart['error'])) {
    http_response_code(400);
    $response['error'] = $clearCart['error'];
    echo json_encode($response);
    exit;
}

$response['success'] = true;


echo json_encode($response);
exit;
