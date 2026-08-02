<?php
// Bottom tab bar + persistent mini cart bar (mobile only) — modeled on the
// bottom navigation and "view cart" bar pattern used by Instacart, DoorDash,
// Walmart, and Amazon, where the primary sections (Home/Store/Cart/Account)
// stay anchored in a fixed, thumb-reachable bar instead of living only in
// the scrolling header. Derived from REQUEST_URI (not a per-page variable)
// so every existing Sheet gets it for free without touching each file.
$__reqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$__currentPage = basename($__reqPath, '.php');
$__navHome = in_array($__currentPage, ['', 'HeyDaniel', 'index'], true);
$__navStore = in_array($__currentPage, ['Store', 'Item'], true);
$__navWishlist = ($__currentPage === 'Saved');
$__navCart = in_array($__currentPage, ['Cart', 'Checkout', 'Confirmation', 'Process'], true);
$__navAccount = in_array($__currentPage, [
    'Profile', 'Orders', 'Addresses', 'PaymentMethods', 'Settings', 'Notifications', 'HelpSupport', 'Credential',
], true);
// Item.php gets its own sticky Add-to-cart buy-bar (Item.css) — stacking
// that under the global tab bar as well would eat two fixed bars' worth of
// screen height, so the tab bar steps aside there. Credential (sign
// in/register) has nothing under it worth switching to either.
$__hideBottomNav = in_array($__currentPage, ['Item', 'Credential'], true);
// The persistent "view cart" bar only makes sense while browsing for more
// items — showing it on Cart/Checkout themselves would just duplicate the
// page the user is already on.
$__showMiniCartBar = $__navHome || $__currentPage === 'Store';
?>
<footer class="site-footer">
    <?php if (!isset($userId) || $userId === 0) { ?>
    <div class="footer-newsletter">
        <div class="footer-newsletter-copy">
            <h2>Get the HeyDaniel newsletter</h2>
            <p>Same-day deals and new arrivals, once a week.</p>
        </div>
        <div class="footer-newsletter-form">
            <input type="email" id="footer-newsletter-email" placeholder="name@email.com" />
            <button type="button" id="footer-newsletter-submit" aria-label="Subscribe to newsletter"><i class="fas fa-paper-plane" aria-hidden="true"></i></button>
        </div>
        <p class="footer-newsletter-message" id="footer-newsletter-message" style="display:none;"></p>
    </div>
    <?php } ?>

    <div class="footer-main">
        <div class="footer-brand">
            <img src="<?php echo $path ?>Assets/Logo/logo.svg" alt="HeyDaniel Logo" class="footer-logo" />
            <p>Your online shopping destination — groceries, essentials, and more, same-day.</p>
            <div class="footer-social">
                <a href="#" aria-label="HeyDaniel on Instagram"><i class="fab fa-instagram" aria-hidden="true"></i></a>
                <a href="#" aria-label="HeyDaniel on Facebook"><i class="fab fa-facebook" aria-hidden="true"></i></a>
                <a href="#" aria-label="HeyDaniel on Twitter"><i class="fab fa-twitter" aria-hidden="true"></i></a>
                <a href="#" aria-label="HeyDaniel on TikTok"><i class="fab fa-tiktok" aria-hidden="true"></i></a>
            </div>
        </div>

        <div class="footer-links">
            <h3>Shop</h3>
            <a href="/HeyDaniel/Interface/Sheets/Store">Groceries</a>
            <a href="/HeyDaniel/Interface/Sheets/Store">Electronics</a>
            <a href="/HeyDaniel/Interface/Sheets/Store">Kitchen</a>
            <a href="/HeyDaniel/Interface/Sheets/Store">Beauty</a>
            <a href="/HeyDaniel/Interface/Sheets/Store">All categories</a>
        </div>

        <div class="footer-links">
            <h3>Help</h3>
            <a href="#">Track order</a>
            <a href="#">Returns</a>
            <a href="#">Shipping info</a>
            <a href="#">Contact us</a>
            <a href="#">FAQs</a>
        </div>

        <div class="footer-links">
            <h3>Company</h3>
            <a href="#">About HeyDaniel</a>
            <a href="#">Careers</a>
            <a href="#">Press</a>
            <div class="footer-app-badges">
                <a href="#" class="footer-app-badge">
                    <i class="fab fa-apple" aria-hidden="true"></i>
                    <span>App Store</span>
                </a>
                <a href="#" class="footer-app-badge">
                    <i class="fab fa-google-play" aria-hidden="true"></i>
                    <span>Google Play</span>
                </a>
            </div>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?php echo date('Y') ?> HeyDaniel, LLC. All rights reserved.</p>
        <div class="footer-legal">
            <a href="#">Terms</a>
            <a href="#">Privacy</a>
            <a href="#">Accessibility</a>
        </div>
    </div>
</footer>

<!-- App-wide confirm/alert modals — replace native confirm()/alert() everywhere
     (see App_Confirm/App_Alert helpers in Component.js). Loaded once here in
     Footer.php since it's included on every page. Matches the user's own
     mockup: centered glowing icon, bold centered title/description, an
     optional context badge, pill-shaped dual buttons with icons, and an
     optional "can't be undone" footnote. -->
