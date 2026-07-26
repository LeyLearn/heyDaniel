<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";


$response = [
    'icon' => 'icon_cart',
    'total_count' => 0,
    'has_active_order' => false,
    'order_status' => null,
    'error' => null
];


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
    $userId              = resolveMobileUserId($data);
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
}

$cartIcon = cartIcon($pdo, $userId);

if (!empty($cartIcon['error'])) {
    http_response_code(500);
    $response['error'] = $cartIcon['error'];
    echo json_encode($response);
    exit;
}

$response['total_count'] = $cartIcon['total_count'];
$response['has_active_order'] = $cartIcon['has_active_order'];
$response['icon'] = $cartIcon['icon'];
$response['order_status'] = $cartIcon['order_status'];

echo json_encode($response);
exit;
