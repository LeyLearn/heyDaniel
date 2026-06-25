<?php
include_once "../../Connect.php";
include_once "../../Function/Components.php";
include_once "../../Function/Response.php";

include_once "../../Function/Auth/ArrayAuth.php";

if (!isset($data['device_type'])) {
    respondWithMsg("Device type is required.");
}

$userDeviceType   = $data['device_type'];
$validDeviceTypes = ['iOS', 'Android', 'Web'];

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
    respondWithMsg("Invalid device type.");
}

$userId = 0;
$token  = '';

if ($userDeviceType === 'iOS' || $userDeviceType === 'Android') {
    $userId = (int)($data['user_id'] ?? 0);
    $token  = trim($data['token']    ?? '');

    if (empty($token)) {
        respondWithMsg("Token is required.");
    }
} else {
    session_start();
    $userId = (int)($_SESSION['user_id'] ?? 0);
}

if ($userId <= 0) {
    respondWithMsg("Unauthorized.", 401);
}

$logout = logoutUser($pdo, $userId, $token);

if (!empty($logout['error'])) {
    respondWithMsg($logout['error']);
}

respondSuccess(['success' => true]);
