<?php

include_once "../connect.php";

header('Content-Type: application/json; charset=utf-8');
$rawInput = file_get_contents('php://input');

$response = [
    'success'           => false,
    'user_id'           => null,
    'zipcode'           => null,
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
    count($input) !== 1
    || !isset($input['device_signature'])
    || !is_string($input['device_signature'])
    || $input['device_signature'] === ''
    || strlen($input['device_signature']) > 128
    || !preg_match('/^[a-zA-Z0-9-]+$/', $input['device_signature'])
) {
    http_response_code(400);
    $response['error'] = 'Invalid or missing device_signature format.';
    echo json_encode($response);
    exit;
}

$userDevice = $input['device_signature'];

// Step 2: Check if the device exists in Devices table
$stmt = $pdo->prepare("
    SELECT Zipcode
    FROM Devices
    WHERE DeviceSignature = ?
    ");
$stmt->execute([$userDevice]);

if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // ✅ Device found in Devices table
    $response['success'] = true;
    $response['zipcode'] = $row['Zipcode'] ?: null;
} else {
    // Step 3: Not found in Devices — check Users table
    $stmt = $pdo->prepare("
           SELECT Id, Zipcode 
           FROM Users 
           WHERE AppleDevice = ? 
                OR AndroidDevice = ? 
                OR WebDevice = ?
            ");
    $stmt->execute([$userDevice, $userDevice, $userDevice]);

    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // ✅ Device found in Users table
        $response['success'] = true;
        $response['user_id'] = (int)$row['Id'];
        $response['zipcode'] = $row['Zipcode'] ?: null;
    }
}

if ($response['success'] === true && $response['zipcode'] !== null) {
    $stmt = $pdo->prepare("
        SELECT 1 FROM AllowedZip WHERE Zipcode = ? LIMIT 1
    ");

    $stmt->execute([$response['zipcode']]);
    $response['same_day_eligible'] = (bool) $stmt->fetchColumn() === 1;
}

echo json_encode($response);
