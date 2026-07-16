<?php
$path = "../../";
$pageTitle = "Checkout - HeyDaniel";
$metaDescription = "Complete your order with HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>
<body>
    <?php if ($userId !== 0) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <div class="Container">
            <div class="Checkout_Steps">
                <div class="Checkout_Step Checkout_Step_Done">
                    <span class="Checkout_Step_Num">1</span><span class="Checkout_Step_Label">Cart</span>
                </div>
                <div class="Checkout_Step Checkout_Step_Active">
                    <span class="Checkout_Step_Num">2</span><span class="Checkout_Step_Label">Checkout</span>
                </div>
                <div class="Checkout_Step">
                    <span class="Checkout_Step_Num">3</span><span class="Checkout_Step_Label">Review</span>
                </div>
            </div>
        </div>

        <section class="Container Checkout_Layout">
            <div class="Checkout_Form">
                <div class="Section_Header">
                    <h2>Delivery address</h2>
                </div>

                <p class="credential-error" id="checkout-error" style="display:none;"></p>

                <form id="checkout-form">
                    <div class="Checkout_Field">
                        <label for="checkout-address">Street address</label>
                        <input type="text" id="checkout-address" value="<?php echo htmlspecialchars($userAddress ?? '') ?>" required />
                    </div>

                    <div class="Checkout_Field">
                        <label for="checkout-apt">Apt / Unit (optional)</label>
                        <input type="text" id="checkout-apt" value="<?php echo htmlspecialchars($userUnit ?? '') ?>" />
                    </div>

                    <div class="Checkout_Field_Row">
                        <div class="Checkout_Field">
                            <label for="checkout-city">City</label>
                            <input type="text" id="checkout-city" value="<?php echo htmlspecialchars($userCity ?? '') ?>" required />
                        </div>
                        <div class="Checkout_Field">
                            <label for="checkout-state">State</label>
                            <input type="text" id="checkout-state" value="<?php echo htmlspecialchars($userState ?? '') ?>" required />
                        </div>
                        <div class="Checkout_Field">
                            <label for="checkout-zip">ZIP code</label>
                            <input type="text" id="checkout-zip" value="<?php echo htmlspecialchars($userZip ?? '') ?>" required />
                        </div>
                    </div>

                    <div class="Checkout_Field">
                        <label for="checkout-phone">Phone</label>
                        <input type="tel" id="checkout-phone" value="<?php echo htmlspecialchars($userPhone ?? '') ?>" required />
                    </div>

                    <div class="Checkout_Field">
                        <label for="checkout-gate-code">Gate code (optional)</label>
                        <input type="text" id="checkout-gate-code" value="<?php echo htmlspecialchars($userGateCode ?? '') ?>" />
                    </div>

                    <div class="Checkout_Field">
                        <label for="checkout-note">Delivery note (optional)</label>
                        <textarea id="checkout-note" rows="2"><?php echo htmlspecialchars($userNote ?? '') ?></textarea>
                    </div>

                    <div class="Section_Header">
                        <h2>Delivery method</h2>
                    </div>
                    <div class="Delivery_Method_Options" id="checkout-delivery-methods">
                        <label class="Delivery_Method_Card" data-method="standard">
                            <input type="radio" name="delivery-method" value="standard" checked />
                            <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                            <div class="Delivery_Method_Info">
                                <span class="Delivery_Method_Name">Standard Delivery</span>
                                <span class="Delivery_Method_Desc">3-5 business days</span>
                            </div>
                            <span class="Delivery_Method_Price">$3.99</span>
                        </label>
                        <label class="Delivery_Method_Card" data-method="express">
                            <input type="radio" name="delivery-method" value="express" />
                            <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                            <div class="Delivery_Method_Info">
                                <span class="Delivery_Method_Name">Express Delivery</span>
                                <span class="Delivery_Method_Desc">1-2 business days</span>
                            </div>
                            <span class="Delivery_Method_Price">$5.99</span>
                        </label>
                        <label class="Delivery_Method_Card" id="checkout-delivery-same-day" data-method="same-day" style="display:none;">
                            <input type="radio" name="delivery-method" value="same-day" />
                            <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                            <div class="Delivery_Method_Info">
                                <span class="Delivery_Method_Name">Same-Day Delivery</span>
                                <span class="Delivery_Method_Desc">Within 4 hours &mdash; your address qualifies</span>
                            </div>
                            <span class="Delivery_Method_Price">$9.99</span>
                        </label>
                    </div>
                    <div class="Checkout_Field" id="checkout-tip-field" style="display:none;">
                        <label for="checkout-tip">Add a tip for your driver (optional)</label>
                        <input type="number" id="checkout-tip" min="0" step="0.01" placeholder="0.00" />
                    </div>

                    <div class="Section_Header">
                        <h2>Payment</h2>
                    </div>
                    <div class="Payment_Method_Options" id="checkout-payment-methods">
                        <label class="Payment_Method_Option" data-method="card">
                            <input type="radio" name="payment-method" value="card" checked />
                            <img src="<?php echo $path ?>Assets/Icons/credit-card.svg" alt="" />
                            <span>Credit / Debit Card</span>
                        </label>
                        <label class="Payment_Method_Option" data-method="paypal">
                            <input type="radio" name="payment-method" value="paypal" />
                            <span>PayPal</span>
                        </label>
                        <label class="Payment_Method_Option" data-method="apple-pay">
                            <input type="radio" name="payment-method" value="apple-pay" />
                            <span>Apple Pay</span>
                        </label>
                    </div>
                    <div class="Payment_Method_Card" id="checkout-card-panel">
                        <div class="Checkout_Field">
                            <div id="checkout-card-element"></div>
                        </div>
                    </div>
                    <p class="Payment_Method_Coming_Soon" id="checkout-payment-coming-soon" style="display:none;">This payment method is coming soon &mdash; please pay by card for now.</p>

                    <button type="submit" class="Place_Order_Btn" id="checkout-submit-btn">Place order</button>
                </form>
            </div>

            <aside class="Cart_Summary Checkout_Summary">
                <h2>Order Summary</h2>
                <div class="Checkout_Summary_Items" id="checkout-summary-items"></div>
                <div class="Cart_Summary_Row">
                    <span>Subtotal</span>
                    <span id="checkout-subtotal">$0.00</span>
                </div>
                <div class="Cart_Summary_Row">
                    <span>Tax</span>
                    <span id="checkout-tax">$0.00</span>
                </div>
                <div class="Cart_Summary_Row" id="checkout-tip-row" style="display:none;">
                    <span>Tip</span>
                    <span id="checkout-tip-total">$0.00</span>
                </div>
                <div class="Cart_Summary_Row">
                    <span>Total</span>
                    <span id="checkout-total">$0.00</span>
                </div>
            </aside>
        </section>

        <?php require $path . "Interface/Sections/Footer.php"; ?>

        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripePublishableKey = <?php echo json_encode($_ENV['STRIPE_PUBLISHABLE_KEY'] ?? ''); ?>;
            const isSameDayEligible = <?php echo json_encode((bool)($_SESSION['same_day_eligible'] ?? false)); ?>;
            const checkoutTaxRate = <?php echo json_encode((float)($_SESSION['tax_rate'] ?? 0)); ?>;

            $(document).ready(function () {
                $("#checkout-delivery-same-day").toggle(isSameDayEligible);

                $("input[name='delivery-method']").on("change", function () {
                    $("#checkout-delivery-methods .Delivery_Method_Card").removeClass("Selected");
                    $(this).closest(".Delivery_Method_Card").addClass("Selected");
                    $("#checkout-tip-field").toggle($(this).val() === "same-day");
                    updateTotal();
                });
                $("#checkout-delivery-methods input[checked]").closest(".Delivery_Method_Card").addClass("Selected");

                $("input[name='payment-method']").on("change", function () {
                    const method = $(this).val();
                    $("#checkout-payment-methods .Payment_Method_Option").removeClass("Selected");
                    $(this).closest(".Payment_Method_Option").addClass("Selected");

                    const isCard = method === "card";
                    $("#checkout-card-panel").toggle(isCard);
                    $("#checkout-payment-coming-soon").toggle(!isCard);
                    $("#checkout-submit-btn").prop("disabled", !isCard);
                });
                $("#checkout-payment-methods input[checked]").closest(".Payment_Method_Option").addClass("Selected");

                const stripe = Stripe(stripePublishableKey);
                const elements = stripe.elements();
                const cardElement = elements.create("card");
                cardElement.mount("#checkout-card-element");

                function isSameDaySelected() {
                    return isSameDayEligible && $("input[name='delivery-method']:checked").val() === "same-day";
                }

                function updateTotal() {
                    const subtotal = parseFloat($("#checkout-subtotal").text().replace('$', '')) || 0;
                    const tax = parseFloat($("#checkout-tax").text().replace('$', '')) || 0;
                    const tip = isSameDaySelected() ? (parseFloat($("#checkout-tip").val()) || 0) : 0;

                    $("#checkout-tip-row").toggle(tip > 0);
                    $("#checkout-tip-total").text('$' + tip.toFixed(2));
                    $("#checkout-total").text('$' + (subtotal + tax + tip).toFixed(2));
                }

                function loadSummary() {
                    $.ajax({
                        method: "POST",
                        url: "/HeyDaniel/Server/index.php",
                        contentType: "application/json",
                        dataType: "json",
                        data: JSON.stringify({ action: "cart_items", device_type: "Web" }),
                        success: function (data) {
                            if (data.message || !data.cart_items || !data.cart_items.length) {
                                window.location.href = "/HeyDaniel/Interface/Sheets/Cart.php";
                                return;
                            }

                            const $items = $("#checkout-summary-items").empty();
                            data.cart_items.forEach(function (item) {
                                $items.append(
                                    $('<div class="Checkout_Summary_Item"></div>').append(
                                        $('<div class="Checkout_Summary_Item_Image"></div>').css('background-image', 'url(' + item.picture + ')'),
                                        $('<div class="Checkout_Summary_Item_Info"></div>').append(
                                            $('<p class="Checkout_Summary_Item_Name"></p>').text(item.name),
                                            $('<p class="Checkout_Summary_Item_Meta"></p>').text((item.oz ? item.oz + ' · ' : '') + 'Qty ' + item.quantity)
                                        ),
                                        $('<span class="Checkout_Summary_Item_Price"></span>').text('$' + item.total_price.toFixed(2))
                                    )
                                );
                            });

                            const taxInclusiveTotal = data.cart_items.reduce(function (sum, item) {
                                return sum + item.total_price;
                            }, 0);

                            const subtotal = checkoutTaxRate > 0 ? taxInclusiveTotal / (1 + checkoutTaxRate) : taxInclusiveTotal;
                            const tax = taxInclusiveTotal - subtotal;

                            $("#checkout-subtotal").text('$' + subtotal.toFixed(2));
                            $("#checkout-tax").text('$' + tax.toFixed(2));
                            updateTotal();
                        },
                        error: function () {
                            window.location.href = "/HeyDaniel/Interface/Sheets/Cart.php";
                        }
                    });
                }

                $("#checkout-tip").on("input", updateTotal);

                loadSummary();

                $("#checkout-form").on("submit", function (e) {
                    e.preventDefault();

                    const $btn = $("#checkout-submit-btn");
                    const $error = $("#checkout-error");
                    $error.hide();
                    $btn.prop("disabled", true).text("Placing order...");

                    stripe.createPaymentMethod({
                        type: "card",
                        card: cardElement,
                        billing_details: { phone: $("#checkout-phone").val().trim() }
                    }).then(function (result) {
                        if (result.error) {
                            $error.text(result.error.message).show();
                            $btn.prop("disabled", false).text("Place order");
                            return;
                        }

                        const address = {
                            Address: $("#checkout-address").val().trim(),
                            Apt: $("#checkout-apt").val().trim(),
                            City: $("#checkout-city").val().trim(),
                            State: $("#checkout-state").val().trim(),
                            ZipCode: $("#checkout-zip").val().trim(),
                            GateCode: $("#checkout-gate-code").val().trim(),
                            Note: $("#checkout-note").val().trim(),
                            Phone: $("#checkout-phone").val().trim()
                        };

                        const tipAmount = isSameDaySelected() ? (parseFloat($("#checkout-tip").val()) || 0) : 0;

                        checkout(address, result.paymentMethod.id, tipAmount, function (orderId) {
                            window.location.href = "/HeyDaniel/Interface/Sheets/Confirmation.php?order_id=" + orderId;
                        }, function (message) {
                            $error.text(message).show();
                            $btn.prop("disabled", false).text("Place order");
                        });
                    });
                });
            });
        </script>
    <?php } else { ?>
        <?php require $path . "Interface/Sections/Credential.php"; ?>
        <?php require $path . "Interface/Sections/Footer.php"; ?>
    <?php } ?>
</body>

</html>
