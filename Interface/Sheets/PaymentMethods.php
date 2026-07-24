<?php
$path = "../../";
$pageTitle = "Payment Methods - HeyDaniel";
$metaDescription = "Manage your saved payment methods on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php if (isset($_SESSION['user_id'])) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <section class="Container Profile_Layout">
            <?php $activeAccountNav = 'payment'; require $path . "Interface/Sections/AccountNav.php"; ?>

            <div class="Profile_Main">
                <div class="Profile_Section">
                    <div class="Order_History_Header">
                        <h3>Payment Methods</h3>
                    </div>

                    <p id="payment-methods-empty" class="Order_Empty_Text" style="display:none;">You haven't saved any payment methods yet — they're added automatically the first time you check out.</p>

                    <div id="payment-methods-list">
                        <?php for ($i = 0; $i < 2; $i++) { ?>
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

                    <button type="button" class="Add_Address_Card Payment_Method_Add_Btn Secondary_Light_Btn Secondary_Light_Btn--dashed" data-coming-soon="Adding a payment method">
                        <span class="Add_Address_Plus">+</span> Add Payment Method
                    </button>

                    <p class="Addresses_Cap_Note"><i class="fas fa-lock" aria-hidden="true"></i> Your payment information is secure and encrypted.</p>
                </div>
            </div>
        </section>
    <?php } else {
        require $path . "Interface/Sections/Credential.php";
    } ?>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            $("#profile-logout-btn").on("click", function () {
                logout();
            });

            $.ajax({
                method: "POST",
                url: "/HeyDaniel/Server/index.php",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify({
                    action: "payment_methods",
                    device_type: "Web"
                }),
                success: function (data) {
                    const methods = data.payment_methods || [];
                    const $list = $("#payment-methods-list").empty();

                    if (!methods.length) {
                        $("#payment-methods-empty").show();
                        return;
                    }

                    methods.forEach(function (method) {
                        const $row = $('<div class="Payment_Method_Row"></div>').append(
                            $('<div class="Payment_Method_Icon Icon_Circle"></div>').append('<i class="fas fa-credit-card" aria-hidden="true"></i>'),
                            $('<div class="Payment_Method_Info"></div>').append(
                                $('<p class="Payment_Method_Brand"></p>').text(
                                    method.brand.charAt(0).toUpperCase() + method.brand.slice(1) + ' ending in ' + method.last4
                                ),
                                $('<p class="Payment_Method_Expiry"></p>').text(
                                    'Expires ' + String(method.exp_month).padStart(2, '0') + '/' + String(method.exp_year).slice(-2)
                                )
                            ),
                            $('<button type="button" class="Secondary_Light_Btn" data-coming-soon="Editing this payment method"></button>').text('Edit')
                        );
                        $list.append($row);
                    });
                },
                error: function (xhr) {
                    const res = JSON.parse(xhr.responseText);
                    $("#payment-methods-list").empty();
                    $("#payment-methods-empty").text(res.error || "Couldn't load payment methods.").show();
                }
            });
        });
    </script>
</body>

</html>
