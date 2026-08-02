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
                <span class="Confirmation_Confetti Confetti_1"></span>
                <span class="Confirmation_Confetti Confetti_2"></span>
                <span class="Confirmation_Confetti Confetti_3"></span>
                <span class="Confirmation_Confetti Confetti_4"></span>
                <span class="Confirmation_Confetti Confetti_5"></span>
                <i class="fas fa-check Confirmation_Icon" aria-hidden="true"></i>
            </div>
            <h1>Order Confirmed!</h1>
            <p class="Confirmation_Subtext">Thank you for your purchase.<br>We've received your order and are getting it ready.</p>

            <div class="Confirmation_Info_Bar">
                <div class="Confirmation_Info_Item">
                    <span class="Confirmation_Info_Icon Icon_Circle"><i class="fas fa-shopping-bag" aria-hidden="true"></i></span>
                    <div>
                        <p class="Confirmation_Info_Label">Order Number</p>
                        <p class="Confirmation_Info_Value">#<span id="confirmation-order-id"></span></p>
                    </div>
                </div>
                <div class="Confirmation_Info_Item">
                    <span class="Confirmation_Info_Icon Icon_Circle"><i class="fas fa-envelope" aria-hidden="true"></i></span>
                    <div>
                        <p class="Confirmation_Info_Label">Confirmation Sent To</p>
                        <p class="Confirmation_Info_Value" id="confirmation-email"></p>
                    </div>
                </div>
                <div class="Confirmation_Info_Item">
                    <span class="Confirmation_Info_Icon Icon_Circle"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                    <div>
                        <p class="Confirmation_Info_Label">Order Date</p>
                        <p class="Confirmation_Info_Value" id="confirmation-order-date"></p>
                    </div>
                </div>
            </div>

            <div class="Confirmation_Summary_Card">
                <div class="Confirmation_Summary_Header">
                    <span class="Confirmation_Summary_Icon Icon_Circle"><i class="fas fa-receipt" aria-hidden="true"></i></span>
                    <h2>Order Summary</h2>
                </div>
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
                </div>
                <div class="Confirmation_Total_Row">
                    <span>Total</span>
                    <span id="confirmation-total"></span>
                </div>
            </div>

            <div class="Estimated_Delivery_Card">
                <span class="Estimated_Delivery_Icon Icon_Circle"><i class="fas fa-truck" aria-hidden="true"></i></span>
                <div>
                    <p class="Estimated_Delivery_Label">Estimated Delivery</p>
                    <p class="Estimated_Delivery_Date" id="confirmation-eta"></p>
                    <p class="Estimated_Delivery_Note">We'll send you updates as your order is on the way.</p>
                </div>
            </div>

            <div class="Confirmation_Actions">
                <button type="button" class="Btn_Checkout Confirmation_Cta_Btn" id="confirmation-track-btn">
                    <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                    <span>Track Your Order</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </button>
                <a href="/HeyDaniel/Interface/Sheets/Store" class="Continue_Shopping_Btn Confirmation_Cta_Btn">
                    <i class="fas fa-shopping-bag" aria-hidden="true"></i>
                    <span>Continue Shopping</span>
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
                <a href="/HeyDaniel/Interface/Sheets/Profile" class="Text_Btn">View order history <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="Confirmation_Card" id="confirmation-skeleton">
            <div class="Skeleton_Block Skeleton_Confirmation_Icon"></div>
            <div class="Skeleton_Block Skeleton_Confirmation_Line" style="width:60%;"></div>
            <div class="Skeleton_Block Skeleton_Confirmation_Line" style="width:80%;"></div>
            <div class="Skeleton_Block Skeleton_Confirmation_Info_Bar"></div>
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
                <a href="/HeyDaniel/Interface/Sheets/Profile" class="Continue_Shopping_Btn">View order history</a>
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
                data: JSON.stringify({ action: "order_details", device_type: "Web", order_id: orderId }),
                success: function (data) {
                    const order = data.order;

                    $("#confirmation-skeleton").hide();

                    if (!order) {
                        $("#confirmation-not-found").show();
                        return;
                    }

                    $("#confirmation-order-id").text(order.order_id);
                    $("#confirmation-email").text(order.email).attr("title", order.email);
                    $("#confirmation-items").text(order.item_count);
                    $("#confirmation-subtotal").text('$' + order.subtotal.toFixed(2));
                    $("#confirmation-tax").text('$' + order.tax.toFixed(2));
                    $("#confirmation-total").text('$' + order.total.toFixed(2));

                    if (order.tip > 0) {
                        $("#confirmation-tip").text('$' + order.tip.toFixed(2));
                        $("#confirmation-tip-row").show();
                    }

                    const orderDate = new Date(order.date_added);
                    $("#confirmation-order-date").text(
                        orderDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
                    );

                    if (order.estimated_delivery) {
                        $("#confirmation-eta").text(order.estimated_delivery);
                    } else {
                        const eta = new Date(order.date_added);
                        eta.setDate(eta.getDate() + 1);
                        $("#confirmation-eta").text(
                            eta.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) + ' · 1:00 PM - 3:00 PM'
                        );
                    }

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
