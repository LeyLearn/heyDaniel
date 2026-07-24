<?php $activeAccountNav = $activeAccountNav ?? 'profile'; ?>
<aside class="Profile_Nav">
    <div class="Profile_Nav_Header">
        <div class="Profile_Nav_Avatar"><?php echo htmlspecialchars($firstInitial) ?></div>
        <div class="Profile_Nav_Identity">
            <p class="Profile_Nav_Name"><?php echo htmlspecialchars($userName) ?></p>
            <?php if ($isMember) { ?>
                <span class="Profile_Nav_Member_Badge"><i class="fas fa-crown" aria-hidden="true"></i> Gold Member</span>
            <?php } ?>
        </div>
    </div>

    <a href="/HeyDaniel/Interface/Sheets/Profile.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'profile' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/user.svg" alt="" />
        <span>My Profile</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/Orders.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'orders' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/history.svg" alt="" />
        <span>Orders</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/Saved.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'wishlist' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/heart.svg" alt="" />
        <span>Wishlist</span>
        <span class="Profile_Nav_Count_Badge" id="account-nav-wishlist-badge" style="display:none;">0</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/Cart.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'cart' ? ' Active' : '' ?>">
        <img id="account-nav-cart-icon" src="<?php echo $path ?>Assets/Icons/cart.svg" alt="" />
        <span>Cart</span>
        <span class="Profile_Nav_Count_Badge" id="account-nav-cart-badge" style="display:none;">0</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/Addresses.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'addresses' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/location.svg" alt="" />
        <span>Addresses</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/PaymentMethods.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'payment' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/credit-card.svg" alt="" />
        <span>Payment Methods</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/Notifications.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'notifications' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/notification.svg" alt="" />
        <span>Notifications</span>
        <span class="Profile_Nav_Count_Badge" id="account-nav-notifications-badge" style="display:none;">0</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/Settings.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'settings' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/setting.svg" alt="" />
        <span>Settings</span>
    </a>
    <a href="/HeyDaniel/Interface/Sheets/HelpSupport.php" class="Profile_Nav_Link Btn_Nav_Row<?php echo $activeAccountNav === 'help' ? ' Active' : '' ?>">
        <img src="<?php echo $path ?>Assets/Icons/help.svg" alt="" />
        <span>Help &amp; Support</span>
    </a>
    <button type="button" class="Profile_Nav_Link Btn_Nav_Row Profile_Nav_Logout" id="profile-logout-btn">
        <img src="<?php echo $path ?>Assets/Icons/logout.svg" alt="" />
        <span>Log out</span>
    </button>
</aside>
