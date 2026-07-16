<?php
$defaultCity = "Naples ";
$defaultState = "FL, ";
$defaultZip = "34116";
?>
<script>
    const isLoggedIn = <?php echo json_encode($userId !== 0); ?>;
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
                <input type="search" class="search" placeholder="Hey, what are we looking for <?php echo $firstName ?>?" />
                <!-- search icon -->
                <button type="button" class="submit" id="search-btn">
                    <img src="<?php echo $path ?>Assets/Icons/search.svg" alt="heyDaniel search" class="search-icon" />
                    <img src="<?php echo $path ?>Assets/Icons/close.svg" alt="Clear search" class="close-icon" />
                </button>
            </div>
        </div>
        <!-- users -->
        <nav class="user-actions">
            <!-- setting || user icon -->
            <button class="nav-account">
                <?php if ($userId === 0) { ?>
                    <img src="<?php echo $path ?>Assets/Icons/user.svg" alt="Settings" />
                    <!-- name || login -->
                    <span>Account</span>
                    <?php
                } else { ?>
                    <img src="<?php echo $path ?>Assets/Icons/setting.svg" alt="Settings" />
                    <!-- name || login -->
                    <span> <?php echo "Setting"; ?></span>
                <?php } ?>
            </button>

            <!-- wishlist -->
            <button class="nav-wishlist">
                <img src="<?php echo $path ?>Assets/Icons/heart.svg" alt="wishlist" />
                <!-- wishlist || number -->
                <span>Favorites</span>
            </button>

            <!-- cart || process icon -->
            <button class="nav-cart">
                <img src="<?php echo $path ?>Assets/Icons/cart.svg" alt="Shopping cart" />
                <!-- cart || number -->
                <span>Cart</span>
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
                    <p><span class="location-city" style="text-transform: capitalize; color: #fff"><?php echo (isset($_SESSION['city']) ? $_SESSION['city'] . ", " : $defaultCity); ?></span><span class="location-state" style="text-transform: capitalize; color: #fff"><?php echo $_SESSION['state'] ?? $defaultState; ?></span><span class="location-zip" style="color: #fff"><?php echo $_SESSION['zipcode'] ?? $defaultZip; ?></span></p>
                </div>
            </div>
            <!-- advertisement message -->
            <div class="summary-advertisement">
                <!-- main tag -->
                <h1>Premium same-day delivery on everything</h1>
                <!-- minor tag line -->
                <p>The best way to order. The fastest way to deliver.</p>
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
<dialog id="side-bar">
    <div class="box-title">
        <img src="<?php echo $path ?>Assets/Icons/close.svg" alt="back" class="close-icon-location" />
        <h1>Verify your location</h1>
    </div>
    <div class="zip-history">
        <p>Same-day delivery is available in select areas. Update your ZIP to see what's available near you.</p>
        <div class="zip-code-search">
            <input type="search" class="zip-code-input" placeholder="Enter your zip <?php echo $firstName ?>" />
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
        <button class="enable-location-btn">Continue</button>
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
                <button class="Close_Btn" type="button" id="zip-conflict-close" aria-label="Close">&times;</button>
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
                <button class="Zip_Conflict_Cancel_Btn" type="button" id="zip-conflict-cancel">cancel</button>
                <button class="Zip_Conflict_Continue_Btn" type="button" id="zip-conflict-continue">Continue</button>
            </div>
        </div>
        <div class="Zip_Conflict_Art">
            <img src="<?php echo $path ?>Assets/Icons/alert.svg" alt="" class="Zip_Conflict_Warning_Icon" />
            <div class="Zip_Conflict_Art_Circle"></div>
            <img src="<?php echo $path ?>Assets/Build/groceryIcon.png" alt="" class="Zip_Conflict_Art_Image" />
        </div>
    </div>
</div>