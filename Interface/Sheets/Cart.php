<?php
$path = "../../";
$pageTitle = "Cart - HeyDaniel";
$metaDescription = "Review your cart and proceed to checkout with HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php require $path . "Interface/Sections/Header.php"; ?>

    <section class="Container Cart_Layout">
        <div class="Cart_Items">
            <div class="Section_Header">
                <h2>Your Cart <span class="Section_Header_Count" id="cart-item-count"></span></h2>
                <button type="button" class="Text_Btn" id="cart-clear-btn">Clear cart</button>
            </div>
            <p id="cart-empty-message" class="Store_Empty" style="display:none;">Your cart is empty.</p>
            <div class="Row_Card_List" id="cart-items-container"></div>
        </div>

        <aside class="Cart_Summary" id="cart-summary" style="display:none;">
            <h2>Order Summary</h2>

            <div class="Promo_Code_Row">
                <input type="text" id="cart-promo-code" placeholder="Have a promo code?" />
                <button type="button" class="Secondary_Light_Btn" id="cart-promo-apply">Apply</button>
            </div>

            <div class="Cart_Summary_Row">
                <span>Subtotal</span>
                <span class="cart-summary-subtotal">$0.00</span>
            </div>
            <div class="Cart_Summary_Row">
                <span>Delivery Fee</span>
                <span>$0.00</span>
            </div>
            <a href="/HeyDaniel/Interface/Sheets/Checkout.php" class="Primary_Btn">Proceed to checkout</a>

            <ul class="Trust_Badges">
                <li><img src="<?php echo $path ?>Assets/Icons/lock.svg" alt="" /> Secure Checkout</li>
                <li><img src="<?php echo $path ?>Assets/Icons/check.svg" alt="" /> Quality Products</li>
                <li><img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" /> Fast Delivery</li>
            </ul>
        </aside>
    </section>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            cartItem();
            summary();

            $("#cart-clear-btn").on("click", function () {
                if (confirm("Remove all items from your cart?")) {
                    clearCart();
                }
            });

            $("#cart-promo-apply").on("click", function () {
                alert("Promo codes are coming soon.");
            });
        });
    </script>

</body>

</html>
