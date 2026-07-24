<?php
$path = "../../";
$pageTitle = "Help & Support - HeyDaniel";
$metaDescription = "Get help with your HeyDaniel account and orders.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php if (isset($_SESSION['user_id'])) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <section class="Container Profile_Layout">
            <?php $activeAccountNav = 'help'; require $path . "Interface/Sections/AccountNav.php"; ?>

            <div class="Profile_Main">
                <div class="Profile_Section">
                    <h3>Help &amp; Support</h3>

                    <div class="Help_Search_Wrap">
                        <label for="help-search" class="Help_Search_Label">How can we help you?</label>
                        <div class="Help_Search_Row">
                            <input type="text" id="help-search" placeholder="Search for help articles..." />
                            <button type="button" class="Help_Search_Btn" id="help-search-btn"><i class="fas fa-search" aria-hidden="true"></i></button>
                        </div>
                    </div>

                    <p class="Help_Topics_Heading">Popular Topics</p>
                    <div class="Help_Topics_Grid">
                        <a href="/HeyDaniel/Interface/Sheets/Orders.php" class="Help_Topic_Card">
                            <p class="Help_Topic_Title">Track My Order</p>
                            <p class="Help_Topic_Desc">Check the status of your order.</p>
                        </a>
                        <button type="button" class="Help_Topic_Card" data-coming-soon="Returns & Refunds articles">
                            <p class="Help_Topic_Title">Returns &amp; Refunds</p>
                            <p class="Help_Topic_Desc">Learn about returns and refunds.</p>
                        </button>
                        <button type="button" class="Help_Topic_Card" data-coming-soon="Shipping Info articles">
                            <p class="Help_Topic_Title">Shipping Info</p>
                            <p class="Help_Topic_Desc">Delivery times and options.</p>
                        </button>
                        <a href="/HeyDaniel/Interface/Sheets/PaymentMethods.php" class="Help_Topic_Card">
                            <p class="Help_Topic_Title">Payment Methods</p>
                            <p class="Help_Topic_Desc">Accepted payment options.</p>
                        </a>
                    </div>

                    <div class="Help_Contact_Row">
                        <div>
                            <p class="Help_Topic_Title">Still need help?</p>
                            <p class="Help_Topic_Desc">Our support team is here for you.</p>
                        </div>
                        <button type="button" class="Profile_Info_Save_Btn" data-coming-soon="Contacting Support"><i class="fas fa-headset" aria-hidden="true"></i> Contact Support</button>
                    </div>
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

            $("#help-search-btn").on("click", function () {
                alert("Help search is coming soon.");
            });
        });
    </script>
</body>

</html>
