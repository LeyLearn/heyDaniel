<?php
include_once "../connect.php";
include_once "../Function/Func_Total.php";

$response = [
  "data"  => [],
  "error" => null
];
include_once "../../Function/Auth/ArrayAuth.php";

$deviceTypeData = authDeviceType($data);
if (!empty($deviceTypeData['error'])) {
    http_response_code(400);
    $response['error'] = $deviceTypeData['error'];
    echo json_encode($response);
    exit;
}
$userId = $deviceTypeData['user_id'];
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


$productData = resolveDevice($pdo, $signature, 'RecentlyViewed', $userId);

if ($productData['error'] !== "" || $productData['error'] !== null) {
    http_response_code(400); 
    $response['error'] = $productData['error'];
    echo json_encode($response);
    exit();
}

$response['data'] = $productData;
echo json_encode($response);
exit();
