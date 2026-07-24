<?php

include_once "Connect.php";

if (isset($_SESSION['user_id'])) {
    $userId = (int)$_SESSION['user_id'];
    // full name
    $userName = (string)$_SESSION['user_name'];
    // getting the first name
    $firstName = explode(" ", $userName);
    $firstName = (string)$firstName[0];
    $firstInitial = (string)$firstName[0];

    $userEmail = (string)$_SESSION['user_email'];
    $userPhone = (string)$_SESSION['user_phone'];
    $userCredits = (float)$_SESSION['user_credit'];
    $isMember = (bool)$_SESSION['is_member'];
    $userTimeRegister = (string)$_SESSION['user_time_register'];
} else {
    $userId = 0;
    // full name
    $userName = "";
    // getting the first name
    $firstName = "";
    $firstName = "";
    $firstInitial = "";

    $userEmail = "";
    $userPhone = "";
    $userCredits = 0.00;
    $isMember = false;
    $userTimeRegister = "";
}

$sql = "SELECT * FROM UserAddresses WHERE UserId = ? ORDER BY DateAdded DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    $userAddressId = $row['Id'];
    $userLabel = $row['Label'];
    $userAddress = $row['Address'];
    $userUnit = $row['Apt'];
    $userCity = $row['City'];
    $userState = $row['State'];
    $userZip = $row['ZipCode'];
    $coordinate = $row['Coordinate'];
    $userGateCode = $row['GateCode'];
    $userNote = $row['Note'];
    $Coordinates = $row['LatnLong'];
    $userPhoneOnAddress = $row['Phone'];
    break;
}
