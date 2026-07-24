<?php

include_once __DIR__ . "/../../Connect.php";
include_once __DIR__ . "/../../Function/Components.php";

$userId = requireAuthenticatedUser($data);

$addressId = (int)($data['address_id'] ?? 0);
$address = [
    'Label'    => $data['label'] ?? '',
    'Address'  => $data['address'] ?? '',
    'Apt'      => $data['apt'] ?? '',
    'City'     => $data['city'] ?? '',
    'State'    => $data['state'] ?? '',
    'ZipCode'  => $data['zip'] ?? '',
    'GateCode' => $data['gate_code'] ?? '',
    'Note'     => $data['note'] ?? '',
    'Phone'    => $data['phone'] ?? ''
];

$result = saveAddress($pdo, $userId, $address, $addressId);

if (!$result['success']) {
    http_response_code(400);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['success' => true, 'address_id' => $result['address_id']]);
exit;
