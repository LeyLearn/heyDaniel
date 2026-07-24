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

    <section class="Container Profile_Layout">
        <?php $activeAccountNav = 'wishlist'; require $path . "Interface/Sections/AccountNav.php"; ?>

        <div class="Profile_Main">
            <div class="Profile_Section">
                <div class="Section_Header Wishlist_Section_Header">
                    <h2>Your Wishlist <span class="Section_Header_Count" id="saved-item-count"></span></h2>
                    <button type="button" class="Secondary_Light_Btn Move_All_Btn" id="saved-move-all-btn" style="display:none;">Move all to cart</button>
                </div>
                <p id="saved-message" style="display:none;"></p>
                <div class="Row" id="saved-items-container">
                    <?php for ($i = 0; $i < 6; $i++) { ?>
                        <div class="Skeleton_Product_Card">
                            <div class="Skeleton_Block Skeleton_Product_Card_Image"></div>
                            <div class="Skeleton_Product_Card_Content">
                                <div class="Skeleton_Block"></div>
                                <div class="Skeleton_Block"></div>
                                <div class="Skeleton_Block"></div>
                                <div class="Skeleton_Block"></div>
                                <div class="Skeleton_Block"></div>
                                <div class="Skeleton_Block"></div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="List_Pagination" id="saved-pagination" style="display:none;">
                    <span class="List_Pagination_Label" id="saved-pagination-label"></span>
                    <div class="List_Pagination_Pages">
                        <button type="button" class="List_Page_Btn List_Page_Nav" id="saved-page-prev" aria-label="Previous page">
                            <i class="fas fa-chevron-left" aria-hidden="true"></i>
                        </button>
                        <span class="List_Pagination_Numbers" id="saved-pagination-numbers"></span>
                        <button type="button" class="List_Page_Btn List_Page_Nav" id="saved-page-next" aria-label="Next page">
                            <i class="fas fa-chevron-right" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            savedItems();

            $("#saved-move-all-btn").on("click", function () {
                moveAllSavedToCart();
            });

            $("#profile-logout-btn").on("click", function () {
                logout();
            });

            $("#saved-pagination-numbers").on("click", ".List_Page_Btn", function () {
                setSavedPage(parseInt($(this).data("page"), 10));
            });

            $("#saved-page-prev").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setSavedPage(savedPageCurrentPage - 1);
                }
            });

            $("#saved-page-next").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setSavedPage(savedPageCurrentPage + 1);
                }
            });
        });
    </script>

</body>

</html>
