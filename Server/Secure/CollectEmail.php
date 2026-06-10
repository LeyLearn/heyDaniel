<?php

include_once "../connect.php";

$response = [
    "is_registered" => false,
    "unique_code" => null,
    "email" => null,
    "error" => null
];

include_once "../../Function/Auth/ArrayAuth.php";

if (!is_array($data) || !isset($data['user_email']) || count($data) !== 2) {
    http_response_code(400);
    $response['error'] = 'Invalid request';
    echo json_encode($response);
    exit;
}

$userEmail = strtolower(trim($data['user_email'])) ?? null;
$isChangeEmail = isset($data['is_change_email']) ? (bool)$data['is_change_email'] : false;

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
        $expiredAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        $insertStmt = $pdo->prepare("INSERT INTO PasswordResetCodes ( Email, Code, SentIn, ExpiredAt) VALUES (?, ?, NOW(), ?)");
        $insertStmt->execute([$userEmail, $uniqueCode, $expiredAt]);
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
