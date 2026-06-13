<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    "success" => false,
    "order_id" => null,
    "error"   => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    http_response_code(400);
    $response['error'] = "Device type is required.";
    echo json_encode($response);
    exit;
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    $response['error'] = "Invalid device type.";
    echo json_encode($response);
    exit;
}

$userId          = 0;
$taxRate         = 0.00;
$isSameDay       = false;
$tipAmount       = 0.00;
$paymentMethodId = '';
$address         = [];

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId          = (int)($data['user_id']           ?? 0);
    $taxRate         = (float)($data['tax_rate']        ?? 0.00);
    $isSameDay       = (bool)($data['is_same_day']      ?? false);
    $tipAmount       = (float)($data['tip_amount']      ?? 0.00);
    $paymentMethodId = trim($data['payment_method_id']  ?? '');
    $address         = $data['address']                 ?? [];
} else {
    session_start();
    $userId          = (int)($_SESSION['user_id']       ?? 0);
    $taxRate         = (float)($_SESSION['tax_rate']    ?? 0.00);
    $isSameDay       = (bool)($_SESSION['is_same_day']  ?? false);
    $tipAmount       = (float)($data['tip_amount']      ?? 0.00);
    $paymentMethodId = trim($data['payment_method_id']  ?? '');
    $address         = $data['address']                 ?? [];
}

if ($userId <= 0) {
    http_response_code(401);
    $response['error'] = "Unauthorized.";
    echo json_encode($response);
    exit;
}

if (empty($paymentMethodId)) {
    http_response_code(400);
    $response['error'] = "Payment method is required.";
    echo json_encode($response);
    exit;
}

$requiredAddressFields = ['address', 'city', 'state', 'zip_code', 'phone'];
foreach ($requiredAddressFields as $field) {
    if (empty($address[$field]) || !is_string($address[$field]) || trim($address[$field]) === '') {
        http_response_code(400);
        $response['error'] = "Missing or empty address field: $field";
        echo json_encode($response);
        exit;
    }
}

$checkout = submitCheckout($pdo, $userId, $isSameDay, $taxRate, $tipAmount, $paymentMethodId, $address);

if (!empty($checkout['error'])) {
    http_response_code(400);
    $response['error'] = $checkout['error'];
    echo json_encode($response);
    exit;
}

$response['success']  = true;
$response['order_id'] = $checkout['order_id'];

echo json_encode($response);
exit;
