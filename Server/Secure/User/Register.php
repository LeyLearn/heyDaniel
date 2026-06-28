<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    "success" => false,
    "error" => null
];


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

// SECURITY: Insufficient input validation (Vulnerability #11)
if (
    strlen($userPass) < 8 || strlen($userPass) > 128 ||
    !preg_match('/[A-Z]/', $userPass) ||
    !preg_match('/[a-z]/', $userPass) ||
    !preg_match('/[0-9]/', $userPass) ||
    !preg_match('/[^A-Za-z0-9]/', $userPass)
) {
    http_response_code(400);
    $response['error'] = 'Password must be 8-128 characters with uppercase, lowercase, number, and symbol.';
    echo json_encode($response);
    exit;
}

$registerUser = registerUser($pdo, $userName, $userEmail, $userPass);

if (!empty($)) {
    http_response_code(400);
    echo json_encode($response);
    exit;
}
if (!empty($)) {
    $response['message'] = $;
    echo json_encode($response);
    exit;
}

echo json_encode(['registered' => true]);
exit;
