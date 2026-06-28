<header>
    <div class="header-main">
        <!-- logo -->
        <a href="/HeyDaniel" class="logo">
            <img src="<?php echo $path ?>Assets/Logo/logo.svg" alt="heyDaniel Logo" />
        </a>
        <!-- search -->
        <div class="search-bar">
            <!-- input -->
            <input type="search" class="search" placeholder="Hey, what are we looking for <?php echo $userName ?>?" />
            <!-- search icon -->
            <button type="button" class="submit" id="search-btn">
                <img src="<?php echo $path ?>Assets/Icons/search.svg" alt="heyDaniel search" class="search-icon" />
                <img src="<?php echo $path ?>Assets/Icons/close.svg" alt="Clear search" class="close-icon" />
            </button>
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
                    <span> <? echo $userName; ?></span>
                <? } ?>
            </button>

            <!-- wishlist -->
            <button class="nav-wishlist">
                <img src="<?php echo $path ?>Assets/Icons/heart.svg" alt="wishlist" />
                <!-- wishlist || number -->
                <span>Wish</span>
            </button>

            <!-- cart || process icon -->
            <button class="nav-cart">
                <img src="<?php echo $path ?>Assets/Icons/cart.svg" alt="Shopping cart" />
                <!-- cart || number -->
                <span>Cart</span>
            </button>
        </nav>
    </div>
    <!-- summary -->
    <section class="header-summary">
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
                <p>Naples FL, 34116</p>
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
    </section>
</header>