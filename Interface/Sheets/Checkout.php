<?php
$path = "../../";
$pageTitle = "Checkout - HeyDaniel";
$metaDescription = "Complete your order with HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>
<body>
    <?php
    // The session's same_day_eligible flag reflects whatever zip the user is
    // currently browsing with, not necessarily this saved address's zip — a
    // saved address in a different area needs its own eligibility check.
    $addressSameDayEligible = false;
    if ($userId !== 0 && !empty($userZip)) {
        $zipStmt = $pdo->prepare("SELECT isSameDayEligible FROM ZipcodeAllowed WHERE Zipcode = ? LIMIT 1");
        $zipStmt->execute([$userZip]);
        $addressSameDayEligible = (bool)$zipStmt->fetchColumn();
    }
    ?>
    <?php if ($userId !== 0) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <section class="Container Checkout_Layout">
            <div class="Checkout_Form">
                <div class="Checkout_Page_Header">
                    <h1>Checkout</h1>
                    <span class="Checkout_Secure_Badge"><i class="fas fa-lock" aria-hidden="true"></i> Secure checkout</span>
                </div>

                <div class="Checkout_Steps" id="checkout-steps">
                    <div class="Checkout_Step Checkout_Step_Active" data-target="checkout-section-information">
                        <span class="Checkout_Step_Num">1</span><span class="Checkout_Step_Label">Information</span>
                    </div>
                    <div class="Checkout_Step_Connector Checkout_Step_Connector_Active"></div>
                    <div class="Checkout_Step" data-target="checkout-section-delivery">
                        <span class="Checkout_Step_Num">2</span><span class="Checkout_Step_Label">Delivery</span>
                    </div>
                    <div class="Checkout_Step_Connector"></div>
                    <div class="Checkout_Step" data-target="checkout-section-payment">
                        <span class="Checkout_Step_Num">3</span><span class="Checkout_Step_Label">Payment</span>
                    </div>
                </div>

                <div class="Checkout_Section_Header" id="checkout-section-information">
                    <span class="Checkout_Section_Icon Icon_Circle"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></span>
                    <h2>Delivery address</h2>
                </div>

                <p class="credential-error" id="checkout-error" style="display:none;"></p>

                <form id="checkout-form">
                    <?php if (!empty($userAddress)) { ?>
                        <div class="Checkout_Suggestion_Card Selected" id="checkout-address-suggestion">
                            <div class="Checkout_Suggestion_Icon Icon_Circle"><i class="fas fa-map-marker-alt" aria-hidden="true"></i></div>
                            <div class="Checkout_Suggestion_Info">
                                <p class="Checkout_Suggestion_Label">Select address</p>
                                <p class="Checkout_Suggestion_Detail"><?php echo htmlspecialchars($userAddress . ($userUnit ? ', ' . $userUnit : '') . ', ' . $userCity . ', ' . $userState . ' ' . $userZip) ?></p>
                                <div class="Checkout_Badges">
                                    <span class="Checkout_Selected_Badge">Selected</span>
                                </div>
                            </div>
                            <button type="button" class="Checkout_Suggestion_Toggle" id="checkout-address-toggle">
                                <span class="Checkout_Suggestion_Toggle_Icon"><i class="fas fa-pen" aria-hidden="true"></i></span>
                                <span class="Checkout_Suggestion_Toggle_Label" id="checkout-address-toggle-label">Edit</span>
                            </button>
                        </div>
                    <?php } ?>

                    <div class="Checkout_Address_Fields" id="checkout-address-fields"<?php echo !empty($userAddress) ? ' style="display:none;"' : '' ?>>
                    <div class="Checkout_Field_Row">
                        <div class="Checkout_Field">
                            <label for="checkout-address">Street address</label>
                            <div class="Checkout_Field_Icon_Wrap">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                <input type="text" id="checkout-address" placeholder="Enter your street address" value="<?php echo htmlspecialchars($userAddress ?? '') ?>" required />
                            </div>
                        </div>
                        <div class="Checkout_Field">
                            <label for="checkout-apt">Apt / Unit (optional)</label>
                            <div class="Checkout_Field_Icon_Wrap">
                                <i class="fas fa-door-open" aria-hidden="true"></i>
                                <input type="text" id="checkout-apt" placeholder="Apt, suite, unit, etc." value="<?php echo htmlspecialchars($userUnit ?? '') ?>" />
                            </div>
                        </div>
                    </div>

                    <div class="Checkout_Field_Row">
                        <div class="Checkout_Field">
                            <label for="checkout-city">City</label>
                            <div class="Checkout_Field_Icon_Wrap">
                                <i class="fas fa-city" aria-hidden="true"></i>
                                <input type="text" id="checkout-city" placeholder="Enter city" value="<?php echo htmlspecialchars($userCity ?? '') ?>" required />
                            </div>
                        </div>
                        <div class="Checkout_Field">
                            <label for="checkout-state">State</label>
                            <div class="Checkout_Field_Icon_Wrap">
                                <i class="fas fa-map" aria-hidden="true"></i>
                                <select id="checkout-state" required>
                                    <option value="" disabled <?php echo empty($userState) ? 'selected' : '' ?>>Select state</option>
                                    <?php foreach ([
                                        'Alabama', 'Alaska', 'Arizona', 'Arkansas', 'California', 'Colorado', 'Connecticut', 'Delaware',
                                        'Florida', 'Georgia', 'Hawaii', 'Idaho', 'Illinois', 'Indiana', 'Iowa', 'Kansas', 'Kentucky',
                                        'Louisiana', 'Maine', 'Maryland', 'Massachusetts', 'Michigan', 'Minnesota', 'Mississippi',
                                        'Missouri', 'Montana', 'Nebraska', 'Nevada', 'New Hampshire', 'New Jersey', 'New Mexico',
                                        'New York', 'North Carolina', 'North Dakota', 'Ohio', 'Oklahoma', 'Oregon', 'Pennsylvania',
                                        'Rhode Island', 'South Carolina', 'South Dakota', 'Tennessee', 'Texas', 'Utah', 'Vermont',
                                        'Virginia', 'Washington', 'West Virginia', 'Wisconsin', 'Wyoming'
                                    ] as $stateOption) { ?>
                                        <option value="<?php echo htmlspecialchars($stateOption) ?>" <?php echo ($userState ?? '') === $stateOption ? 'selected' : '' ?>><?php echo htmlspecialchars($stateOption) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>
                        <div class="Checkout_Field">
                            <label for="checkout-zip">ZIP code</label>
                            <div class="Checkout_Field_Icon_Wrap">
                                <i class="fas fa-hashtag" aria-hidden="true"></i>
                                <input type="text" id="checkout-zip" placeholder="Enter ZIP code" value="<?php echo htmlspecialchars($userZip ?? '') ?>" required />
                            </div>
                        </div>
                    </div>

                    <div class="Checkout_Field">
                        <label for="checkout-phone">Phone</label>
                        <div class="Checkout_Field_Icon_Wrap">
                            <i class="fas fa-phone-alt" aria-hidden="true"></i>
                            <input type="tel" id="checkout-phone" placeholder="Enter phone number" value="<?php echo htmlspecialchars($userPhone ?? '') ?>" required />
                        </div>
                    </div>

                    <div class="Checkout_Field_Row">
                        <div class="Checkout_Field">
                            <label for="checkout-gate-code">Gate code (optional)</label>
                            <div class="Checkout_Field_Icon_Wrap">
                                <i class="fas fa-key" aria-hidden="true"></i>
                                <input type="text" id="checkout-gate-code" placeholder="Enter gate code" value="<?php echo htmlspecialchars($userGateCode ?? '') ?>" />
                            </div>
                        </div>
                        <div class="Checkout_Field">
                            <label for="checkout-note">Delivery note (optional)</label>
                            <div class="Checkout_Field_Icon_Wrap Checkout_Field_Icon_Wrap--area">
                                <i class="fas fa-sticky-note" aria-hidden="true"></i>
                                <textarea id="checkout-note" rows="2" placeholder="Add delivery instructions, notes, etc."><?php echo htmlspecialchars($userNote ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    </div>

                    <div class="Checkout_Section_Header" id="checkout-section-delivery">
                        <span class="Checkout_Section_Icon Icon_Circle"><i class="fas fa-truck" aria-hidden="true"></i></span>
                        <h2>Delivery method</h2>
                    </div>
                    <div class="Delivery_Method_Options" id="checkout-delivery-methods">
                        <label class="Delivery_Method_Card Selected" data-method="standard">
                            <span class="Delivery_Method_Recommended">Recommended</span>
                            <input type="radio" name="delivery-method" value="standard" checked />
                            <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                            <div class="Delivery_Method_Info">
                                <span class="Delivery_Method_Name">Standard Delivery</span>
                                <span class="Delivery_Method_Desc">3-5 business days</span>
                                <span class="Checkout_Selected_Badge">Selected</span>
                            </div>
                            <span class="Delivery_Method_Price">$3.99</span>
                        </label>
                        <label class="Delivery_Method_Card" data-method="express">
                            <input type="radio" name="delivery-method" value="express" />
                            <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                            <div class="Delivery_Method_Info">
                                <span class="Delivery_Method_Name">Express Delivery</span>
                                <span class="Delivery_Method_Desc">1-2 business days</span>
                                <span class="Checkout_Selected_Badge" style="display:none;">Selected</span>
                            </div>
                            <span class="Delivery_Method_Price">$5.99</span>
                        </label>
                        <?php if ($isMember) { ?>
                            <label class="Delivery_Method_Card" id="checkout-delivery-same-day" data-method="same-day" style="display:none;">
                                <input type="radio" name="delivery-method" value="same-day" />
                                <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                                <div class="Delivery_Method_Info">
                                    <span class="Delivery_Method_Name">Same-Day Delivery</span>
                                    <span class="Delivery_Method_Desc">Within 4 hours &mdash; your address qualifies</span>
                                    <span class="Checkout_Selected_Badge" style="display:none;">Selected</span>
                                </div>
                                <span class="Delivery_Method_Price">Free</span>
                            </label>
                        <?php } else { ?>
                            <div class="Delivery_Method_Card Delivery_Method_Locked" id="checkout-delivery-same-day" data-method="same-day" style="display:none;">
                                <span class="Delivery_Method_Recommended" style="display:none;">Recommended</span>
                                <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                                <div class="Delivery_Method_Info">
                                    <span class="Delivery_Method_Name"><i class="fas fa-lock" aria-hidden="true"></i> Same-Day Delivery</span>
                                    <span class="Delivery_Method_Desc">Within 4 hours &mdash; HeyDaniel+ members only</span>
                                    <span class="Delivery_Method_Free_Badge">Free Delivery</span>
                                </div>
                                <button type="button" class="Delivery_Method_Join_Btn" data-open-membership-modal>Join for $9.99/mo</button>
                            </div>
                            <label class="Delivery_Method_Card" id="checkout-delivery-same-day-paid" data-method="same-day-paid" style="display:none;">
                                <input type="radio" name="delivery-method" value="same-day-paid" />
                                <img src="<?php echo $path ?>Assets/Icons/truck.svg" alt="" />
                                <div class="Delivery_Method_Info">
                                    <span class="Delivery_Method_Name">Same-Day Delivery</span>
                                    <span class="Delivery_Method_Desc">Within 4 hours &mdash; pay per order, no subscription</span>
                                    <span class="Checkout_Selected_Badge" style="display:none;">Selected</span>
                                </div>
                                <span class="Delivery_Method_Price">$7.99</span>
                            </label>
                        <?php } ?>
                    </div>
                    <div class="Checkout_Field" id="checkout-tip-field" style="display:none;">
                        <label>Add a tip for your driver (optional)</label>
                        <div class="Tip_Percent_Options" id="checkout-tip-percent-options">
                            <button type="button" class="Tip_Percent_Btn" data-tip-percent="5"><i class="fas fa-dollar-sign" aria-hidden="true"></i> 5%</button>
                            <button type="button" class="Tip_Percent_Btn" data-tip-percent="10"><i class="fas fa-dollar-sign" aria-hidden="true"></i> 10%</button>
                            <button type="button" class="Tip_Percent_Btn" data-tip-percent="20"><i class="fas fa-dollar-sign" aria-hidden="true"></i> 20%</button>
                            <button type="button" class="Tip_Percent_Btn" data-tip-percent="custom"><i class="fas fa-dollar-sign" aria-hidden="true"></i> Custom</button>
                        </div>
                        <div class="Checkout_Field_Icon_Wrap" id="checkout-tip-custom-wrap" style="display:none;">
                            <i class="fas fa-dollar-sign" aria-hidden="true"></i>
                            <input type="number" id="checkout-tip" min="0" step="0.01" placeholder="0.00" />
                        </div>
                    </div>

                    <div class="Checkout_Section_Header" id="checkout-section-payment">
                        <span class="Checkout_Section_Icon Icon_Circle"><i class="fas fa-credit-card" aria-hidden="true"></i></span>
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
                            <img src="<?php echo $path ?>Assets/Icons/paypal.svg" alt="" />
                            <span>PayPal</span>
                        </label>
                        <label class="Payment_Method_Option" data-method="apple-pay">
                            <input type="radio" name="payment-method" value="apple-pay" />
                            <img src="<?php echo $path ?>Assets/Icons/apple-pay.svg" alt="" />
                            <span>Apple Pay</span>
                        </label>
                    </div>
                    <div class="Payment_Method_Card" id="checkout-card-panel">
                        <div class="Checkout_Suggestion_Card Selected" id="checkout-card-suggestion" style="display:none;">
                            <div class="Checkout_Suggestion_Icon Icon_Circle" id="checkout-card-suggestion-icon"><i class="fas fa-credit-card" aria-hidden="true"></i></div>
                            <div class="Checkout_Suggestion_Info">
                                <p class="Checkout_Suggestion_Label">Select card</p>
                                <p class="Checkout_Suggestion_Detail" id="checkout-card-suggestion-detail"></p>
                                <span class="Checkout_Selected_Badge">Selected</span>
                            </div>
                            <button type="button" class="Checkout_Suggestion_Toggle" id="checkout-card-toggle">
                                <span class="Checkout_Suggestion_Toggle_Icon"><i class="fas fa-pen" aria-hidden="true"></i></span>
                                <span class="Checkout_Suggestion_Toggle_Label" id="checkout-card-toggle-label">Edit</span>
                            </button>
                        </div>

                        <div id="checkout-card-manual-fields" class="Membership_Modal_Card_Box">
                            <label for="checkout-card-number">Card details</label>
                            <div class="Membership_Modal_Card_Brands">
                                <img src="<?php echo $path ?>Assets/Icons/CardBrands/visa.svg" alt="Visa" />
                                <img src="<?php echo $path ?>Assets/Icons/CardBrands/mastercard.svg" alt="Mastercard" />
                                <img src="<?php echo $path ?>Assets/Icons/CardBrands/amex.svg" alt="Amex" />
                                <img src="<?php echo $path ?>Assets/Icons/CardBrands/discover.svg" alt="Discover" />
                            </div>
                            <div class="Membership_Modal_Card_Wrap Membership_Modal_Card_Number">
                                <i class="fas fa-credit-card" aria-hidden="true"></i>
                                <div id="checkout-card-number"></div>
                            </div>
                            <div class="Membership_Modal_Card_Row">
                                <div class="Membership_Modal_Card_Field">
                                    <label for="checkout-card-expiry">Expiration</label>
                                    <div class="Membership_Modal_Card_Wrap">
                                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                        <div id="checkout-card-expiry"></div>
                                    </div>
                                </div>
                                <div class="Membership_Modal_Card_Field">
                                    <label for="checkout-card-cvc">CVC</label>
                                    <div class="Membership_Modal_Card_Wrap">
                                        <i class="fas fa-lock" aria-hidden="true"></i>
                                        <div id="checkout-card-cvc"></div>
                                    </div>
                                </div>
                                <div class="Membership_Modal_Card_Field">
                                    <label for="checkout-card-zip">ZIP Code</label>
                                    <div class="Membership_Modal_Card_Wrap Membership_Modal_Card_Zip">
                                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                                        <input type="text" id="checkout-card-zip" inputmode="numeric" maxlength="10" placeholder="ZIP" autocomplete="postal-code" />
                                    </div>
                                </div>
                            </div>
                            <label class="Checkout_Save_Card_Row">
                                <input type="checkbox" id="checkout-save-card" checked />
                                Save card for faster checkout
                            </label>
                        </div>
                    </div>
                    <p class="Payment_Method_Coming_Soon" id="checkout-payment-coming-soon" style="display:none;">This payment method is coming soon &mdash; please pay by card for now.</p>

                    <button type="submit" class="Checkout_Submit_Btn Btn_Checkout" id="checkout-submit-btn">
                        <i class="fas fa-lock" aria-hidden="true"></i> <span id="checkout-submit-btn-label">Place order</span>
                    </button>
                </form>
            </div>

            <aside class="Cart_Summary Checkout_Summary">
                <h2>Order Summary</h2>
                <div class="Checkout_Summary_Items" id="checkout-summary-items">
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
                <div class="Cart_Summary_Row">
                    <span>Subtotal</span>
                    <span id="checkout-subtotal">$0.00</span>
                </div>
                <div class="Cart_Summary_Row">
                    <span>Tax</span>
                    <span id="checkout-tax">$0.00</span>
                </div>
                <div class="Cart_Summary_Row" id="checkout-delivery-fee-row" style="display:none;">
                    <span>Delivery fee</span>
                    <span id="checkout-delivery-fee-total">$0.00</span>
                </div>
                <div class="Cart_Summary_Row" id="checkout-tip-row" style="display:none;">
                    <span>Tip</span>
                    <span id="checkout-tip-total">$0.00</span>
                </div>

                <div class="Cart_Summary_Total_Row">
                    <span>Total</span>
                    <span id="checkout-total">$0.00</span>
                </div>

                <ul class="Checkout_Trust_Badges">
                    <li>
                        <span class="Checkout_Trust_Badge_Icon Icon_Circle"><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                        <span class="Checkout_Trust_Badge_Label">Secure Checkout</span>
                        <span class="Checkout_Trust_Badge_Sub">Your data is protected</span>
                    </li>
                    <li>
                        <span class="Checkout_Trust_Badge_Icon Icon_Circle"><i class="fas fa-award" aria-hidden="true"></i></span>
                        <span class="Checkout_Trust_Badge_Label">Quality Products</span>
                        <span class="Checkout_Trust_Badge_Sub">Carefully selected</span>
                    </li>
                    <li>
                        <span class="Checkout_Trust_Badge_Icon Icon_Circle"><i class="fas fa-truck" aria-hidden="true"></i></span>
                        <span class="Checkout_Trust_Badge_Label">Fast Delivery</span>
                        <span class="Checkout_Trust_Badge_Sub">Right to your door</span>
                    </li>
                </ul>

                <p class="Checkout_Security_Note">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                    <span>Your information is safe and secure.<br />We never store your full card details.</span>
                </p>
            </aside>
        </section>

        <?php require $path . "Interface/Sections/Footer.php"; ?>

        <script>
            const isSameDayEligible = <?php echo json_encode((bool)($_SESSION['same_day_eligible'] ?? false)); ?>;
            const addressSameDayEligible = <?php echo json_encode($addressSameDayEligible); ?>;
            const checkoutTaxRate = <?php echo json_encode((float)($_SESSION['tax_rate'] ?? 0)); ?>;
            const SAME_DAY_PAID_FEE = 7.99;

            $(document).ready(function () {
                // ============================
                // Step indicator — highlights the section currently in view
                // and lets clicking a step jump to it
                // ============================
                const $checkoutSteps = $("#checkout-steps .Checkout_Step");
                const $checkoutConnectors = $("#checkout-steps .Checkout_Step_Connector");
                const checkoutStepSections = $checkoutSteps.map(function () {
                    return document.getElementById($(this).data("target"));
                }).get();

                // the steps bar is sticky, so its own height is the amount of
                // content it permanently covers at the top of the viewport
                function stepsBarOffset() {
                    return $("#checkout-steps").outerHeight() + 20;
                }

                function updateCheckoutSteps() {
                    const scrollPos = window.scrollY + stepsBarOffset();
                    let activeIndex = 0;
                    checkoutStepSections.forEach(function (section, i) {
                        if (section && section.offsetTop <= scrollPos) {
                            activeIndex = i;
                        }
                    });
                    $checkoutSteps.each(function (i) {
                        $(this).toggleClass("Checkout_Step_Active", i <= activeIndex);
                    });
                    $checkoutConnectors.each(function (i) {
                        $(this).toggleClass("Checkout_Step_Connector_Active", i < activeIndex);
                    });
                }

                $checkoutSteps.on("click", function () {
                    const target = document.getElementById($(this).data("target"));
                    if (target) {
                        window.scrollTo({ top: target.offsetTop - stepsBarOffset(), behavior: "smooth" });
                    }
                });

                $(window).on("scroll", updateCheckoutSteps);
                updateCheckoutSteps();

                $("#checkout-delivery-same-day").toggle(isSameDayEligible);
                $("#checkout-delivery-same-day-paid").toggle(isSameDayEligible);

                if (isSameDayEligible) {
                    // Standard/Express shipping don't make sense once same-day is
                    // on the table — hide them rather than just deprioritizing,
                    // so the real choice is membership vs. pay-per-order (or just
                    // the free perk for existing members).
                    $("#checkout-delivery-methods label[data-method='standard']").hide();
                    $("#checkout-delivery-methods label[data-method='express']").hide();

                    if (isMember) {
                        $("#checkout-delivery-same-day")
                            .addClass("Selected")
                            .find(".Checkout_Selected_Badge").show();
                        $("#checkout-delivery-same-day input[name='delivery-method']").prop("checked", true);
                    } else {
                        // Non-members: nudge toward the subscription with the
                        // "Recommended" badge, but default-select the one-time
                        // $7.99 option so checkout isn't blocked on joining.
                        $("#checkout-delivery-same-day .Delivery_Method_Recommended").show();
                        $("#checkout-delivery-same-day-paid")
                            .addClass("Selected")
                            .find(".Checkout_Selected_Badge").show();
                        $("#checkout-delivery-same-day-paid input[name='delivery-method']").prop("checked", true);
                    }
                    $("#checkout-tip-field").show();
                    updateTotal();
                }

                $("input[name='delivery-method']").on("change", function () {
                    $("#checkout-delivery-methods .Delivery_Method_Card").removeClass("Selected");
                    $("#checkout-delivery-methods .Checkout_Selected_Badge").hide();
                    $(this).closest(".Delivery_Method_Card").addClass("Selected");
                    $(this).closest(".Delivery_Method_Card").find(".Checkout_Selected_Badge").show();
                    $("#checkout-tip-field").toggle($(this).val() === "same-day" || $(this).val() === "same-day-paid");
                    updateTotal();
                });

                // Hidden required fields still block native form validation (the
                // browser tries to focus them and silently swallows the submit),
                // so the "required" attribute has to track visibility, not just
                // sit static on the markup.
                const $requiredAddressFields = $("#checkout-address, #checkout-city, #checkout-state, #checkout-zip, #checkout-phone");

                function setAddressFieldsRequired(isRequired) {
                    $requiredAddressFields.prop("required", isRequired);
                }

                if ($("#checkout-address-fields").is(":hidden")) {
                    setAddressFieldsRequired(false);
                }

                $("#checkout-address-toggle").on("click", function () {
                    const $suggestion = $("#checkout-address-suggestion");
                    const $fields = $("#checkout-address-fields");
                    const $badge = $suggestion.find(".Checkout_Selected_Badge");
                    const usingSaved = $suggestion.hasClass("Selected");

                    if (usingSaved) {
                        $suggestion.removeClass("Selected");
                        $fields.show();
                        $badge.hide();
                        setAddressFieldsRequired(true);
                        $("#checkout-address-toggle-label").text("Select");
                    } else {
                        $suggestion.addClass("Selected");
                        $fields.hide();
                        $badge.show();
                        setAddressFieldsRequired(false);
                        $("#checkout-address-toggle-label").text("Edit");
                    }
                });

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
                const stripeElementStyle = {
                    base: {
                        fontSize: "14px",
                        fontFamily: "inherit",
                        color: "#333",
                        "::placeholder": { color: "#9aa0ab" }
                    }
                };
                const cardNumberElement = elements.create("cardNumber", { style: stripeElementStyle, showIcon: false });
                const cardExpiryElement = elements.create("cardExpiry", { style: stripeElementStyle });
                const cardCvcElement = elements.create("cardCvc", { style: stripeElementStyle });
                cardNumberElement.mount("#checkout-card-number");
                cardExpiryElement.mount("#checkout-card-expiry");
                cardCvcElement.mount("#checkout-card-cvc");

                let savedCardId = null;

                $("#checkout-card-toggle").on("click", function () {
                    const $suggestion = $("#checkout-card-suggestion");
                    const $fields = $("#checkout-card-manual-fields");
                    const $badge = $suggestion.find(".Checkout_Selected_Badge");
                    const usingSaved = $suggestion.hasClass("Selected");

                    if (usingSaved) {
                        $suggestion.removeClass("Selected");
                        $fields.show();
                        $badge.hide();
                        $("#checkout-card-toggle-label").text("Select");
                    } else {
                        $suggestion.addClass("Selected");
                        $fields.hide();
                        $badge.show();
                        $("#checkout-card-toggle-label").text("Edit");
                    }
                });

                function loadSavedCard() {
                    $.ajax({
                        method: "POST",
                        url: "/HeyDaniel/Server/index.php",
                        contentType: "application/json",
                        dataType: "json",
                        data: JSON.stringify({ action: "payment_methods", device_type: "Web" }),
                        success: function (data) {
                            const cards = data.payment_methods || [];
                            if (!cards.length) {
                                return;
                            }

                            const card = cards[0];
                            savedCardId = card.id;

                            $("#checkout-card-suggestion-icon").html(cardBrandIconHTML(card.brand));
                            $("#checkout-card-suggestion-detail").text(
                                card.brand.charAt(0).toUpperCase() + card.brand.slice(1) +
                                " •••• " + card.last4 + " · Expires " + card.exp_month + "/" + card.exp_year
                            );

                            $("#checkout-card-suggestion").show();
                            $("#checkout-card-manual-fields").hide();
                        },
                        error: function (xhr) {
                            const res = JSON.parse(xhr.responseText);
                            console.log("loadSavedCard failed:", res.error);
                        }
                    });
                }

                loadSavedCard();

                function isSameDaySelected() {
                    const selectedMethod = $("input[name='delivery-method']:checked").val();
                    return isSameDayEligible && (selectedMethod === "same-day" || selectedMethod === "same-day-paid");
                }

                function updateTotal() {
                    const subtotal = parseFloat($("#checkout-subtotal").text().replace('$', '')) || 0;
                    const tax = parseFloat($("#checkout-tax").text().replace('$', '')) || 0;
                    const deliveryFee = $("input[name='delivery-method']:checked").val() === "same-day-paid" ? SAME_DAY_PAID_FEE : 0;
                    const tip = isSameDaySelected() ? (parseFloat($("#checkout-tip").val()) || 0) : 0;

                    $("#checkout-delivery-fee-row").toggle(deliveryFee > 0);
                    $("#checkout-delivery-fee-total").text('$' + deliveryFee.toFixed(2));
                    $("#checkout-tip-row").toggle(tip > 0);
                    $("#checkout-tip-total").text('$' + tip.toFixed(2));
                    $("#checkout-total").text('$' + (subtotal + tax + tip + deliveryFee).toFixed(2));
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
                                window.location.href = "/HeyDaniel/Interface/Sheets/Cart";
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
                            window.location.href = "/HeyDaniel/Interface/Sheets/Cart";
                        }
                    });
                }

                $("#checkout-tip").on("input", updateTotal);

                $(document).on("click", ".Tip_Percent_Btn", function () {
                    const $btn = $(this);
                    const percent = $btn.data("tip-percent");

                    $(".Tip_Percent_Btn").removeClass("Selected");
                    $btn.addClass("Selected");

                    if (percent === "custom") {
                        $("#checkout-tip-custom-wrap").show();
                        $("#checkout-tip").val("").focus();
                    } else {
                        $("#checkout-tip-custom-wrap").hide();
                        const subtotal = parseFloat($("#checkout-subtotal").text().replace('$', '')) || 0;
                        $("#checkout-tip").val((subtotal * percent / 100).toFixed(2));
                    }

                    updateTotal();
                });

                loadSummary();

                $("#checkout-form").on("submit", function (e) {
                    e.preventDefault();

                    const $btn = $("#checkout-submit-btn");
                    const $btnLabel = $("#checkout-submit-btn-label");
                    const $error = $("#checkout-error");
                    $error.hide();
                    $btn.prop("disabled", true);
                    $btnLabel.text("Placing order...");

                    function submitOrder(paymentMethodId) {
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
                        const deliveryMethod = $("input[name='delivery-method']:checked").val() || "standard";

                        checkout(address, paymentMethodId, tipAmount, deliveryMethod, function (orderId) {
                            window.location.href = "/HeyDaniel/Interface/Sheets/Confirmation?order_id=" + orderId;
                        }, function (message) {
                            $error.text(message).show();
                            $btn.prop("disabled", false);
                            $btnLabel.text("Place order");
                        });
                    }

                    const usingSavedCard = savedCardId && $("#checkout-card-suggestion").hasClass("Selected");

                    if (usingSavedCard) {
                        submitOrder(savedCardId);
                        return;
                    }

                    stripe.createPaymentMethod({
                        type: "card",
                        card: cardNumberElement,
                        billing_details: {
                            phone: $("#checkout-phone").val().trim(),
                            address: { postal_code: $("#checkout-card-zip").val() }
                        }
                    }).then(function (result) {
                        if (result.error) {
                            $error.text(result.error.message).show();
                            $btn.prop("disabled", false);
                            $btnLabel.text("Place order");
                            return;
                        }

                        submitOrder(result.paymentMethod.id);
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
