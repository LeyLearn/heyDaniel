<?php
$path = "../../";
$pageTitle = "My Orders - HeyDaniel";
$metaDescription = "View your order history on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php if (isset($_SESSION['user_id'])) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <section class="Container Profile_Layout">
            <?php $activeAccountNav = 'orders'; require $path . "Interface/Sections/AccountNav.php"; ?>

            <div class="Profile_Main">
                <div class="Profile_Section">
                    <div class="Orders_Page_Header">
                        <div class="Orders_Page_Header_Text">
                            <h1>My Orders</h1>
                            <p class="Orders_Page_Subtitle">Track, manage and reorder your purchases.</p>
                        </div>
                        <div class="Orders_Page_Header_Actions">
                            <div class="Orders_Search_Wrap">
                                <img src="<?php echo $path ?>Assets/Icons/search.svg" alt="" class="Orders_Search_Icon" />
                                <input type="text" id="orders-search-input" class="Orders_Search_Input" placeholder="Search orders..." />
                            </div>
                            <div class="Orders_Sort_Wrap">
                                <button type="button" class="Orders_Filter_Btn" id="orders-sort-toggle">
                                    <img src="<?php echo $path ?>Assets/Icons/filter.svg" alt="" />
                                    <span>Filter</span>
                                </button>
                                <div class="Orders_Sort_Dropdown" id="orders-sort-dropdown" style="display:none;">
                                    <button type="button" class="Orders_Sort_Option Btn_Nav_Row active" data-sort="newest">Newest First</button>
                                    <button type="button" class="Orders_Sort_Option Btn_Nav_Row" data-sort="oldest">Oldest First</button>
                                    <button type="button" class="Orders_Sort_Option Btn_Nav_Row" data-sort="highest">Highest Total</button>
                                    <button type="button" class="Orders_Sort_Option Btn_Nav_Row" data-sort="lowest">Lowest Total</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="Order_Status_Tabs" id="order-status-tabs">
                        <button type="button" class="Filter_Btn Secondary_Light_Btn active" data-status="all">All Orders <span class="Filter_Btn_Count" id="tab-count-all">0</span></button>
                        <button type="button" class="Filter_Btn Secondary_Light_Btn" data-status="pending">Pending <span class="Filter_Btn_Count" id="tab-count-pending">0</span></button>
                        <button type="button" class="Filter_Btn Secondary_Light_Btn" data-status="processing">Processing <span class="Filter_Btn_Count" id="tab-count-processing">0</span></button>
                        <button type="button" class="Filter_Btn Secondary_Light_Btn" data-status="shipped">Shipped <span class="Filter_Btn_Count" id="tab-count-shipped">0</span></button>
                        <button type="button" class="Filter_Btn Secondary_Light_Btn" data-status="delivered">Delivered <span class="Filter_Btn_Count" id="tab-count-delivered">0</span></button>
                        <button type="button" class="Filter_Btn Secondary_Light_Btn" data-status="cancelled">Cancelled <span class="Filter_Btn_Count" id="tab-count-cancelled">0</span></button>
                    </div>

                    <div class="Order_Help_Banner">
                        <div class="Order_Help_Banner_Icon_Wrap Icon_Circle">
                            <img src="<?php echo $path ?>Assets/Icons/cart-empty.svg" alt="" class="Order_Help_Banner_Icon" />
                        </div>
                        <div class="Order_Help_Banner_Text">
                            <p class="Order_Help_Banner_Title">Can't find your order?</p>
                            <p class="Order_Help_Banner_Subtitle">Try changing the filter or check another time period.</p>
                        </div>
                        <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Order_Help_Banner_Link">Start Browsing <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                    </div>

                    <div id="orders-empty" class="Order_Empty" style="display:none;">
                        <p class="Order_Empty_Text">No orders found for this filter.</p>
                    </div>

                    <div id="orders-list-container">
                        <?php for ($i = 0; $i < 3; $i++) { ?>
                            <div class="Skeleton_List_Row">
                                <div class="Skeleton_Block Skeleton_List_Row_Icon"></div>
                                <div class="Skeleton_List_Row_Info">
                                    <div class="Skeleton_Block"></div>
                                    <div class="Skeleton_Block"></div>
                                </div>
                                <div class="Skeleton_Block Skeleton_List_Row_Trailing"></div>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="Orders_Pagination" id="orders-pagination" style="display:none;">
                        <span class="Orders_Pagination_Label" id="orders-pagination-label"></span>
                        <div class="Orders_Pagination_Pages" id="orders-pagination-pages">
                            <button type="button" class="Orders_Page_Btn Orders_Page_Nav" id="orders-page-prev" aria-label="Previous page">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                            </button>
                            <span class="Orders_Pagination_Numbers" id="orders-pagination-numbers"></span>
                            <button type="button" class="Orders_Page_Btn Orders_Page_Nav" id="orders-page-next" aria-label="Next page">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    <?php } else {
        require $path . "Interface/Sections/Credential.php";
    } ?>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            initOrdersPage();

            $("#profile-logout-btn").on("click", function () {
                logout();
            });

            initOrderRowInteractions("#orders-list-container");

            $("#order-status-tabs").on("click", ".Filter_Btn", function () {
                $("#order-status-tabs .Filter_Btn").removeClass("active");
                $(this).addClass("active");
                setOrdersStatusFilter($(this).data("status"));
            });

            $("#orders-pagination-numbers").on("click", ".Orders_Page_Btn", function () {
                setOrdersPage(parseInt($(this).data("page"), 10));
            });

            $("#orders-page-prev").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setOrdersPage(ordersPageCurrentPage - 1);
                }
            });

            $("#orders-page-next").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setOrdersPage(ordersPageCurrentPage + 1);
                }
            });

            let searchDebounce = null;
            $("#orders-search-input").on("input", function () {
                const value = $(this).val();
                clearTimeout(searchDebounce);
                searchDebounce = setTimeout(function () {
                    setOrdersSearchQuery(value);
                }, 200);
            });

            $("#orders-sort-toggle").on("click", function (e) {
                e.stopPropagation();
                $("#orders-sort-dropdown").toggle();
            });

            $("#orders-sort-dropdown").on("click", ".Orders_Sort_Option", function () {
                $("#orders-sort-dropdown .Orders_Sort_Option").removeClass("active");
                $(this).addClass("active");
                $("#orders-sort-dropdown").hide();
                setOrdersSort($(this).data("sort"));
            });

            $(document).on("click", function () {
                $("#orders-sort-dropdown").hide();
            });
        });
    </script>
</body>

</html>
