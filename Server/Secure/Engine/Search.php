<?php
include_once "../../Connect.php";
include_once "../../Function/Func_Total.php";

$response = [
    'data' => [],
    'error' => null
];

include_once "../../Function/Auth/ArrayAuth.php";

$userDevice = $data['device_signature'];
$authDevice = authBySig($pdo, $userDevice);

if (!empty($authDevice['error'])) {
    http_response_code(400);
    $response['error'] = $authDevice['error'];
    echo json_encode($response);
    exit;
}

$isSameDayEligible = $authDevice['same_day_eligible'];

$search = trim($data['search']);
if (!isset($data['search']) || !is_string($data['search']) || $data['search'] === "" || strlen($data['search']) > 100 || !preg_match('/^[a-zA-Z0-9 ]+$/u', $search)) {
    http_response_code(400);
    $response['error'] = "Invalid or missing search information";
    echo json_encode($response);
    exit;
}

$inputInfo = "%{$search}%";
$productQuery = null;

if ($isSameDayEligible) {
    $productQuery = "SELECT * FROM Products WHERE (Id LIKE ? OR Brand LIKE ? OR Pr_Category LIKE ? OR Snd_Category LIKE ? OR Thd_Category LIKE ? OR Ext_Category LIKE ? OR Name LIKE ? OR Oz LIKE ? OR Price LIKE ?) LIMIT 7";
} else {
    $productQuery = "SELECT * FROM Products WHERE (Id LIKE ? OR Brand LIKE ? OR Pr_Category LIKE ? OR Snd_Category LIKE ? OR Thd_Category LIKE ? OR Ext_Category LIKE ? OR Name LIKE ? OR Oz LIKE ? OR Price LIKE ?) AND Pr_Category != 'Grocery' LIMIT 7";
}
$stmt = $pdo->prepare($productQuery);
$stmt->execute([$inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo, $inputInfo]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$productData = [];

foreach ($results as $row) {
    $productData[] = [
        'product_id'    => $row['ProductId'],
        'brand'        => $row['Brand'],
        'name'         => $row['Name'],
        'oz'           => $row['Oz'],
        'price'        => $row['Price'],
        'picture'      => $row['Picture'],
        'is_on_sale'     => (bool)$row['isOnSale'],
        'sale_price'    => $row['SalePrice'],
        'is_bogo'       => (bool)$row['isBogo'],
    ];
}

$response['data'] = $productData;
echo json_encode($response);
exit;
