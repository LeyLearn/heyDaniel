<?php
$path = "../../";
$pageTitle = "Item - HeyDaniel";
$metaDescription = "View product details on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>
<body>
    <?php require $path . "Interface/Sections/Header.php"; ?>

    <section class="Container Item_Layout">
        <div id="item-loading" class="Item_Main">
            <div class="Skeleton_Block" style="width:420px;max-width:100%;height:420px;border-radius:8px;flex-shrink:0;"></div>
            <div style="flex:1;display:flex;flex-direction:column;gap:14px;">
                <div class="Skeleton_Block" style="width:30%;height:10px;"></div>
                <div class="Skeleton_Block" style="width:70%;height:28px;"></div>
                <div class="Skeleton_Block" style="width:40%;height:14px;"></div>
                <div class="Skeleton_Block" style="width:25%;height:26px;"></div>
                <div class="Skeleton_Block" style="width:50%;height:14px;"></div>
                <div class="Skeleton_Block" style="width:100%;height:50px;border-radius:5px;margin-top:10px;"></div>
            </div>
        </div>
        <p id="item-not-found" class="Store_Empty" style="display:none;">Product not found.</p>

        <div id="item-content" style="display:none;">
            <a href="/HeyDaniel/Interface/Sheets/Store" class="Item_Back_Link">
                <img src="<?php echo $path ?>Assets/Icons/chevron-left.svg" alt="" /> Back to Store
            </a>

            <div class="Item_Main">
                <div class="Item_Image_Wrap" id="item-image-wrap"></div>

                <div class="Item_Info">
                    <p class="Item_Breadcrumb" id="item-breadcrumb"></p>
                    <p class="Product_Card_Brand" id="item-brand"></p>
                    <h1 id="item-name"></h1>
                    <p class="Product_Card_Rating" id="item-rating"></p>
                    <p class="Product_Card_Price" id="item-price"></p>
                    <p class="Product_Card_Meta" id="item-meta"></p>

                    <div class="Item_Size_Select" id="item-size-wrap" style="display:none;">
                        <label for="item-size">Size</label>
                        <select id="item-size" disabled></select>
                    </div>

                    <div class="Item_Actions" id="item-cart-control"></div>
                    <p class="Item_Wishlist_Link" id="item-wishlist-link"></p>
                    <p class="Item_Description" id="item-description"></p>

                    <ul class="Trust_Badges Item_Trust_Badges">
                        <li><img src="<?php echo $path ?>Assets/Icons/lock.svg" alt="" /> Secure Payment</li>
                        <li><img src="<?php echo $path ?>Assets/Icons/check.svg" alt="" /> Quality Products</li>
                        <li><img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" /> Fast Delivery</li>
                    </ul>
                </div>
            </div>

            <section class="Item_Reviews">
                <div class="Section_Header">
                    <h2>Reviews</h2>
                </div>

                <div class="Item_Reviews_Summary" id="item-reviews-summary"></div>

                <?php if ($userId !== 0) { ?>
                    <form class="Item_Review_Form" id="item-review-form">
                        <p class="credential-error" id="item-review-error" style="display:none;"></p>

                        <div class="Item_Review_Field">
                            <label>Your rating</label>
                            <div class="Item_Star_Picker" data-field="stars">
                                <?php for ($i = 1; $i <= 5; $i++) { ?>
                                    <button type="button" class="Star_Btn" data-value="<?php echo $i ?>">&#9733;</button>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="Item_Review_Field">
                            <label>Did it meet your expectations?</label>
                            <div class="Item_Star_Picker" data-field="expectation">
                                <?php for ($i = 1; $i <= 5; $i++) { ?>
                                    <button type="button" class="Star_Btn" data-value="<?php echo $i ?>">&#9733;</button>
                                <?php } ?>
                            </div>
                        </div>

                        <div class="Item_Review_Field">
                            <label for="item-review-title">Title</label>
                            <input type="text" id="item-review-title" maxlength="255" placeholder="Summarize your experience" />
                        </div>

                        <div class="Item_Review_Field">
                            <label for="item-review-text">Review</label>
                            <textarea id="item-review-text" rows="4" placeholder="What did you like or dislike?"></textarea>
                        </div>

                        <button type="submit" class="Submit_Review_Btn">Submit review</button>
                    </form>
                <?php } else { ?>
                    <p class="Item_Review_Prompt">
                        <a href="/HeyDaniel/Interface/Sheets/Credential">Sign in</a> to write a review.
                    </p>
                <?php } ?>

                <div id="item-reviews-list"></div>
                <button type="button" class="Text_Btn" id="item-reviews-more" style="display:none;">Show more reviews</button>
            </section>
        </div>
    </section>

    <section class="Container" id="item-related-section" style="display:none;">
        <div class="Section_Header">
            <h2>You might also like</h2>
        </div>
        <div class="Row">
            <div class="product-slider-wrap">
                <button class="Slider_Arrow Slider_Arrow_Prev" type="button" aria-label="Scroll left">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-right.svg" alt="" />
                </button>
                <div class="product-slider" id="item-related-products"></div>
                <button class="Slider_Arrow Slider_Arrow_Next" type="button" aria-label="Scroll right">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-left.svg" alt="" />
                </button>
            </div>
        </div>
    </section>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            const productId = parseInt(new URLSearchParams(window.location.search).get("id"), 10);

            if (!productId || productId <= 0) {
                $("#item-loading").hide();
                $("#item-not-found").show();
            } else {
                productDetail(productId);
            }

            $("#item-reviews-more").on("click", function () {
                const $btn = $(this);
                getReviews(productId, $btn.data("nextPage") || 2, 5);
            });

            $(document).on("click", ".Item_Star_Picker .Star_Btn", function () {
                const $picker = $(this).closest(".Item_Star_Picker");
                const value = $(this).data("value");

                $picker.data("value", value);
                $picker.find(".Star_Btn").each(function () {
                    $(this).toggleClass("selected", $(this).data("value") <= value);
                });
            });

            $("#item-review-form").on("submit", function (e) {
                e.preventDefault();

                const stars = $(".Item_Star_Picker[data-field='stars']").data("value") || 0;
                const expectation = $(".Item_Star_Picker[data-field='expectation']").data("value") || 0;
                const title = $("#item-review-title").val().trim();
                const review = $("#item-review-text").val().trim();
                const $error = $("#item-review-error");

                if (!stars || !expectation || !title || !review) {
                    $error.text("Please fill out a star rating, expectation rating, title, and review.").show();
                    return;
                }

                $error.hide();
                addReview(productId, stars, expectation, title, review);
            });
        });
    </script>

</body>

</html>
