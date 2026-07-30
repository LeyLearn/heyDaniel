<?php
$defaultCity = "Naples ";
$defaultState = "FL, ";
$defaultZip = "34116";
$safeFirstName = isset($firstName) ? (string)$firstName : "";
?>
<script>
    window.isLoggedIn = <?php echo json_encode($userId !== 0); ?>;
    window.isloggedin = window.isLoggedIn;
    window.isMember = <?php echo json_encode((bool)$isMember); ?>;
</script>
<div id="app-skeleton">

    <div class="sk-header-main"></div>

    <div class="sk-header-summary">

        <div class="sk-location"></div>

        <div class="sk-banner"></div>

        <div class="sk-order"></div>

    </div>

</div>

<header>
    <div class="header-main">
        <!-- logo -->
        <a href="/HeyDaniel" class="logo">
            <img src="<?php echo $path ?>Assets/Logo/logo.svg" alt="heyDaniel Logo" />
        </a>
        <!-- search -->
        <div class="search-wrap">
            <div class="search-bar">
                <!-- input -->
                <input type="search" class="search" placeholder="Hey, what are we looking for <?php echo htmlspecialchars($safeFirstName, ENT_QUOTES, 'UTF-8'); ?>?" />
                <!-- search icon -->
                <button type="button" class="submit" id="search-btn">
                    <img src="<?php echo $path ?>Assets/Icons/search.svg" alt="heyDaniel search" class="search-icon" />
                    <img src="<?php echo $path ?>Assets/Icons/close.svg" alt="Clear search" class="close-icon" />
                </button>
            </div>
        </div>
        <!-- users -->
        <nav class="user-actions">
            <!-- dark mode toggle -->
            <button class="nav-theme-toggle" id="theme-toggle-btn" type="button" aria-label="Toggle dark mode" aria-pressed="false">
                <i class="fas fa-moon" id="theme-toggle-icon" aria-hidden="true"></i>
            </button>

            <!-- setting || user icon -->
            <button class="nav-account">
                <?php if ($userId === 0) { ?>
                    <img src="<?php echo $path ?>Assets/Icons/user.svg" alt="Settings" />
                    <!-- name || login -->
                    <span>Account</span>
                    <?php
                } else { ?>
                    <img src="<?php echo $path ?>Assets/Icons/user.svg" alt="Profile" />
                    <!-- name || login -->
                    <span> <?php echo "Profile"; ?></span>
                <?php } ?>
            </button>

            <!-- wishlist -->
            <button class="nav-wishlist">
                <span class="Nav_Icon_Wrap">
                    <img src="<?php echo $path ?>Assets/Icons/heart.svg" alt="wishlist" />
                    <span class="Nav_Icon_Badge" style="display:none;">0</span>
                </span>
                <span>Favorites</span>
            </button>

            <!-- cart || process icon -->
            <button class="nav-cart">
                <span class="Nav_Icon_Wrap">
                    <span id="nav-cart-icon" class="Nav_Icon_Svg" role="img" aria-label="Shopping cart"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg></span>
                    <span class="Nav_Icon_Badge" style="display:none;">0</span>
                </span>
                <span id="nav-cart-label">Cart</span>
            </button>
        </nav>
    </div>
    <div class="Search_Results" id="search-results"></div>
    <!-- summary -->
    <section class="header-summary">
        <div class="header-main">
            <!-- delivery spot zip -->
            <div class="summary-location">
                <!-- icon -->
                <div class="location-icon">
                    <img src="<?php echo $path ?>Assets/Icons/location.svg" alt="heyDaniel Delivery" />
                </div>
                <!-- tag line & zip -->
                <div class="location-info">
                    <!-- tag line -->
                    <h3>Delivery to</h3>
                    <!-- zipcode -->
                    <p><span class="location-city"><?php echo (isset($_SESSION['city']) ? $_SESSION['city'] . ", " : $defaultCity); ?></span><span class="location-state"><?php echo $_SESSION['state'] ?? $defaultState; ?></span><span class="location-zip"><?php echo $_SESSION['zipcode'] ?? $defaultZip; ?></span></p>
                </div>
            </div>
            <!-- advertisement message -->
            <div class="summary-advertisement">
                <!-- main tag -->
                <h1>Premium same-day delivery on everything</h1>
                <!-- minor tag line -->
                <p>The best way to order. The fastest way to deliver.</p>
                <?php /* Header_Join_Membership_Btn — pulled off the header, kept here for reuse elsewhere.
                <?php if ($userId !== 0 && !$isMember) { ?>
                    <button type="button" class="Header_Join_Membership_Btn" data-open-membership-modal>
                        <i class="fas fa-crown" aria-hidden="true"></i> Join HeyDaniel+ &mdash; $9.99/mo
                    </button>
                <?php } ?>
                */ ?>
            </div>
            <!-- order summary -->
            <div class="summary-order">
                <!-- icon -->
                <div class="order-icon">
                    <img src="<?php echo $path ?>Assets/Icons/check.svg" alt="Order Summary" />
                </div>
                <!-- order info -->
                <div class="order-info">
                    <h3>Order Summary</h3>
                    <p>$0.00</p>
                </div>
            </div>
        </div>
    </section>
