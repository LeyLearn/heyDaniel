<?php

include_once "../connect.php";

header('Content-Type: application/json; charset=utf-8');

$response = [
    "is_registered" => false,
    "unique_code" => null,
    "email" => null,
    "error" => null
];

$rawInput = file_get_contents('php://input');

if ($rawInput === false || $rawInput === '' || strlen($rawInput) > 2048) {
    http_response_code(400);
    $response['error'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

$data = json_decode($rawInput, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['error'] = 'Malformed JSON';
    echo json_encode($response);
    exit;
}

if (!is_array($data) || !isset($data['userEmail']) || count($data) !== 2) {
    http_response_code(400);
    $response['error'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

$userEmail = strtolower(trim($data['userEmail'])) ?? null;
$isChangeEmail = isset($data['isChangeEmail']) ? (bool)$data['isChangeEmail'] : false;

if ($userEmail === null || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['error'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}

$stmt = $pdo->prepare("SELECT 1 FROM Users WHERE Email = ? LIMIT 1");
$stmt->execute([$userEmail]);
$exists = $stmt->fetchColumn();
if ($exists) {
    if ($isChangeEmail) {
        // clean up any existing codes for this email
        $deleteStmt = $pdo->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
        $deleteStmt->execute([$userEmail]);
        date_default_timezone_set('America/New_York');
        $uniqueCode = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $sentIn = date('Y-m-d H:i:s');
        $expiredAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $insertStmt = $pdo->prepare("INSERT INTO PasswordResetCodes ( Email, Code, SentIn, ExpiredAt) VALUES (?, ?, ?, ?)");
        $insertStmt->execute([$userEmail, $uniqueCode, $sentIn, $expiredAt]);
        $response['unique_code'] = $uniqueCode;
    } else {
        $response['is_registered'] = true;
        $response['email'] = $userEmail ?? null;
    }
} else {
    if ($isChangeEmail) {
        http_response_code(400);
        $response['error'] = 'Email not registered';
    } else {
        $response['is_registered'] = false;
        $response['email'] = $userEmail;
    }
}

echo json_encode($response);
exit;
