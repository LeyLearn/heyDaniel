<?php
$path = "../../";

require_once __DIR__ . "/../../Server/Connect.php";
require_once __DIR__ . "/../../Server/Function/Components.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// An order that's still active (Pending, being picked, or out for delivery)
// gets its own page/template (Process.php) instead of this file toggling
// between two states client-side.
$activeOrderId = getActiveSameDayOrderId($pdo, (int)($_SESSION['user_id'] ?? 0));
if ($activeOrderId !== null) {
    require __DIR__ . "/Process.php";
    exit;
}

$pageTitle = "Cart - HeyDaniel";
$metaDescription = "Review your cart and proceed to checkout with HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php require $path . "Interface/Sections/Header.php"; ?>

    <section class="Container Profile_Layout">
        <?php $activeAccountNav = 'cart'; require $path . "Interface/Sections/AccountNav.php"; ?>

        <div class="Profile_Main">
            <div class="Cart_Layout">
                <div class="Cart_Items">
                    <div class="Section_Header">
                        <h2><span id="cart-section-title">Your Cart</span> <span class="Section_Header_Count" id="cart-item-count"></span></h2>
                        <button type="button" class="Cart_Clear_Btn" id="cart-clear-btn">
                            <img src="<?php echo $path ?>Assets/Icons/trash.svg" alt="" />
                            <span>Clear</span>
                        </button>
                    </div>
                    <p id="cart-empty-message" class="Store_Empty" style="display:none;">Your cart is empty.</p>

                    <div class="Row_Card_List" id="cart-items-container">
                        <?php for ($i = 0; $i < 3; $i++) { ?>
                            <div class="Skeleton_Row_Card">
                                <div class="Skeleton_Block Skeleton_Row_Card_Image"></div>
                                <div class="Skeleton_Row_Card_Info">
                                    <div class="Skeleton_Block"></div>
                                    <div class="Skeleton_Block"></div>
                                    <div class="Skeleton_Block"></div>
                                </div>
                                <div class="Skeleton_Block Skeleton_Row_Card_Price"></div>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="List_Pagination" id="cart-pagination" style="display:none;">
                        <span class="List_Pagination_Label" id="cart-pagination-label"></span>
                        <div class="List_Pagination_Pages">
                            <button type="button" class="List_Page_Btn List_Page_Nav" id="cart-page-prev" aria-label="Previous page">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                            </button>
                            <span class="List_Pagination_Numbers" id="cart-pagination-numbers"></span>
                            <button type="button" class="List_Page_Btn List_Page_Nav" id="cart-page-next" aria-label="Next page">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="Cart_Summary" id="cart-summary" style="display:none;">
                    <h2 id="cart-summary-title">Order Summary</h2>

                    <div class="Promo_Code_Row" id="cart-promo-row">
                        <div class="Promo_Code_Input_Wrap">
                            <input type="text" id="cart-promo-code" placeholder="Have a promo code?" />
                            <button type="button" class="Cart_Promo_Apply_Btn" id="cart-promo-apply" aria-label="Apply promo code">
                                <i class="fas fa-check" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div id="cart-summary-totals">
                        <div class="Cart_Summary_Row">
                            <span>Subtotal</span>
                            <span class="cart-summary-subtotal">$0.00</span>
                        </div>
                        <div class="Cart_Summary_Row">
                            <span>Delivery Fee</span>
                            <span class="cart-summary-delivery">$0.00</span>
                        </div>

                        <div class="Cart_Summary_Total_Row">
                            <span>Total</span>
                            <span class="cart-summary-total">$0.00</span>
                        </div>
                    </div>

                    <a href="/HeyDaniel/Interface/Sheets/Checkout" class="Cart_Checkout_Btn Btn_Checkout" id="cart-checkout-btn">
                        <i class="fas fa-lock" aria-hidden="true"></i> Proceed to checkout
                    </a>

                    <ul class="Trust_Badges" id="cart-trust-badges">
                        <li>
                            <span class="Trust_Badge_Icon Icon_Circle"><img src="<?php echo $path ?>Assets/Icons/lock.svg" alt="" /></span>
                            <span>Secure Checkout</span>
                        </li>
                        <li>
                            <span class="Trust_Badge_Icon Icon_Circle"><img src="<?php echo $path ?>Assets/Icons/check.svg" alt="" /></span>
                            <span>Quality Products</span>
                        </li>
                        <li>
                            <span class="Trust_Badge_Icon Icon_Circle"><img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" /></span>
                            <span>Fast Delivery</span>
                        </li>
                    </ul>
                </aside>
            </div>
        </div>
    </section>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            cartItem();
            summary();

            $("#profile-logout-btn").on("click", function () {
                logout();
            });

            $("#cart-clear-btn").on("click", function () {
                showAppConfirm("Remove all items from your cart?", function () {
                    clearCart();
                }, { title: "Clear cart", confirmLabel: "Clear cart" });
            });

            $("#cart-promo-apply").on("click", function () {
                showAppAlert("Promo codes are coming soon.");
            });

            $("#cart-pagination-numbers").on("click", ".List_Page_Btn", function () {
                setCartPage(parseInt($(this).data("page"), 10));
            });

            $("#cart-page-prev").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setCartPage(cartPageCurrentPage - 1);
                }
            });

            $("#cart-page-next").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setCartPage(cartPageCurrentPage + 1);
                }
            });
        });
    </script>

</body>

</html>