</header>
<?php if ($userId !== 0) { ?>
    <script src="https://js.stripe.com/v3/"></script>
    <script>
        const stripePublishableKey = <?php echo json_encode($_ENV['STRIPE_PUBLISHABLE_KEY'] ?? ''); ?>;
    </script>
    <?php require $path . "Interface/Sections/MembershipModal.php"; ?>
<?php } ?>
<dialog id="side-bar">
    <div class="box-title">
        <button type="button" class="close-icon-location Btn_Icon_Ghost Btn_Icon_Ghost--inverse" aria-label="Close">
            <img src="<?php echo $path ?>Assets/Icons/close.svg" alt="" />
        </button>
        <h1>Verify your location</h1>
    </div>
    <div class="zip-history">
        <p>Same-day delivery is available in select areas. Update your ZIP to see what's available near you.</p>
        <div class="zip-code-search">
            <input type="search" class="zip-code-input" placeholder="Enter your zip <?php echo htmlspecialchars($safeFirstName, ENT_QUOTES, 'UTF-8'); ?>" />
            <button class="search-btn">
                <img src="<?php echo $path ?>Assets/Icons/location.svg" alt="heyDaniel location" class="search-icon" />
            </button>
        </div>
        <div class="history">
            <img src="<?php echo $path ?>Assets/Icons/history.svg" alt="heyDaniel history" class="history-icon" />
            <p class="prev-zip"><?php echo $_SESSION['zipcode'] ?? $defaultZip; ?></p>
        </div>
    </div>
    <div class="box-footer">
        <button class="Primary_Btn Primary_Btn--auto enable-location-btn">Continue</button>
        <p>
            By entering your ZIP code or allowing access to your current location, you acknowledge and agree to our Privacy Policy and Terms of Use.
        </p>
    </div>
</dialog>
<div class="Modal" id="zip-conflict-modal">
    <div class="Modal_Content Zip_Conflict_Content">
        <div class="Zip_Conflict_Main">
            <div class="Zip_Conflict_Header">
                <h2>Items unavailable</h2>
                <button class="Close_Btn Btn_Icon_Ghost Btn_Icon_Ghost--inverse" type="button" id="zip-conflict-close" aria-label="Close">&times;</button>
            </div>
            <div class="Zip_Conflict_Body">
                <div class="Zip_Conflict_Warning">
                    <div class="Zip_Conflict_Warning_Row">
                        <p><span class="zip-conflict-count"></span> item(s) in your cart require same-day delivery and aren't available at this address:</p>
                    </div>
                    <ul class="zip-conflict-items"></ul>
                </div>
            </div>
            <div class="Zip_Conflict_Footer">
                <button class="Secondary_Light_Btn Zip_Conflict_Cancel_Btn" type="button" id="zip-conflict-cancel">cancel</button>
                <button class="Primary_Btn Primary_Btn--auto Zip_Conflict_Continue_Btn" type="button" id="zip-conflict-continue">Continue</button>
            </div>
        </div>
        <div class="Zip_Conflict_Art">
            <img src="<?php echo $path ?>Assets/Icons/alert.svg" alt="" class="Zip_Conflict_Warning_Icon" />
            <div class="Zip_Conflict_Art_Circle"></div>
            <img src="<?php echo $path ?>Assets/Build/groceryIcon.png" alt="" class="Zip_Conflict_Art_Image" />
        </div>
    </div>
</div>