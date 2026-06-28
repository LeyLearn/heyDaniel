<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";


$response = [
    'icon' => 'icon_cart',
    'total_count' => 0,
    'has_active_order' => false,
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
$isSameDayEligible = false;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['error'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}


if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = (int)($data['user_id'] ?? 0);
    $isSameDayEligible = $data['same_day_eligible'] ?? false;
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $isSameDayEligible = $_SESSION['same_day_eligible'] ?? false;
}

$cartIcon = cartIcon($pdo, $userId, $isSameDayEligible);

if (!empty($)) {
    http_response_code(400);
    echo json_encode($response);
    exit;
}
if (!empty($)) {
    $response['message'] = $;
    echo json_encode($response);
    exit;
}

$response['total_count'] = $cartIcon['total_count'];
$response['has_active_order'] = $cartIcon['has_active_order'];
$response['icon'] = $cartIcon['icon'];

echo json_encode($response);
exit;
