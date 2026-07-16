<?php
session_start();
?>
<head>
  <!-- meta link -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="HeyDaniel">
  <meta name="description" content="<?= isset($metaDescription) ? $metaDescription : 'HeyDaniel - Your online shopping destination.' ?>">
  <!-- script files -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  <script src="https://accounts.google.com/gsi/client" defer></script>
  <script type="text/javascript" src="<?= $path ?>Client/Component.js"></script>
  <script type="text/javascript" src="<?= $path ?>Client/Interaction.js"></script>
  <!-- css files -->
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Header.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Button.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Input.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Category.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Filter.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Loading.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Icon.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Card.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Pictures.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Ad.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Main.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sections/Footer.css">

  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Credential.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Cart.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Saved.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Store.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Checkout.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Confirmation.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Profile.css">
  <link rel="stylesheet" type="text/css" href="<?= $path ?>Css/Sheets/Item.css">
  <!-- for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <?php
    include_once $path . "Server/Status.php";
    ?>

  <title><?= isset($pageTitle) ? $pageTitle : "Home - HeyDaniel"; ?></title>
  <!-- <link rel="icon" href="Images/favicon.ico" type="image/x-icon"> -->
</head>