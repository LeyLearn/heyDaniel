<?php
$path = "../../";
$pageTitle = "Saved Items - HeyDaniel";
$metaDescription = "View your saved items on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php require $path . "Interface/Sections/Header.php"; ?>

    <section class="Container">
        <div class="Section_Header">
            <h2>Your Wishlist <span class="Section_Header_Count" id="saved-item-count"></span></h2>
            <button type="button" class="Secondary_Light_Btn Move_All_Btn" id="saved-move-all-btn" style="display:none;">Move all to cart</button>
        </div>
        <p id="saved-message" style="display:none;"></p>
        <div class="Row_Card_List" id="saved-items-container"></div>
    </section>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            savedItems();

            $("#saved-move-all-btn").on("click", function () {
                moveAllSavedToCart();
            });
        });
    </script>

</body>

</html>
