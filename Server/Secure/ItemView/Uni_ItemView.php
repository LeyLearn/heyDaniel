<?php
include_once "../connect.php";
include_once "../Function/Func_Total.php";
header('Content-Type: application/json; charset=utf-8');

$rawInput = file_get_contents('php://input');

$response = [
  "data"  => [],
  "error" => null
];

$userId = 0;

if ($rawInput === false || $rawInput === "" || strlen($rawInput) > 1000) {
  http_response_code(400);
  $response["error"] = "Invalid input data.";
  echo json_encode($response);
  exit();
}

$data = json_decode($rawInput, true);
if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  $response['error'] = "Malformed JSON input.";
  echo json_encode($response);
   exit();
}

// validate device type 
if (!isset($data['device_type']) || !is_string($data['device_type']) || $data['device_type'] === "") {
  http_response_code(400);
  $response['error'] = "Invalid or missing device type.";
  echo json_encode($response);
   exit();
}

$deviceType = trim($data['device_type']);
$allowedDevices = ["Web", "iOS", "Android"];
if (!in_array($deviceType, $allowedDevices, true)) {
  http_response_code(400);
  $response['error'] = "Unsupported device type.";
  echo json_encode($response);
   exit();
}

// validate signature
$signature = $data['device_signature'];
if (
    !isset($data['device_signature'])
  || !is_string($data['device_signature'])
  || $signature === ''
  || strlen($signature) > 128
  || !preg_match('/^[a-zA-Z0-9-]+$/', $signature)
) {
  http_response_code(400);
  $response['error'] = "Invalid or missing device signature.";
  echo json_encode($response);
  exit();
}


// validate user id;
if ($deviceType === "Web") {
  session_start();
  $userId = isset($_SESSION['UserId']) ? intval($_SESSION['UserId']) : 0;
} else {
  if (!isset($data['user_id']) || !is_int($data['user_id'])) {
    http_response_code(400);
    $response['error'] = "Invalid or missing user ID.";
    echo json_encode($response);
     exit();
  }
  $userId = (int)$data['user_id'];
}

$productData = resolveDevice($pdo, $signature, 'RecentlyViewed', $userId);

if (isset($productData['error'])) {
    http_response_code(400); 
    $response['error'] = $productData['error'];
    echo json_encode($response);
    exit();
}

$response['data'] = $productData;
echo json_encode($response);
exit();
