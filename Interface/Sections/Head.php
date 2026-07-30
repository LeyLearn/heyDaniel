<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Appends the file's last-modified time as a cache-busting query string, so
// browsers always fetch a fresh copy after a local CSS/JS edit instead of
// serving a stale cached version.
function asset_v($relativePath, $path)
{
    $fsPath = __DIR__ . '/../../' . $relativePath;
    $version = file_exists($fsPath) ? filemtime($fsPath) : time();
    return $path . $relativePath . '?v=' . $version;
}
?>
<head>
  <!-- meta link -->
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="author" content="HeyDaniel">
  <meta name="description" content="<?= isset($metaDescription) ? $metaDescription : 'HeyDaniel - Your online shopping destination.' ?>">
  <script>
    // Applied before any stylesheet loads, so there's no flash of the wrong
    // theme on page load/navigation - every page in this app is a separate
    // full server render, not a SPA, so this has to re-run on every single
    // page rather than once. Falls back to the OS-level preference when the
    // user hasn't picked one explicitly yet; toggle button is in Header.php,
    // click handler in Interaction.js.
    (function () {
      var stored = localStorage.getItem("hd-theme");
      var theme = stored || (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light");
      if (theme === "dark") {
        document.documentElement.setAttribute("data-theme", "dark");
      }
    })();
  </script>
  <script>
    window.isLoggedIn = false;
    window.isloggedin = false;
    window.isMember = false;
  </script>
  <!-- script files -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
  <script src="https://accounts.google.com/gsi/client" defer></script>
  <script type="text/javascript" src="<?= asset_v('Client/Component.js', $path) ?>"></script>
  <script type="text/javascript" src="<?= asset_v('Client/Interaction.js', $path) ?>"></script>
  <!-- fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;550;600;700&display=swap" rel="stylesheet">
  <!-- css files -->
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/Header.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/Button.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/Input.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/Card.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/Main.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/Footer.css', $path) ?>">

  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Credential.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Cart.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Saved.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Store.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Checkout.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Confirmation.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Profile.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Orders.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Addresses.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/PaymentMethods.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Settings.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Notifications.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/HelpSupport.css', $path) ?>">
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sheets/Item.css', $path) ?>">

  <!-- design system preview: remove this line to revert -->
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/DesignSystem.css', $path) ?>">
  <!-- luxury design system preview: remove this line to revert -->
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/DesignSystemLux.css', $path) ?>">
  <!-- lux motion preview: remove this line to revert -->
  <link rel="stylesheet" type="text/css" href="<?= asset_v('Css/Sections/DesignSystemMotion.css', $path) ?>">
  <!-- for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
  <?php
    include_once $path . "Server/Status.php";
    ?>

  <title><?= isset($pageTitle) ? $pageTitle : "Home - HeyDaniel"; ?></title>
</head>