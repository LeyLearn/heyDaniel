<?php
$path = "../../";
$pageTitle = "Order Confirmed - HeyDaniel";
$metaDescription = "Your order has been placed. Thank you for shopping with HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>
<body>
    <?php require $path . "Interface/Sections/Header.php"; ?>

    <section class="Container Confirmation_Layout">
        <div class="Confirmation_Card" id="confirmation-found">
            <div class="Confirmation_Icon_Wrap">
                <img src="<?php echo $path ?>Assets/Icons/check.svg" alt="" class="Confirmation_Icon" />
            </div>
            <h1>Thank you!</h1>
            <p class="Confirmation_Subtext">Your order has been placed successfully.</p>
            <p class="Confirmation_Order_Id">Order #<span id="confirmation-order-id"></span></p>
            <?php if (!empty($userEmail)) { ?>
                <p class="Confirmation_Email">A confirmation email has been sent to <strong><?php echo htmlspecialchars($userEmail) ?></strong></p>
            <?php } ?>

            <div class="Confirmation_Summary">
                <div class="Cart_Summary_Row">
                    <span>Items</span>
                    <span id="confirmation-items"></span>
                </div>
                <div class="Cart_Summary_Row">
                    <span>Subtotal</span>
                    <span id="confirmation-subtotal"></span>
                </div>
                <div class="Cart_Summary_Row">
                    <span>Tax</span>
                    <span id="confirmation-tax"></span>
                </div>
                <div class="Cart_Summary_Row" id="confirmation-tip-row" style="display:none;">
                    <span>Tip</span>
                    <span id="confirmation-tip"></span>
                </div>
                <div class="Cart_Summary_Row">
                    <span>Total</span>
                    <span id="confirmation-total"></span>
                </div>
            </div>

            <div class="Estimated_Delivery_Card">
                <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                <div>
                    <p class="Estimated_Delivery_Label">Estimated Delivery</p>
                    <p class="Estimated_Delivery_Date" id="confirmation-eta"></p>
                </div>
            </div>

            <div class="Confirmation_Actions">
                <button type="button" class="Place_Order_Btn Primary_Btn" id="confirmation-track-btn">Track Your Order</button>
                <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Continue_Shopping_Btn">Continue shopping</a>
                <a href="/HeyDaniel/Interface/Sheets/Profile.php" class="Text_Btn">View order history</a>
            </div>
        </div>

        <div class="Confirmation_Card" id="confirmation-skeleton">
            <div class="Skeleton_Block Skeleton_Confirmation_Icon"></div>
            <div class="Skeleton_Block Skeleton_Confirmation_Line" style="width:60%;"></div>
            <div class="Skeleton_Block Skeleton_Confirmation_Line" style="width:80%;"></div>
            <div class="Skeleton_Confirmation_Summary">
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
            </div>
            <div class="Skeleton_Block Skeleton_Confirmation_Btn"></div>
        </div>

        <div class="Confirmation_Card" id="confirmation-not-found" style="display:none;">
            <h1>We couldn't find that order</h1>
            <p>It may still be processing. Check your order history for the latest status.</p>
            <div class="Confirmation_Actions">
                <a href="/HeyDaniel/Interface/Sheets/Profile.php" class="Continue_Shopping_Btn">View order history</a>
            </div>
        </div>
    </section>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            const orderId = parseInt(new URLSearchParams(window.location.search).get("order_id"), 10);

            $("#confirmation-found").hide();

            if (!orderId || orderId <= 0) {
                $("#confirmation-skeleton").hide();
                $("#confirmation-not-found").show();
                return;
            }

            $.ajax({
                method: "POST",
                url: "/HeyDaniel/Server/index.php",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify({ action: "order_history", device_type: "Web" }),
                success: function (data) {
                    const order = (data.orders || []).find(function (o) {
                        return o.order_id === orderId;
                    });

                    $("#confirmation-skeleton").hide();

                    if (!order) {
                        $("#confirmation-not-found").show();
                        return;
                    }

                    $("#confirmation-order-id").text(order.order_id);
                    $("#confirmation-items").text(order.item_count);
                    $("#confirmation-subtotal").text('$' + order.subtotal.toFixed(2));
                    $("#confirmation-tax").text('$' + order.tax.toFixed(2));
                    $("#confirmation-total").text('$' + order.total.toFixed(2));

                    if (order.tip > 0) {
                        $("#confirmation-tip").text('$' + order.tip.toFixed(2));
                        $("#confirmation-tip-row").show();
                    }

                    const eta = new Date(order.date_added);
                    eta.setDate(eta.getDate() + 1);
                    $("#confirmation-eta").text(
                        eta.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) + ' · 1:00 PM - 3:00 PM'
                    );

                    $("#confirmation-found").show();
                },
                error: function () {
                    $("#confirmation-skeleton").hide();
                    $("#confirmation-not-found").show();
                }
            });

            $("#confirmation-track-btn").on("click", function () {
                showAppAlert("Order tracking is coming soon.");
            });
        });
    </script>

</body>

</html>
