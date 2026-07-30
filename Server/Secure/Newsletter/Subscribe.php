<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
    'success' => false,
    'message' => null,
    'error'   => null
];

if (!isset($data['email']) || trim((string)$data['email']) === '') {
    http_response_code(400);
    $response['error'] = 'Email is required.';
    echo json_encode($response);
    exit;
}

$email = trim((string)$data['email']);

$result = subscribeNewsletter($pdo, $email);

if (!empty($result['error'])) {
    http_response_code(400);
    $response['error'] = $result['error'];
    echo json_encode($response);
    exit;
}

$response['success'] = true;
$response['message'] = $result['message'];

echo json_encode($response);
exit;
