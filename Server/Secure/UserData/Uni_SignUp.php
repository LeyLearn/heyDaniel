<?php

include_once "../connect.php";

$response = [
    "success" => false,
    "error" => null
];

include_once "../../Function/Auth/ArrayAuth.php";

// === STRICT INPUT VALIDATION ===
$required = ['user_name', 'user_email', 'user_pass', 'device', 'device_type'];
foreach ($required as $field) {
    if (!isset($input[$field]) || !is_string($input[$field]) || trim($input[$field]) === '') {
        http_response_code(400);
        $response['error'] = "Missing or empty field: $field";
        echo json_encode($response);
        exit;
    }
}

$userName = trim($input['user_name']);
$userEmail = strtolower(trim($input['user_email']));
$userPass = $input['user_pass'];
$device = trim($input['device']);
$deviceType = trim($input['device_type']);

if (strlen($device) > 128 || strlen($deviceType) > 50 || strlen($userName) > 100) {
    http_response_code(400);
    $response['error'] = 'Input too long';
    echo json_encode($response);
    exit;
}

if (!preg_match('/^[\p{L}\p{M}\'\-\s]+$/u', $userName)) {
    http_response_code(400);
    $response['error'] = 'Invalid name format';
    echo json_encode($response);
    exit;
}

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['error'] = 'Invalid email address';
    echo json_encode($response);
    exit;
}

if (
    strlen($userPass) < 8 ||
    !preg_match('/[A-Z]/', $userPass) ||     // at least one uppercase
    !preg_match('/[a-z]/', $userPass) ||     // at least one lowercase
    !preg_match('/[0-9]/', $userPass) ||     // at least one digit
    !preg_match('/[^A-Za-z0-9]/', $userPass) // at least one special char
) {
    http_response_code(400);
    $response['error'] = 'Password too weak. Must be 8+ chars with uppercase, lowercase, number, and symbol.';
    echo json_encode($response);
    exit;
}

// checking if  user has its device registered
$userZip = null;
$userAddress = null;

$stmt = $pdo->prepare("SELECT * FROM Devices WHERE DeviceSignature = ?");
$stmt->execute([$Device]);
$exist = $stmt->fetch(PDO::FETCH_ASSOC);

if ($exist) {
    $userZip = $exist['ZipCode'] ?? '';
    $userAddress = $exist['UserAddress'] ?? '';
}

$hashedPassWord = password_hash($userPass, PASSWORD_DEFAULT);
$userPhone = null;
$userUnit = null;
$LatnLong = null;
$userGateCode = null;
$userNote = null;
$userCredits = 0.0;
$isUpgraded = false;
$userTimeMember = null;
$isActive = true;

$allowedDeviceTypes = ['Apple', 'Android', 'Web'];
if (!in_array($deviceType, $allowedDeviceTypes, true)) {
    http_response_code(400);
    $response['error'] = 'Invalid DeviceType';
    echo json_encode($response);
    exit;
}

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
$stmt = $pdo->prepare("SELECT COUNT(*) FROM Users WHERE Email = ?");
$stmt->execute([$userEmail]);

if ($stmt->fetchColumn() > 0) {
    http_response_code(400);
    $response['error'] = 'There have been an error , please try with different credentials';
    echo json_encode($response);
    exit;
} else {
    $stmt = $pdo->prepare("INSERT INTO Users (Name, Email, Password, Phone, Address, Apt, ZipCode, LatnLong, GateCode, Note, Credits, IsMember, $column, TimeRegister, TimeMembership, isActive) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
    $stmt->execute([
        $userName,
        $userEmail,
        $hashedPassWord,
        $userPhone,
        $userAddress,
        $userUnit,
        $userZip,
        $LatnLong,
        $userGateCode,
        $userNote,
        $userCredits,
        $isUpgraded,
        $device,
        $userTimeMember,
        $isActive
    ]);
    if ($exist) {
        $stmt = $pdo->prepare("DELETE FROM Devices WHERE DeviceSignature = ?");
        $stmt->execute([$device]);
    }
    $response['success'] = true;
    echo json_encode($response);
    exit;
}
