<?php

include_once "../connect.php";

$response = [
    'success'           => false,
    'same_day_eligible' => false,
    'error'             => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (
    count($data) !== 5
    || !isset($data['device_signature'])
    || !is_string($data['device_signature'])
    || $data['device_signature'] === ''
    || strlen($data['device_signature']) > 128
    || !preg_match('/^[a-zA-Z0-9-]+$/', $data['device_signature'])
    || !isset($data['device_type'])
    || !is_string($data['device_type'])
    || $data['device_type'] === ''
    || strlen($data['device_type']) > 32
    || !in_array($data['device_type'], ['Apple', 'Android', 'Web'])
    || !isset($data['zipcode'])
    || !is_string($data['zipcode'])
    || $data['zipcode'] === ''
    || strlen($data['zipcode']) > 16
    || !preg_match('/^[a-zA-Z0-9\- ]+$/', $data['zipcode'])
    || !isset($data['user_address'])
    || !is_string($data['user_address'])
    || $data['user_address'] === ''
    || strlen($data['user_address']) > 256
    || !isset($data['user_id'])
    || !is_int($data['user_id'])
    || $data['user_id'] === ''
    || strlen($data['user_id']) > 64
) {
    http_response_code(400);
    $response['error'] = 'Invalid or missing parameters.';
    echo json_encode($response);
    exit;
}

$userId = (Int)$data['user_id'];
$userAddress = htmlentities($data['user_address']);
$userZip = htmlentities($data['zipcode']);
$userDevice = htmlentities($data['device_signature']);
$deviceType = htmlentities($data['device_type']);

$isActive = true;
$userTimeRegister = date("Y-m-d H:i:s");

// Whitelist column mapping — eliminates SQL injection
$columnMap = [
    'Apple'   => 'AppleDevice',
    'Android' => 'AndroidDevice',
    'Web'     => 'WebDevice'
];
$column = $columnMap[$deviceType] ?? null;

if (!$column) {
    // This should never happen — but defense in depth
    http_response_code(400);
    $response['error'] = 'invalid device type';
    echo json_encode($response);
    exit;
}
// check if device exists in Devices table
$stmt = $pdo->prepare("
    SELECT 1
    FROM Devices
    WHERE DeviceSignature = ?
    ");
$stmt->execute([$userDevice]);
$exist = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exist) {
    // device exists, update zip and address
    $stmt = $pdo->prepare("
        UPDATE Devices
        SET ZipCode = ?, UserAddress = ?
        WHERE DeviceSignature = ?
    ");
    $stmt->execute([$userZip, $userAddress, $userDevice]);
}
else {
    // check in Users table
    $stmt = $pdo->prepare("
        SELECT 1
        FROM Users
        WHERE $column = ?
    ");
    $stmt->execute([$userDevice, $userDevice, $userDevice]);
    $existInUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existInUser) {
        // device exists in Users table, update zip
        $stmt = $pdo->prepare("
            UPDATE Users
            SET ZipCode = ?
            WHERE $column = ?
        ");
        $stmt->execute([$userZip, $userDevice]);
    } else {
        if ($userId !== 0) {
            // insert device for logged-in user
            $stmt = $pdo->prepare("
                UPDATE Users
                SET $deviceType = ?, ZipCode = ?
                WHERE Id = ?
            ");
            $stmt->execute([$userDevice, $userZip, $userId]);
        } else {
            // insert new device for guest user
            $stmt = $pdo->prepare("
                INSERT INTO Devices (DeviceSignature, DeviceType, ZipCode, UserAddress, isActive, DateAdded)
                VALUES (?, ?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$userDevice, $deviceType, $userZip, $userAddress]);
        }
    }
}

echo json_encode($response);
