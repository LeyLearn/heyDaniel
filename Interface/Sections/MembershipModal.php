<div class="Address_Modal_Overlay" id="membership-modal-overlay" style="display:none;">
    <div class="Membership_Modal_Box">
        <button type="button" class="Membership_Modal_Close_Btn Btn_Icon_Ghost" id="membership-modal-close" aria-label="Close">
            <img src="<?php echo $path ?>Assets/Icons/close.svg" alt="" />
        </button>

        <div class="Membership_Modal_Top">
            <div class="Membership_Modal_Hero">
                <img src="<?php echo $path ?>Assets/Build/MembershipHero.png" alt="" />
            </div>
            <div class="Membership_Modal_Intro">
                <h3><span class="Membership_Modal_Title_Join">Join</span> <span class="Membership_Modal_Title_Brand">HeyDaniel+</span></h3>
                <p class="Membership_Modal_Subtitle">Unlock premium perks and a better shopping experience.</p>
                <div class="Membership_Modal_Price">
                    <span class="Membership_Modal_Price_Amount">$9.99</span><span class="Membership_Modal_Price_Interval">/month</span>
                </div>
            </div>
        </div>

        <div class="Membership_Modal_Benefits">
            <div class="Membership_Modal_Benefit">
                <span class="Membership_Modal_Benefit_Icon Icon_Circle"><i class="fas fa-truck" aria-hidden="true"></i></span>
                <p class="Membership_Modal_Benefit_Title">Same-day delivery</p>
                <p class="Membership_Modal_Benefit_Desc">On eligible orders</p>
            </div>
            <div class="Membership_Modal_Benefit">
                <span class="Membership_Modal_Benefit_Icon Icon_Circle"><i class="fas fa-tag" aria-hidden="true"></i></span>
                <p class="Membership_Modal_Benefit_Title">No per-order fee</p>
                <p class="Membership_Modal_Benefit_Desc">No same-day delivery fee</p>
            </div>
            <div class="Membership_Modal_Benefit">
                <span class="Membership_Modal_Benefit_Icon Icon_Circle"><i class="fas fa-calendar-alt" aria-hidden="true"></i></span>
                <p class="Membership_Modal_Benefit_Title">Cancel anytime</p>
                <p class="Membership_Modal_Benefit_Desc">No commitments, cancel anytime</p>
            </div>
        </div>

        <p class="credential-error" id="membership-modal-error" style="display:none;"></p>

        <div class="Checkout_Suggestion_Card Selected" id="membership-saved-card" style="display:none;">
            <div class="Checkout_Suggestion_Icon Icon_Circle" id="membership-saved-card-icon"><i class="fas fa-credit-card" aria-hidden="true"></i></div>
            <div class="Checkout_Suggestion_Info">
                <p class="Checkout_Suggestion_Label">Select card</p>
                <p class="Checkout_Suggestion_Detail" id="membership-saved-card-detail"></p>
                <span class="Checkout_Selected_Badge">Selected</span>
            </div>
            <button type="button" class="Checkout_Suggestion_Toggle" id="membership-card-toggle">
                <span class="Checkout_Suggestion_Toggle_Icon"><i class="fas fa-pen" aria-hidden="true"></i></span>
                <span class="Checkout_Suggestion_Toggle_Label" id="membership-card-toggle-label">Edit</span>
            </button>
        </div>

        <div id="membership-card-fields" class="Membership_Modal_Card_Box" style="display:none;">
            <label for="membership-card-number">Card details</label>
            <div class="Membership_Modal_Card_Brands">
                <img src="<?php echo $path ?>Assets/Icons/CardBrands/visa.svg" alt="Visa" />
                <img src="<?php echo $path ?>Assets/Icons/CardBrands/mastercard.svg" alt="Mastercard" />
                <img src="<?php echo $path ?>Assets/Icons/CardBrands/amex.svg" alt="Amex" />
                <img src="<?php echo $path ?>Assets/Icons/CardBrands/discover.svg" alt="Discover" />
            </div>
            <div class="Membership_Modal_Card_Wrap Membership_Modal_Card_Number">
                <i class="fas fa-credit-card" aria-hidden="true"></i>
                <div id="membership-card-number"></div>
            </div>
            <div class="Membership_Modal_Card_Row">
                <div class="Membership_Modal_Card_Field">
                    <label for="membership-card-expiry">Expiration</label>
                    <div class="Membership_Modal_Card_Wrap">
                        <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                        <div id="membership-card-expiry"></div>
                    </div>
                </div>
                <div class="Membership_Modal_Card_Field">
                    <label for="membership-card-cvc">CVC</label>
                    <div class="Membership_Modal_Card_Wrap">
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <div id="membership-card-cvc"></div>
                    </div>
                </div>
                <div class="Membership_Modal_Card_Field">
                    <label for="membership-card-zip">ZIP Code</label>
                    <div class="Membership_Modal_Card_Wrap Membership_Modal_Card_Zip">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <input type="text" id="membership-card-zip" inputmode="numeric" maxlength="10" placeholder="ZIP" autocomplete="postal-code" />
                    </div>
                </div>
            </div>
            <label class="Checkout_Save_Card_Row">
                <input type="checkbox" id="membership-save-card" checked />
                Save card for faster checkout
            </label>
        </div>

        <div class="Membership_Modal_Footer">
            <button type="button" class="Secondary_Light_Btn Membership_Modal_Cancel_Btn" id="membership-modal-cancel">Cancel</button>
            <button type="button" class="Primary_Btn Membership_Modal_Subscribe_Btn" id="membership-modal-subscribe">
                <i class="fas fa-shield-alt" aria-hidden="true"></i> <span id="membership-modal-subscribe-label">Subscribe for $9.99/mo</span>
            </button>
        </div>

        <p class="Membership_Modal_Secure_Note"><i class="fas fa-lock" aria-hidden="true"></i> Secure payment. Cancel anytime.</p>
    </div>
</div>
