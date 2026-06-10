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
$query = null;

if($isSameDayEligible){
    $query = "SELECT DISTINCT Pr_Category, Picture FROM Products GROUP BY Pr_Category ORDER BY Id DESC";
}else{
    $query = "SELECT DISTINCT Pr_Category, Picture FROM Products WHERE Pr_Category != 'Grocery' GROUP BY Pr_Category ORDER BY Id DESC";
}

$stmt = $pdo->prepare($query);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
$productData = [];
foreach($results as $row){
        $productData[] = [
        'picture'      => $row['Picture'],
        'pr_category'  => $row['Pr_Category']
    ];
}

$response['data'] = $productData;
echo json_encode($response);
exit;



