<?php
$path = "../../";
$pageTitle = "Saved Items - HeyDaniel";
$metaDescription = "View your saved items on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php
    require $path . "Interface/Sections/Header.php";
    ?>

    <main>
        <p id="saved-message"></p>
        <div id="saved-items-container"></div>
    </main>

    <script>
        $(document).ready(function () {
            savedItems();
        });
    </script>

</body>

</html>