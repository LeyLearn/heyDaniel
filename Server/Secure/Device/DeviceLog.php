<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";


$response = [
    'zipcode'               => null,
    'city'                  => null,
    'state'                 => null,
    'same_day_eligible'     => false,
    'tax_rate'              => 0.00,
    'requires_confirmation'              => false,
    'perishable_items'                   => [],
    'requires_order_cancel_confirmation' => false,
    'order_in_transit'                   => false,
    'message'                            => null,
    'error'                              => null
];


if (!isset($data['device_type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Device type is required.']);
    exit;
}

if (!isset($data['zipcode']) || empty($data['zipcode']) || strlen($data['zipcode']) > 16 || !preg_match('/^[a-zA-Z0-9\- ]+$/', $data['zipcode'])) {
    http_response_code(200);
    echo json_encode(['message' => 'Invalid or missing zipcode.']);
    exit;
}

$userDeviceType = $data['device_type'];
$zipcode = $data['zipcode'];
$confirmRemovePerishables = (bool)($data['confirm_remove_perishables'] ?? false);
$confirmCancelOrder = (bool)($data['confirm_cancel_order'] ?? false);
$validDeviceTypes = ['iOS', 'Android', 'Web'];
$userId = 0;
$userDeviceSignature = null;

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid device type.']);
    exit;
}

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId              = resolveMobileUserId($data);
    $userDeviceSignature = (string)($data['device_signature'] ?? '');
} else {
    session_start();
    $userId              = (int)($_SESSION['user_id'] ?? 0);
    $userDeviceSignature = $_SESSION['device_signature'];
}

if ($confirmRemovePerishables) {
    // The user already saw the conflict and chose to continue — this one
    // call both drops the perishables and finalizes the zip change.
    $confirm = removePerishablesFromCart($pdo, $userId, $userDeviceSignature, $userDeviceType, $zipcode);

    if (!empty($confirm['error'])) {
        http_response_code(500);
        echo json_encode($confirm['error']);
        exit;
    }

    $response['zipcode']           = $zipcode;
    $response['city']              = $confirm['city'];
    $response['state']             = $confirm['state'];
    $response['same_day_eligible'] = false;
    $response['tax_rate']          = $confirm['tax_rate'];
} else {
    $update = updateDeviceZip($pdo, $userDeviceSignature, $userDeviceType, $zipcode, $userId, $confirmCancelOrder);

    if (!empty($update['error'])) {
        http_response_code(500);
        echo json_encode($update['error']);
        exit;
    }

    if (!empty($update['message'])) {
        $response['message'] = $update['message'];
        echo json_encode($response);
        exit;
    }

    if ($update['requires_order_cancel_confirmation']) {
        $response['requires_order_cancel_confirmation'] = true;
        echo json_encode($response);
        exit;
    }

    if ($update['requires_confirmation']) {
        $response['requires_confirmation'] = true;
        $response['perishable_items'] = $update['perishable_items'];
        $response['order_in_transit'] = $update['order_in_transit'];
        echo json_encode($response);
        exit;
    }

    $response['zipcode']           = $update['zipcode'];
    $response['city']              = $update['city'];
    $response['state']             = $update['state'];
    $response['same_day_eligible'] = $update['same_day_eligible'];
    $response['tax_rate']          = $update['tax_rate'];
    $response['order_in_transit']  = $update['order_in_transit'];
}

$_SESSION['zipcode'] = $response['zipcode'];
$_SESSION['city'] = $response['city'];
$_SESSION['state'] = $response['state'];
$_SESSION['tax_rate'] = $response['tax_rate'];
$_SESSION['same_day_eligible'] = $response['same_day_eligible'];

echo json_encode($response);
exit;
