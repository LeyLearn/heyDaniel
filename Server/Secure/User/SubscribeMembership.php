<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    "success" => false,
    "message" => null,
    "error"   => null
];

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['message'] = "Device type is required.";
    echo json_encode($response);
    exit;
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['message'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

$userId = 0;
$isWeb  = ($userDeviceType === 'Web');

if ($isWeb) {
    session_start();
    $userId = (int)($_SESSION['user_id'] ?? 0);
} else {
    $userId = resolveMobileUserId($data);
}

$paymentMethodId = trim($data['payment_method_id'] ?? '');
$saveCard         = (bool)($data['save_card'] ?? true);

if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

if ($userId <= 0) {
    http_response_code(401);
    $response['message'] = "Unauthorized.";
    echo json_encode($response);
    exit;
}

if (empty($paymentMethodId)) {
    http_response_code(400);
    $response['message'] = "Payment method is required.";
    echo json_encode($response);
    exit;
}

if (RateLimiter::tooManyAttempts($pdo, "subscribe_membership:{$userId}", 5, 600)) {
    http_response_code(429);
    $response['message'] = "Too many attempts. Please wait a few minutes and try again.";
    echo json_encode($response);
    exit;
}

$result = subscribeMembership($pdo, $userId, $paymentMethodId, $saveCard);

if (!empty($result['error'])) {
    http_response_code(400);
    $response['error'] = $result['error'];
    echo json_encode($response);
    exit;
}

if (!empty($result['requires_action'])) {
    echo json_encode([
        'success'         => false,
        'requires_action' => true,
        'client_secret'   => $result['client_secret']
    ]);
    exit;
}

if ($isWeb) {
    session_start();
    $_SESSION['is_member'] = true;
    session_write_close();
}

$response['success'] = true;
echo json_encode($response);
exit;
