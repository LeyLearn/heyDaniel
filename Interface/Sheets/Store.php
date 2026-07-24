<?php
$path = "../../";
$pageTitle = "Store - HeyDaniel";
$metaDescription = "Browse our store and get same-day delivery with HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>
<body>
    <?php require $path . "Interface/Sections/Header.php"; ?>

    <nav class="Store_Tabs">
        <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Store_Tab Active">Shop</a>
        <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Store_Tab">Categories</a>
        <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Store_Tab">Deals</a>
        <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Store_Tab">New Arrivals</a>
        <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Store_Tab">Brands</a>
    </nav>

    <section class="Container Store_Layout">
        <aside class="Store_Filters">
            <h2>Categories</h2>

            <div class="Store_Category_List" id="store-category-list">
                <button type="button" class="Store_Category_Item Btn_Nav_Row Active" data-value="">All Categories</button>
            </div>

            <div class="Store_Filter_Group" id="store-subcategory-group" style="display:none;">
                <label for="store-filter-sub-category">Subcategory</label>
                <select id="store-filter-sub-category" disabled>
                    <option value="">All Subcategories</option>
                </select>
            </div>

            <div class="Store_Filter_Group" id="store-thirdcategory-group" style="display:none;">
                <label for="store-filter-third-category">Type</label>
                <select id="store-filter-third-category" disabled>
                    <option value="">All Types</option>
                </select>
            </div>

            <div class="Store_Filter_Group">
                <label for="store-filter-brand">Brand</label>
                <select id="store-filter-brand">
                    <option value="">All Brands</option>
                </select>
            </div>

            <div class="Store_Filter_Group Store_Filter_Checkboxes">
                <label>
                    <input type="checkbox" id="store-filter-sale" />
                    On Sale
                </label>
                <label>
                    <input type="checkbox" id="store-filter-bogo" />
                    BOGO
                </label>
            </div>

            <button type="button" class="Secondary_Light_Btn" id="store-filter-clear">Clear filters</button>
        </aside>

        <div class="Store_Results">
            <div class="Section_Header">
                <h2>Best Sellers</h2>
            </div>
            <div class="Row" id="store-grid">
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
        </div>
    </section>

    <section class="Container" id="store-similar-section" style="display:none;">
        <div class="Section_Header">
            <h2>You might also like</h2>
        </div>
        <div class="Row">
            <div class="product-slider-wrap">
                <button class="Slider_Arrow Slider_Arrow_Prev" type="button" aria-label="Scroll left">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-right.svg" alt="" />
                </button>
                <div class="product-slider" id="store-similar-products"></div>
                <button class="Slider_Arrow Slider_Arrow_Next" type="button" aria-label="Scroll right">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-left.svg" alt="" />
                </button>
            </div>
        </div>
    </section>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            function currentFilter() {
                return {
                    MainCategory: $("#store-category-list .Store_Category_Item.Active").data("value") || "",
                    SubCategory: $("#store-filter-sub-category").val(),
                    ThirdCategory: $("#store-filter-third-category").val(),
                    Brand: $("#store-filter-brand").val(),
                    isOnSale: $("#store-filter-sale").is(":checked") ? 1 : '',
                    isBogo: $("#store-filter-bogo").is(":checked") ? 1 : ''
                };
            }

            function loadCategoryList() {
                $.ajax({
                    method: "POST",
                    url: "/HeyDaniel/Server/index.php",
                    contentType: "application/json",
                    dataType: "json",
                    data: JSON.stringify({ action: "main_categories", device_type: "Web" }),
                    success: function (data) {
                        if (data.message || !data.categories) {
                            return;
                        }

                        const $list = $("#store-category-list");
                        data.categories.forEach(function (category) {
                            $list.append(
                                $('<button type="button" class="Store_Category_Item Btn_Nav_Row"></button>')
                                    .attr("data-value", category.name)
                                    .text(category.name)
                            );
                        });
                    },
                    error: function (xhr) {
                        const res = JSON.parse(xhr.responseText);
                        console.log("main_categories failed:", res.error);
                    }
                });
            }

            loadCategoryList();
            store(currentFilter());

            $("#store-category-list").on("click", ".Store_Category_Item", function () {
                const mainCategory = $(this).data("value") || "";

                $("#store-category-list .Store_Category_Item").removeClass("Active");
                $(this).addClass("Active");

                $("#store-filter-sub-category").prop("disabled", true).find("option:not(:first)").remove();
                $("#store-filter-third-category").prop("disabled", true).find("option:not(:first)").remove();
                $("#store-subcategory-group, #store-thirdcategory-group").hide();

                if (mainCategory) {
                    subCategories(mainCategory);
                    $("#store-subcategory-group").show();
                }

                store(currentFilter());
            });

            $("#store-filter-sub-category").on("change", function () {
                const subCategory = $(this).val();

                $("#store-filter-third-category").prop("disabled", true).find("option:not(:first)").remove();
                $("#store-thirdcategory-group").hide();

                if (subCategory) {
                    thirdCategories(subCategory);
                    $("#store-thirdcategory-group").show();
                }

                store(currentFilter());
            });

            $("#store-filter-third-category, #store-filter-brand, #store-filter-sale, #store-filter-bogo").on("change", function () {
                store(currentFilter());
            });

            $("#store-filter-clear").on("click", function () {
                $("#store-category-list .Store_Category_Item").removeClass("Active");
                $("#store-category-list .Store_Category_Item[data-value='']").addClass("Active");
                $("#store-filter-sub-category").val("").prop("disabled", true).find("option:not(:first)").remove();
                $("#store-filter-third-category").val("").prop("disabled", true).find("option:not(:first)").remove();
                $("#store-subcategory-group, #store-thirdcategory-group").hide();
                $("#store-filter-brand").val("");
                $("#store-filter-sale").prop("checked", false);
                $("#store-filter-bogo").prop("checked", false);

                store(currentFilter());
            });
        });
    </script>

</body>

</html>
