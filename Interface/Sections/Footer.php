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
