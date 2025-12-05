<?php

include_once "../connect.php";

header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');

$response = [
    'success'           => false,
    'same_day_eligible' => false,
    'error'             => null
];

if ($rawInput === false || $rawInput === '' || strlen($rawInput) > 1024) {
    http_response_code(400);
    $response['error'] = 'Invalid request body';
    echo json_encode($response);
    exit;
}

$input = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['error'] = 'Bad JSON';
    echo json_encode($response);
    exit;
}
if (
    count($input) !== 5
    || !isset($input['device_signature'])
    || !is_string($input['device_signature'])
    || $input['device_signature'] === ''
    || strlen($input['device_signature']) > 128
    || !preg_match('/^[a-zA-Z0-9-]+$/', $input['device_signature'])
    || !isset($input['device_type'])
    || !is_string($input['device_type'])
    || $input['device_type'] === ''
    || strlen($input['device_type']) > 32
    || !in_array($input['device_type'], ['Apple', 'Android', 'Web'])
    || !isset($input['zipcode'])
    || !is_string($input['zipcode'])
    || $input['zipcode'] === ''
    || strlen($input['zipcode']) > 16
    || !preg_match('/^[a-zA-Z0-9\- ]+$/', $input['zipcode'])
    || !isset($input['user_address'])
    || !is_string($input['user_address'])
    || $input['user_address'] === ''
    || strlen($input['user_address']) > 256
    || !isset($input['user_id'])
    || !is_int($input['user_id'])
    || $input['user_id'] === ''
    || strlen($input['user_id']) > 64
) {
    http_response_code(400);
    $response['error'] = 'Invalid or missing parameters.';
    echo json_encode($response);
    exit;
}

$userId = (Int)$input['user_id'];
$userAddress = htmlentities($input['user_address']);
$userZip = htmlentities($input['zipcode']);
$userDevice = htmlentities($input['device_signature']);
$deviceType = htmlentities($input['device_type']);

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
    echo json_encode(['error' => 'invalid_device_type']);
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

if($userZip !== null) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM AllowedZip WHERE Zipcode = ? LIMIT 1
    ");

    $stmt->execute([$userZip]);
    $response['success'] = true;
    $response['same_day_eligible'] = (bool) $stmt->fetchColumn() === 1;
}

echo json_encode($response);
