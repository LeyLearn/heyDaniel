<?php
$path = "../../";
$pageTitle = "Profile - HeyDaniel";
$metaDescription = "Manage your account and orders on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php
    if (isset($_SESSION['user_id'])) {
        require $path . "Interface/Sections/Profile.php";
    } else {
        require $path . "Interface/Sections/Credential.php";
    }
    require $path . "Interface/Sections/Footer.php";
    ?>


</body>

</html>