<div class="Address_Modal_Overlay" id="app-confirm-modal" style="display:none;">
    <div class="Address_Modal_Box App_Confirm_Box">
        <button type="button" class="App_Confirm_Close_Btn" id="app-confirm-close-x" aria-label="Close">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <div class="App_Confirm_Icon_Wrap App_Confirm_Icon_Danger" id="app-confirm-icon-wrap">
            <i class="fas fa-question" id="app-confirm-icon" aria-hidden="true"></i>
        </div>
        <h3 class="App_Confirm_Title" id="app-confirm-title">Please confirm</h3>
        <p class="App_Confirm_Message" id="app-confirm-message"></p>
        <span class="App_Confirm_Badge" id="app-confirm-badge" style="display:none;"></span>
        <div class="App_Confirm_Footer">
            <button type="button" class="App_Confirm_Pill_Btn App_Confirm_Pill_Danger" id="app-confirm-ok">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <span id="app-confirm-ok-label">Yes, continue</span>
            </button>
            <button type="button" class="App_Confirm_Pill_Btn App_Confirm_Pill_Neutral" id="app-confirm-cancel">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span id="app-confirm-cancel-label">No, go back</span>
            </button>
        </div>
        <p class="App_Confirm_Note" id="app-confirm-note">
            <i class="fas fa-question-circle" aria-hidden="true"></i>
            <span id="app-confirm-note-text">This action cannot be undone</span>
        </p>
    </div>
</div>

<div class="Address_Modal_Overlay" id="app-alert-modal" style="display:none;">
    <div class="Address_Modal_Box App_Confirm_Box">
        <button type="button" class="App_Confirm_Close_Btn" id="app-alert-close-x" aria-label="Close">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
        <div class="App_Confirm_Icon_Wrap App_Confirm_Icon_Info">
            <i class="fas fa-info" aria-hidden="true"></i>
        </div>
        <h3 class="App_Confirm_Title" id="app-alert-title">Notice</h3>
        <p class="App_Confirm_Message" id="app-alert-message"></p>
        <div class="App_Confirm_Footer">
            <button type="button" class="App_Confirm_Pill_Btn App_Confirm_Pill_Neutral App_Confirm_Pill_Solo" id="app-alert-ok">
                <i class="fas fa-check" aria-hidden="true"></i>
                OK
            </button>
        </div>
    </div>
</div>

<?php if ($__showMiniCartBar) { ?>
    <!-- PERSISTENT MINI CART BAR (mobile only) — floats above the bottom tab
         bar while browsing Home/Store, same "N items - View Cart" pattern
         Instacart/DoorDash use so the cart stays reachable without pinning
         a whole cart summary. Hidden until cartIcon()'s AJAX response (see
         Interaction.js's document-ready call) reports at least one item. -->
    <a href="/HeyDaniel/Interface/Sheets/Cart" class="Mini_Cart_Bar" id="mini-cart-bar">
        <span class="Mini_Cart_Bar_Icon"><i class="fas fa-shopping-cart" aria-hidden="true"></i></span>
        <span class="Mini_Cart_Bar_Text">
            <span class="Mini_Cart_Bar_Count" id="mini-cart-bar-count">0 items</span>
            <span class="Mini_Cart_Bar_Price" id="mini-cart-bar-price">$0.00</span>
        </span>
        <span class="Mini_Cart_Bar_Cta">View Cart <i class="fas fa-chevron-right" aria-hidden="true"></i></span>
    </a>
<?php } ?>

<?php if (!$__hideBottomNav) { ?>
    <!-- BOTTOM TAB BAR (mobile only) — the primary-sections-anchored-at-the-
         bottom pattern every major shopping app (Instacart, DoorDash,
         Walmart, Amazon) uses instead of relying solely on a scrolling
         header for navigation. -->
    <nav class="Bottom_Nav" aria-label="Primary">
        <a href="/HeyDaniel" class="Bottom_Nav_Link<?php echo $__navHome ? ' Active' : '' ?>">
            <i class="fas fa-home" aria-hidden="true"></i>
            <span>Home</span>
        </a>
        <a href="/HeyDaniel/Interface/Sheets/Store" class="Bottom_Nav_Link<?php echo $__navStore ? ' Active' : '' ?>">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span>Store</span>
        </a>
        <a href="/HeyDaniel/Interface/Sheets/Saved" class="Bottom_Nav_Link<?php echo $__navWishlist ? ' Active' : '' ?>">
            <span class="Nav_Icon_Wrap">
                <i class="fas fa-heart" aria-hidden="true"></i>
                <span class="Nav_Icon_Badge" id="bottomnav-wishlist-badge" style="display:none;">0</span>
            </span>
            <span>Wishlist</span>
        </a>
        <a href="/HeyDaniel/Interface/Sheets/Cart" class="Bottom_Nav_Link<?php echo $__navCart ? ' Active' : '' ?>">
            <span class="Nav_Icon_Wrap">
                <span id="bottomnav-cart-icon" class="Nav_Icon_Svg" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg></span>
                <span class="Nav_Icon_Badge" id="bottomnav-cart-badge" style="display:none;">0</span>
            </span>
            <span id="bottomnav-cart-label">Cart</span>
        </a>
        <a href="/HeyDaniel/Interface/Sheets/Profile" class="Bottom_Nav_Link<?php echo $__navAccount ? ' Active' : '' ?>">
            <i class="fas fa-user" aria-hidden="true"></i>
            <span>Account</span>
        </a>
    </nav>
<?php } ?>
