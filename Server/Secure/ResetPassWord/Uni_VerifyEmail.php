<?php

include_once "../connect.php";
header('Content-Type: application/json; charset=utf-8');

$response = [
    "success" => false,
    "message" => null
];
$rawInput = file_get_contents('php://input');   
if ($rawInput === false || $rawInput === '' || strlen($rawInput) > 2048) {
    http_response_code(400);
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}   
$data = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['message'] = 'Malformed JSON';
    echo json_encode($response);
    exit;
}
if (!is_array($data) || !isset($data['userEmail']) || !isset($data['uniqueCode']) || count($data) !== 2) {
    http_response_code(400);
    $response['message'] = 'Invalid request';
    echo json_encode($response);
    exit;
}
$userEmail = strtolower(trim($data['userEmail']));
$uniqueCode = trim($data['uniqueCode']);

if ($userEmail === "" || !filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}
if ($uniqueCode === null || !preg_match('/^\d{6}$/', $uniqueCode)) {
    http_response_code(400);
    $response['message'] = 'Invalid code format';
    echo json_encode($response);
    exit;
}
date_default_timezone_set('America/New_York');
$currentTime = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT Code, ExpiredAt FROM PasswordResetCodes WHERE Email = ? LIMIT 1");
$stmt->execute([$userEmail]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$row) {
    $response['message'] = 'No code was sent to this email. Please request a code first.';
    echo json_encode($response);
    exit;
}
$dbCode = $row['Code'];
$expiredAt = $row['ExpiredAt'];
if ($uniqueCode !== $dbCode) {
    $response['message'] = 'The code entered was not valid.';
    echo json_encode($response);
    exit;
} else if ($currentTime > $expiredAt) {
    $response['message'] = 'The code has expired. Please request a new one.';
    // delete the expired code from the database here
    $deleteStmt = $pdo->prepare("DELETE FROM PasswordResetCodes WHERE Email = ?");
    $deleteStmt->execute([$userEmail]);
    echo json_encode($response);
    exit;
} else {
    $response['success'] = true;
    $response['message'] = 'Success';
    echo json_encode($response);
    exit;
}