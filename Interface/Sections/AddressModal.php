<div class="Address_Modal_Overlay" id="address-modal-overlay" style="display:none;">
    <div class="Address_Modal_Box">
        <div class="Address_Modal_Header">
            <h3 id="address-modal-title">Add New Address</h3>
            <button type="button" class="Address_Modal_Close_Btn Btn_Icon_Ghost" id="address-modal-close" aria-label="Close">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <div class="Address_Modal_Body">
            <input type="hidden" id="address-form-id" value="" />
            <div class="Address_Modal_Field">
                <label for="address-form-label">Label</label>
                <input type="text" id="address-form-label" maxlength="50" placeholder="e.g. Home, Work" />
            </div>
            <div class="Address_Modal_Field">
                <label for="address-form-street">Street Address</label>
                <input type="text" id="address-form-street" maxlength="255" />
            </div>
            <div class="Address_Modal_Field">
                <label for="address-form-apt">Apt / Suite (optional)</label>
                <input type="text" id="address-form-apt" maxlength="50" />
            </div>
            <div class="Address_Modal_Field_Row">
                <div class="Address_Modal_Field">
                    <label for="address-form-city">City</label>
                    <input type="text" id="address-form-city" maxlength="100" />
                </div>
                <div class="Address_Modal_Field">
                    <label for="address-form-state">State</label>
                    <input type="text" id="address-form-state" maxlength="50" />
                </div>
                <div class="Address_Modal_Field">
                    <label for="address-form-zip">Zip Code</label>
                    <input type="text" id="address-form-zip" maxlength="16" />
                </div>
            </div>
            <div class="Address_Modal_Field">
                <label for="address-form-phone">Phone (optional)</label>
                <input type="tel" id="address-form-phone" maxlength="20" placeholder="e.g. 9041234567" />
            </div>
            <div class="Address_Modal_Field">
                <label for="address-form-gate">Gate Code (optional)</label>
                <input type="text" id="address-form-gate" maxlength="50" />
            </div>
            <div class="Address_Modal_Field">
                <label for="address-form-note">Delivery Note (optional)</label>
                <textarea id="address-form-note" maxlength="500" rows="2"></textarea>
            </div>
        </div>
        <div class="Address_Modal_Footer">
            <button type="button" class="Secondary_Light_Btn" id="address-modal-cancel">Cancel</button>
            <button type="button" class="Profile_Info_Save_Btn" id="address-modal-save">Save Address</button>
        </div>
    </div>
</div>
