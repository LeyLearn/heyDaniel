<?php
include_once "../connect.php";
include_once "../../Function/Components.php";

$response = [
    "success" => false,
    "error" => null
];

include_once "../../Function/Auth/ArrayAuth.php";

// === STRICT INPUT VALIDATION ===
$required = ['user_name', 'user_email', 'user_pass'];
foreach ($required as $field) {
    if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
        http_response_code(400);
        $response['error'] = "Missing or empty field: $field";
        echo json_encode($response);
        exit;
    }
}

$userName = trim($data['user_name']);
$userEmail = strtolower(trim($data['user_email']));
$userPass = $data['user_pass'];

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

$registerUser = registerUser($pdo, $userName, $userEmail, $userPass);

if(!empty($registerUser['error'])){
    http_response_code(400);
    $response['error'] = $registerUser['error'];
    echo json_encode($response);
    exit;
}

$response['success'] = true;


echo json_encode($response);
exit;
