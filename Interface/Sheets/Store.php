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

    <section class="Store_Page">
      <div class="Container Store_Layout">

        <!-- ========== SIDEBAR FILTERS ========== -->
        <aside class="Store_Sidebar" id="store-sidebar">
          <div class="Store_Sidebar_Header">
            <h2 class="Store_Sidebar_Title">Filters</h2>
            <button type="button" class="Btn_Icon_Ghost Store_Sidebar_Close" id="store-sidebar-close" aria-label="Close filters">
              <i class="fas fa-times"></i>
            </button>
          </div>

          <div class="Store_Filter_Group">
            <label class="Store_Filter_Label">Category</label>
            <select id="store-filter-main-category" class="Store_Select">
              <option value="">All Categories</option>
            </select>
          </div>

          <div class="Store_Filter_Group">
            <label class="Store_Filter_Label">Sub Category</label>
            <select id="store-filter-sub-category" class="Store_Select" disabled>
              <option value="">Select sub category</option>
            </select>
          </div>

          <div class="Store_Filter_Group">
            <label class="Store_Filter_Label">Type</label>
            <select id="store-filter-third-category" class="Store_Select" disabled>
              <option value="">Select type</option>
            </select>
          </div>

          <div class="Store_Filter_Group">
            <label class="Store_Filter_Label">Brand</label>
            <select id="store-filter-brand" class="Store_Select">
              <option value="">All Brands</option>
            </select>
          </div>

          <div class="Store_Filter_Group">
            <label class="Store_Filter_Label">Price</label>
            <div class="Store_Price_Range">
              <input type="number" id="store-filter-min-price" class="Store_Input" placeholder="Min" min="0">
              <span class="Store_Price_Sep">–</span>
              <input type="number" id="store-filter-max-price" class="Store_Input" placeholder="Max" min="0">
            </div>
          </div>

          <div class="Store_Filter_Group">
            <label class="Store_Filter_Label">Specials</label>
            <div class="Store_Checkbox_List">
              <label class="Store_Checkbox">
                <input type="checkbox" id="store-filter-sale">
                <span>On Sale</span>
              </label>
              <label class="Store_Checkbox">
                <input type="checkbox" id="store-filter-bogo">
                <span>BOGO</span>
              </label>
            </div>
          </div>

          <div class="Store_Sidebar_Actions">
            <button type="button" class="Secondary_Light_Btn Secondary_Light_Btn--full" id="store-filter-reset">
              Clear all
            </button>
            <button type="button" class="Primary_Btn Primary_Btn--full" id="store-filter-apply">
              Apply filters
            </button>
          </div>
        </aside>

        <!-- ========== MAIN CONTENT ========== -->
        <div class="Store_Main">

          <!-- Category drilldown: Main -> Sub -> Third, kept in sync with the
               sidebar dropdowns (clicking a circle drives the matching select;
               changing a select re-renders the circles for that level). -->
          <div class="Store_Category_Circles" id="store-category-circles" style="display:none;">
            <button type="button" class="Store_Circle_Back" id="store-circle-back" style="display:none;" aria-label="Back">
              <i class="fas fa-chevron-left"></i>
            </button>
            <div class="Store_Circle_Row" id="store-circle-row"></div>
          </div>

          <!-- Top bar -->
          <div class="Store_Topbar">
            <div class="Store_Topbar_Left">
              <button type="button" class="Secondary_Light_Btn Store_Filter_Toggle" id="store-filter-toggle">
                <i class="fas fa-sliders-h"></i>
                <span>Filters</span>
              </button>
              <p class="Store_Results_Count" id="store-results-count">Loading products…</p>
            </div>

            <div class="Store_Topbar_Right">
              <label class="Store_Sort_Label" for="store-sort">Sort by</label>
              <select id="store-sort" class="Store_Select Store_Select--sm">
                <option value="featured">Featured</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
                <option value="name_asc">Name: A–Z</option>
                <option value="newest">Newest</option>
              </select>
            </div>
          </div>

          <!-- Active filter chips -->
          <div class="Store_Active_Filters" id="store-active-filters" style="display:none;">
            <!-- chips injected by JS -->
          </div>

          <!-- Product grid -->
          <div class="Store_Grid" id="store-grid">
            <!-- Product cards rendered by JS -->
            <!-- Skeleton placeholders while loading -->
            <?php for ($i = 0; $i < 8; $i++): ?>
              <div class="Store_Skeleton_Card">
                <div class="Skeleton_Block Store_Skeleton_Image"></div>
                <div class="Store_Skeleton_Body">
                  <div class="Skeleton_Block" style="width:40%;height:12px;"></div>
                  <div class="Skeleton_Block" style="width:80%;height:14px;margin-top:8px;"></div>
                  <div class="Skeleton_Block" style="width:50%;height:14px;margin-top:8px;"></div>
                </div>
              </div>
            <?php endfor; ?>
          </div>

          <!-- Empty state -->
          <div class="Store_Empty_State" id="store-empty" style="display:none;">
            <div class="Store_Empty_Icon">
              <img src="<?php echo $path ?>Assets/Icons/search.svg" alt="">
            </div>
            <h3>No products found</h3>
            <p>Try adjusting your filters or browse all categories.</p>
            <button type="button" class="Primary_Btn" id="store-empty-reset">Clear filters</button>
          </div>

          <!-- Pagination -->
          <div class="Store_Pagination" id="store-pagination" style="display:none;">
            <button type="button" class="Secondary_Light_Btn" id="store-page-prev" disabled>
              <i class="fas fa-chevron-left"></i> Previous
            </button>
            <div class="Store_Pagination_Numbers" id="store-pagination-numbers"></div>
            <button type="button" class="Secondary_Light_Btn" id="store-page-next">
              Next <i class="fas fa-chevron-right"></i>
            </button>
          </div>

          <!-- Similar products (optional) -->
          <section class="Store_Similar" id="store-similar-section" style="display:none;">
            <h3 class="Store_Section_Title">You might also like</h3>
            <div class="Store_Similar_Grid" id="store-similar-products"></div>
          </section>

        </div>
      </div>
    </section>

    <!-- Mobile filter overlay -->
    <div class="Store_Sidebar_Overlay" id="store-sidebar-overlay"></div>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {

            // ---------- Mobile sidebar ----------
            $("#store-filter-toggle").on("click", function () {
                $("#store-sidebar").addClass("Open");
                $("#store-sidebar-overlay").addClass("Open");
            });
            $("#store-sidebar-close, #store-sidebar-overlay").on("click", function () {
                $("#store-sidebar").removeClass("Open");
                $("#store-sidebar-overlay").removeClass("Open");
            });

            // ---------- Category circles (drilldown, synced with the dropdowns) ----------
            // The dropdowns are the source of truth; circles are just a visual
            // reflection of whichever <select> is currently active, rebuilt from
            // its <option>s every time that select's contents or value change.
            // That's what keeps the two in sync in both directions: clicking a
            // circle sets the matching <select>'s value (native change event,
            // same code path as picking it manually), and picking a dropdown
            // option re-renders the circle row for that level.
            function renderCategoryCircles(level) {
                const selectId = level === "main" ? "#store-filter-main-category"
                    : level === "sub" ? "#store-filter-sub-category"
                        : "#store-filter-third-category";

                const $select = $(selectId);
                const $options = $select.find("option:not(:first)");
                const $wrap = $("#store-category-circles");
                const $row = $("#store-circle-row").empty();

                if (!$options.length) {
                    $wrap.hide();
                    return;
                }

                const selectedValue = $select.val();
                $options.each(function () {
                    const value = $(this).val();
                    const icon = $(this).attr("data-icon");
                    $('<button type="button" class="Store_Circle_Item"></button>')
                        .toggleClass("Active", value === selectedValue)
                        .append(
                            $('<span class="Store_Circle_Item_Img"></span>').css("background-image", "url(/HeyDaniel/Assets/Categories/" + icon + ")"),
                            $('<span class="Store_Circle_Item_Label"></span>').text(value)
                        )
                        .on("click", function () {
                            $select.val(value).trigger("change");
                            applyFilters();
                        })
                        .appendTo($row);
                });

                $("#store-circle-back").toggle(level !== "main");
                $wrap.data("level", level).show();
            }

            $("#store-circle-back").on("click", function () {
                const level = $("#store-category-circles").data("level");
                if (level === "sub") {
                    $("#store-filter-main-category").val("").trigger("change");
                } else if (level === "third") {
                    $("#store-filter-sub-category").val("").trigger("change");
                }
                applyFilters();
            });

            // ---------- Category cascade ----------
            $("#store-filter-main-category").on("change", function () {
                const main = $(this).val();

                $("#store-filter-sub-category").prop("disabled", true).find("option:not(:first)").remove();
                $("#store-filter-third-category").prop("disabled", true).find("option:not(:first)").remove();

                if (main) {
                    subCategories(main, function () {
                        renderCategoryCircles("sub");
                    });
                } else {
                    renderCategoryCircles("main");
                }
            });

            $("#store-filter-sub-category").on("change", function () {
                const sub = $(this).val();

                $("#store-filter-third-category").prop("disabled", true).find("option:not(:first)").remove();

                if (sub) {
                    thirdCategories(sub, function () {
                        renderCategoryCircles("third");
                    });
                } else {
                    renderCategoryCircles("sub");
                }
            });

            $("#store-filter-third-category").on("change", function () {
                renderCategoryCircles("third");
            });

            // ---------- Filter chips ----------
            function currentFilter() {
                return {
                    MainCategory: $("#store-filter-main-category").val() || "",
                    SubCategory: $("#store-filter-sub-category").val() || "",
                    ThirdCategory: $("#store-filter-third-category").val() || "",
                    Brand: $("#store-filter-brand").val() || "",
                    isOnSale: $("#store-filter-sale").is(":checked") ? 1 : "",
                    isBogo: $("#store-filter-bogo").is(":checked") ? 1 : ""
                };
                // Note: price range isn't sent — the backend only supports an
                // exact-match Price filter, not a range, so Min/Max here are
                // left as future-facing inputs until that's added server-side.
            }

            function renderActiveFilterChips(filter) {
                const $wrap = $("#store-active-filters");
                $wrap.empty();

                const chips = [];
                if (filter.MainCategory) chips.push(["MainCategory", filter.MainCategory]);
                if (filter.SubCategory) chips.push(["SubCategory", filter.SubCategory]);
                if (filter.ThirdCategory) chips.push(["ThirdCategory", filter.ThirdCategory]);
                if (filter.Brand) chips.push(["Brand", filter.Brand]);
                if (filter.isOnSale) chips.push(["isOnSale", "On Sale"]);
                if (filter.isBogo) chips.push(["isBogo", "BOGO"]);

                if (!chips.length) {
                    $wrap.hide();
                    return;
                }

                chips.forEach(function (chip) {
                    const key = chip[0];
                    const label = chip[1];
                    const $chip = $('<span class="Store_Chip"></span>').text(label);
                    const $remove = $('<button type="button" aria-label="Remove filter">&times;</button>');
                    $remove.on("click", function () {
                        clearSingleFilter(key);
                    });
                    $chip.append($remove);
                    $wrap.append($chip);
                });

                $wrap.show();
            }

            function clearSingleFilter(key) {
                if (key === "MainCategory") {
                    $("#store-filter-main-category").val("");
                    $("#store-filter-sub-category").val("").prop("disabled", true).find("option:not(:first)").remove();
                    $("#store-filter-third-category").val("").prop("disabled", true).find("option:not(:first)").remove();
                    renderCategoryCircles("main");
                } else if (key === "SubCategory") {
                    $("#store-filter-sub-category").val("");
                    $("#store-filter-third-category").val("").prop("disabled", true).find("option:not(:first)").remove();
                    renderCategoryCircles("sub");
                } else if (key === "ThirdCategory") {
                    $("#store-filter-third-category").val("");
                    renderCategoryCircles("third");
                } else if (key === "Brand") {
                    $("#store-filter-brand").val("");
                } else if (key === "isOnSale") {
                    $("#store-filter-sale").prop("checked", false);
                } else if (key === "isBogo") {
                    $("#store-filter-bogo").prop("checked", false);
                }

                applyFilters();
            }

            function applyFilters() {
                const filter = currentFilter();
                renderActiveFilterChips(filter);
                store(filter, 16);
            }

            // ---------- Apply / Reset ----------
            $("#store-filter-apply").on("click", applyFilters);

            $("#store-sort").on("change", function () {
                // Re-render with the same last-fetched data via a fresh fetch,
                // since we don't cache the previous response client-side.
                applyFilters();
            });

            $("#store-filter-reset, #store-empty-reset").on("click", function () {
                $("#store-filter-main-category, #store-filter-sub-category, #store-filter-third-category, #store-filter-brand").val("");
                $("#store-filter-sub-category, #store-filter-third-category").prop("disabled", true).find("option:not(:first)").remove();
                $("#store-filter-min-price, #store-filter-max-price").val("");
                $("#store-filter-sale, #store-filter-bogo").prop("checked", false);
                $("#store-active-filters").hide().empty();
                renderCategoryCircles("main");
                store({}, 16);
            });

            // ---------- Results count / empty state ----------
            window.updateStoreResultsCount = function (count) {
                const text = count === 0
                    ? "No products found"
                    : count === 1
                        ? "1 product"
                        : count + " products";
                $("#store-results-count").text(text);

                if (count === 0) {
                    $("#store-grid").hide();
                    $("#store-empty").show();
                    $("#store-pagination").hide();
                } else {
                    $("#store-grid").show();
                    $("#store-empty").hide();
                }
            };

            // ---------- Initial load ----------
            // Category links elsewhere (front page circles/CTAs) pass the
            // category via ?category=, box-category "Shop more" links pass
            // ?sale=1 / ?bogo=1 - all applied once the dropdown's options
            // exist so .val() has something to match against. None of these
            // present means the exact same unfiltered load as before.
            const initialParams = new URLSearchParams(window.location.search);
            const initialCategory = initialParams.get("category");
            const initialSale = initialParams.get("sale") === "1";
            const initialBogo = initialParams.get("bogo") === "1";
            const hasInitialFilter = initialCategory || initialSale || initialBogo;

            if (initialSale) {
                $("#store-filter-sale").prop("checked", true);
            }
            if (initialBogo) {
                $("#store-filter-bogo").prop("checked", true);
            }

            mainCategories(function () {
                if (initialCategory) {
                    $("#store-filter-main-category").val(initialCategory);
                }
                renderCategoryCircles("main");
                if (hasInitialFilter) {
                    if (initialCategory) {
                        $("#store-filter-main-category").trigger("change");
                    }
                    applyFilters();
                }
            });

            if (!hasInitialFilter) {
                store({}, 16);
            }
        });
    </script>

</body>

</html>
