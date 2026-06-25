<?php

function respondWithMsg($message, $statusCode = 400) {
    http_response_code($statusCode);
    echo json_encode([
        'data'    => null,
        'message' => $message,
        'error'   => null
    ]);
    exit;
}

function respondWithError($error, $statusCode = 500) {
    http_response_code($statusCode);
    echo json_encode([
        'data'    => null,
        'message' => null,
        'error'   => $error
    ]);
    exit;
}

function respondSuccess($data) {
    http_response_code(200);
    echo json_encode([
        'data'    => $data,
        'message' => null,
        'error'   => null
    ]);
    exit;
}
