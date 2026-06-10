<?php
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');

if ($rawInput === false || $rawInput === "" || strlen($rawInput) > 2000) {
    http_response_code(400);
    $response['error'] = "Invalid input data";
    echo json_encode($response);
    exit;
}

$data = json_decode($rawInput, true);
if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $response['error'] = "Malformed json input";
    echo json_encode($response);
    exit;
}