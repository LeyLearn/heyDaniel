<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    "success" => false,
    "message" => null,
    "error" => null
];


$clientIp = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (RateLimiter::tooManyAttempts($pdo, "register:{$clientIp}", 8, 3600)) {
    http_response_code(429);
    $response['message'] = "Too many registration attempts. Please try again later.";
    echo json_encode($response);
    exit;
}

// === STRICT INPUT VALIDATION ===
$required = ['user_name', 'user_email', 'user_pass'];
foreach ($required as $field) {
    if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
        $response['message'] = "Missing or empty field: $field";
        echo json_encode($response);
        exit;
    }
}

$userName = trim($data['user_name']);
$userEmail = strtolower(trim($data['user_email']));
$userPass = $data['user_pass'];

if (!preg_match('/^[\p{L}\p{M}\'\-\s]+$/u', $userName)) {
    $response['message'] = 'Invalid name format';
    echo json_encode($response);
    exit;
}

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email address';
    echo json_encode($response);
    exit;
}

// SECURITY: Insufficient input validation (Vulnerability #11)
if (
    strlen($userPass) < 8 || strlen($userPass) > 128 ||
    !preg_match('/[A-Z]/', $userPass) ||
    !preg_match('/[a-z]/', $userPass) ||
    !preg_match('/[0-9]/', $userPass) ||
    !preg_match('/[^A-Za-z0-9]/', $userPass)
) {
    $response['message'] = 'Password must be 8-128 characters with uppercase, lowercase, number, and symbol.';
    echo json_encode($response);
    exit;
}

$registerUser = registerUser($pdo, $userName, $userEmail, $userPass);

if (!empty($registerUser['error'])) {
    http_response_code(400);
    $response['error'] = $registerUser['error'];
    echo json_encode($response);
    exit;
}
if (!empty($registerUser['message'])) {
    $response['message'] = $registerUser['message'];
    echo json_encode($response);
    exit;
}

if ($registerUser['success'] === true) {
    $response['success'] = $registerUser['success'];
    echo json_encode($response);
    exit;
}
