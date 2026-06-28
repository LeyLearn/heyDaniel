<?php
include_once __DIR__ . "/../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$response = [
  "JWT" => null,
  "error" => null
];


$requiredFields = ['user_email', 'user_pass', 'device_type'];

foreach ($requiredFields as $field) {
  if (!isset($data[$field]) || !is_string($data[$field]) || trim($data[$field]) === '') {
    http_response_code(400);
    $response['error'] = "Missing or empty field: $field.";
    echo json_encode($response);
    exit;
  }
}

$userEmail = strtolower(trim($data['user_email']));
$userPassword = $data['user_pass'];
$userDeviceType    = $data['device_type'];
$validDeviceTypes  = ['iOS', 'Android', 'Web'];

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  $response['error'] = "Please enter a valid email!";
  echo json_encode($response);
  exit;
}

if (!in_array($userDeviceType, $validDeviceTypes, true)) {
  http_response_code(400);
  $response['error'] = "Invalid device type.";
  echo json_encode($response);
  exit;
}

$user = loginUser($pdo, $userEmail, $userPassword);

if (!empty($user['error'])) {
  http_response_code(400);
  $response['message'] = "Invalid email or password";
  echo json_encode($response);
  exit;
}


if ($userDeviceType === "Web") {
  session_start();
  $_SESSION['user_id']    = $user['user']['user_id'];
  $_SESSION['user_email'] = $user['user']['user_email'];
  $_SESSION['user_name']  = $user['user']['user_name'];
  $_SESSION['user_credit'] = $user['user']['credits'];
  $_SESSION['is_member']  = $user['user']['is_member'];
  $_SESSION['user_phone'] = $user['user']['user_phone'];
  $_SESSION['redirectURL'] = $_SERVER['REQUEST_URI'];
  $response['success'] = true;
  echo json_encode($response);
  exit;
}

$secret = $_ENV['JWT_SECRET'];

$header    = base64_encode(json_encode(["alg" => "HS256", "typ" => "JWT"]));

$payload   = base64_encode(json_encode([
  "user_id"    => $user['user']['user_id'],
  "name"       => $user['user']['user_name'],
  "email"      => $user['user']['user_email'],
  "credits"    => $user['user']['credits'],
  "phone"      => $user['user']['user_phone'],
  "is_member"  => $user['user']['is_member'],
  "exp"        => time() + 3600
]));

$signature = base64_encode(hash_hmac('sha256', "$header.$payload", $secret, true));
$signature = strtr($signature, '+/', '-_');
$signature = rtrim($signature, '=');
$JWT = "$header.$payload.$signature";

$response = array(
  "success" => true,
  "JWT" => $JWT
);

echo json_encode($response);
exit;
