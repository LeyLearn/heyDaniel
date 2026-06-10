<?php

include_once "../connect.php";

$response = [
  "success" => false,
  "JWT" => null,
  "error" => null
];

include_once "../../Function/Auth/ArrayAuth.php";

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
$userPassWord = $data['user_pass'];
$DeviceType = trim($data['device_type']);

if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
  http_response_code(400);
  $response['error'] = "Please enter a valid email!";
  echo json_encode($response);
  exit;
}

$stmt = $pdo->prepare("SELECT * FROM Users WHERE Email = ? LIMIT 1");
$stmt->execute([$userEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($userPassWord, $user['Password'])) {
  http_response_code(401);
  $response['error'] = "Incorrect username or password.";
  echo json_encode($response);
  exit;
}

if ($DeviceType === "Web") {
  session_start();
  $_SESSION['user_id'] = $user['Id'];
  $_SESSION['user_email'] = $user['Email'];
  $_SESSION['user_name'] = $user['Name'];
  $_SESSION['user_credit'] = $user['Credits'];
  $_SESSION['is_member'] = $user['IsMember'];
  $_SESSION['user_phone'] = $user['Phone'];
  $_SESSION['user_address'] = $user['Address'];
  $_SESSION['user_apt'] = $user['Apt'];
  $_SESSION['user_latnlong'] = $user['LatnLong'];
  $_SESSION['user_gatecode'] = $user['GateCode'];
  $_SESSION['user_note'] = $user['Note'];
  $_SESSION['time_member'] = $user['TimeMembership'];
  $_SESSION['user_device'] = $user['WebDevice'];
  $_SESSION['redirectURL'] = $_SERVER['REQUEST_URI'];
  $response['success'] = true;
  echo json_encode($response);
  exit;
}

  $secret = $_ENV['JWT_SECRET'] ?? 'temporary-secret-change-later-12345';

  $header    = base64_encode(json_encode(["alg" => "HS256", "typ" => "JWT"]));

  $payload   = base64_encode(json_encode([
    "Id" => $user['Id'],
    "name" => $user['Name'],
    "email" => $user['Email'],
    "credits" => $user['Credits'],
    "phone" => $user['Phone'],
    "address" => $user['Address'],
    "apt" => $user['Apt'],
    "lat_long" => $user['LatnLong'],
    "gate_code" => $user['GateCode'],
    "note" => $user['Note'],
    "device" => $DeviceType === "Apple" ? $user['AppleDevice'] : $user['AndroidDevice'],
    "is_member" => $user['IsMember'],
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
  
