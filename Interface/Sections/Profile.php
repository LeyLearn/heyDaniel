<?php require $path . "Interface/Sections/Header.php"; ?>

<section class="Container Profile_Layout">
    <?php $activeAccountNav = 'profile'; require $path . "Interface/Sections/AccountNav.php"; ?>

    <div class="Profile_Main">
        <div class="Profile_Welcome" id="profile-info">
            <div class="Profile_Avatar_Wrap">
                <div class="Profile_Avatar"><?php echo htmlspecialchars($firstInitial) ?></div>
            </div>
            <div class="Profile_Welcome_Text">
                <p class="Profile_Welcome_Tag">Welcome back,</p>
                <h1 id="profile-welcome-name"><?php echo htmlspecialchars($userName) ?></h1>
                <div class="Profile_Badges">
                    <?php if ($isMember) { ?>
                        <span class="Profile_Member_Badge"><i class="fas fa-crown" aria-hidden="true"></i> Gold Member</span>
                    <?php } ?>
                    <?php if (!empty($userTimeRegister)) { ?>
                        <span class="Profile_Member_Since">Since <?php echo htmlspecialchars(date("F j, Y", strtotime($userTimeRegister))) ?></span>
                    <?php } ?>
                </div>
            </div>
            <button type="button" class="Secondary_Light_Btn Edit_Profile_Btn" id="profile-welcome-edit-btn">
                <i class="fas fa-pen" aria-hidden="true"></i> Edit Profile
            </button>
        </div>

        <div class="Profile_Stats">
            <div class="Stat_Item">
                <span class="Stat_Icon Icon_Circle Stat_Icon_Orders" aria-hidden="true"></span>
                <div class="Stat_Text">
                    <span class="Stat_Number Stat_Number_Orders" id="stat-orders-count">0</span>
                    <span class="Stat_Label">Orders</span>
                    <a href="/HeyDaniel/Interface/Sheets/Orders.php" class="Stat_Link">View all orders</a>
                </div>
            </div>
            <div class="Stat_Divider"></div>
            <div class="Stat_Item">
                <span class="Stat_Icon Icon_Circle Stat_Icon_Wishlist" aria-hidden="true"></span>
                <div class="Stat_Text">
                    <span class="Stat_Number Stat_Number_Wishlist" id="stat-wishlist-count">0</span>
                    <span class="Stat_Label">Wishlist Items</span>
                    <a href="/HeyDaniel/Interface/Sheets/Saved.php" class="Stat_Link">View wishlist</a>
                </div>
            </div>
            <div class="Stat_Divider"></div>
            <div class="Stat_Item">
                <span class="Stat_Icon Icon_Circle Stat_Icon_Spent" aria-hidden="true"></span>
                <div class="Stat_Text">
                    <span class="Stat_Number Stat_Number_Spent" id="stat-total-spent">$0.00</span>
                    <span class="Stat_Label">Total Spent</span>
                    <a href="/HeyDaniel/Interface/Sheets/Orders.php" class="Stat_Link">View spending</a>
                </div>
            </div>
            <div class="Stat_Divider"></div>
            <div class="Stat_Item">
                <span class="Stat_Icon Icon_Circle Stat_Icon_Rewards" aria-hidden="true"></span>
                <div class="Stat_Text">
                    <span class="Stat_Number Stat_Number_Rewards"><?php echo htmlspecialchars(number_format($userCredits, 0)) ?></span>
                    <span class="Stat_Label">Reward Points</span>
                    <button type="button" class="Stat_Link" data-coming-soon="Rewards">View rewards</button>
                </div>
            </div>
        </div>

        <div class="Profile_Grid">
            <div class="Profile_Section">
                <div class="Profile_Info_Header">
                    <h3>Profile Information</h3>
                    <button type="button" class="Btn_Icon_Ghost Profile_Info_Edit" id="profile-info-edit-btn" aria-label="Edit Profile Information">
                        <i class="fas fa-pen" aria-hidden="true"></i>
                    </button>
                </div>

                <div class="Profile_Field">
                    <div class="Profile_Field_Icon Icon_Circle"><i class="fas fa-user" aria-hidden="true"></i></div>
                    <div class="Profile_Field_Text">
                        <label>Full Name</label>
                        <p id="profile-field-name-display"><?php echo htmlspecialchars($userName) ?></p>
                        <input type="text" id="profile-field-name-input" class="Profile_Field_Input" maxlength="100" value="<?php echo htmlspecialchars($userName) ?>" style="display:none;" />
                    </div>
                </div>
                <div class="Profile_Field">
                    <div class="Profile_Field_Icon Icon_Circle"><i class="fas fa-envelope" aria-hidden="true"></i></div>
                    <div class="Profile_Field_Text">
                        <label>Email Address</label>
                        <p><?php echo htmlspecialchars($userEmail) ?></p>
                    </div>
                </div>
                <div class="Profile_Field">
                    <div class="Profile_Field_Icon Icon_Circle"><i class="fas fa-phone-alt" aria-hidden="true"></i></div>
                    <div class="Profile_Field_Text">
                        <label>Phone Number</label>
                        <p id="profile-field-phone-display"><?php echo !empty($userPhone) ? htmlspecialchars($userPhone) : 'Not set' ?></p>
                        <input type="tel" id="profile-field-phone-input" class="Profile_Field_Input" maxlength="20" placeholder="e.g. 9041234567" value="<?php echo htmlspecialchars($userPhone) ?>" style="display:none;" />
                    </div>
                </div>
                <div class="Profile_Info_Edit_Actions" id="profile-info-edit-actions" style="display:none;">
                    <button type="button" class="Secondary_Light_Btn" id="profile-info-cancel-btn">Cancel</button>
                    <button type="button" class="Profile_Info_Save_Btn" id="profile-info-save-btn">Save</button>
                </div>
                <?php if (!empty($userTimeRegister)) { ?>
                    <div class="Profile_Field">
                        <div class="Profile_Field_Icon Icon_Circle"><i class="fas fa-calendar-alt" aria-hidden="true"></i></div>
                        <div class="Profile_Field_Text">
                            <label>Member Since</label>
                            <p><?php echo htmlspecialchars(date("F j, Y g:i A", strtotime($userTimeRegister))) ?></p>
                        </div>
                    </div>
                <?php } ?>
                <div class="Profile_Field">
                    <div class="Profile_Field_Icon Icon_Circle Profile_Field_Icon_Premium"><i class="fas fa-crown" aria-hidden="true"></i></div>
                    <div class="Profile_Field_Text">
                        <label>Premium Subscription</label>
                        <p class="<?php echo $isMember ? 'Profile_Premium_Active' : 'Profile_Premium_Inactive' ?>"><?php echo $isMember ? 'Active' : 'Inactive' ?></p>
                    </div>
                    <button type="button" class="Toggle_Switch <?php echo $isMember ? 'Toggle_On' : 'Toggle_Off' ?>" id="profile-premium-toggle" aria-label="Toggle Premium Subscription" aria-pressed="<?php echo $isMember ? 'true' : 'false' ?>">
                        <span class="Toggle_Knob"></span>
                    </button>
                </div>
            </div>

            <div class="Profile_Section" id="profile-orders">
                <div class="Order_History_Header">
                    <h3>Order History</h3>
                    <a href="/HeyDaniel/Interface/Sheets/Orders.php" class="Order_History_View_All">View All Orders</a>
                </div>
                <div id="order-history-empty" class="Order_Empty" style="display:none;">
                    <div class="Order_Empty_Icon_Wrap">
                        <img src="<?php echo $path ?>Assets/Icons/cart-empty.svg" alt="" class="Order_Empty_Icon" />
                    </div>
                    <p class="Order_Empty_Text">You haven't placed any orders yet.</p>
                    <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Order_Empty_Link">Start Browsing</a>
                </div>
                <div id="order-history-container">
                    <?php for ($i = 0; $i < 3; $i++) { ?>
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
                <a href="/HeyDaniel/Interface/Sheets/Orders.php" id="order-history-view-all-btn" class="Secondary_Light_Btn Secondary_Light_Btn--full Order_History_Footer_Btn" style="display:none;">View All Orders</a>
            </div>
        </div>

        <div class="Profile_Section">
            <h3><i class="fas fa-map-marker-alt" aria-hidden="true"></i> Address Book</h3>
            <div class="Address_Book_Grid" id="address-book-container">
                <?php if (!empty($userAddress)) { ?>
                    <div class="Address_Card"
                        data-address-id="<?php echo htmlspecialchars($userAddressId) ?>"
                        data-label="<?php echo htmlspecialchars($userLabel) ?>"
                        data-address="<?php echo htmlspecialchars($userAddress) ?>"
                        data-apt="<?php echo htmlspecialchars($userUnit) ?>"
                        data-city="<?php echo htmlspecialchars($userCity) ?>"
                        data-state="<?php echo htmlspecialchars($userState) ?>"
                        data-zip="<?php echo htmlspecialchars($userZip) ?>"
                        data-phone="<?php echo htmlspecialchars($userPhoneOnAddress) ?>"
                        data-gate-code="<?php echo htmlspecialchars($userGateCode) ?>"
                        data-note="<?php echo htmlspecialchars($userNote) ?>">
                        <div class="Address_Card_Icon Icon_Circle">
                            <img src="<?php echo $path ?>Assets/Icons/home.svg" alt="" />
                        </div>
                        <div class="Address_Card_Body">
                            <p class="Address_Card_Label"><?php echo htmlspecialchars($userLabel) ?> <span class="Address_Default_Badge">Default</span></p>
                            <p class="Profile_Address">
                                <?php echo htmlspecialchars($userAddress) ?><?php echo !empty($userUnit) ? ', ' . htmlspecialchars($userUnit) : '' ?><br>
                                <?php echo htmlspecialchars($userCity) ?>, <?php echo htmlspecialchars($userState) ?> <?php echo htmlspecialchars($userZip) ?>
                            </p>
                        </div>
                        <div class="Address_Card_Menu_Wrap">
                            <button type="button" class="Address_Card_Menu Btn_Icon_Ghost Btn_Icon_Ghost--sm" aria-label="Address options">
                                <i class="fas fa-ellipsis-v" aria-hidden="true"></i>
                            </button>
                            <div class="Address_Card_Menu_Dropdown">
                                <button type="button" class="Address_Card_Edit_Btn Btn_Nav_Row">Edit</button>
                                <button type="button" class="Address_Card_Delete_Btn Btn_Nav_Row">Delete</button>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <button type="button" class="Add_Address_Card Secondary_Light_Btn Secondary_Light_Btn--dashed" id="add-address-btn">
                    <span class="Add_Address_Plus">+</span> Add New Address
                </button>
            </div>
        </div>
    </div>
</section>

<?php require $path . "Interface/Sections/AddressModal.php"; ?>

<script>
    $(document).ready(function () {
        orderHistory();

        $("#profile-logout-btn").on("click", function () {
            logout();
        });

        initOrderRowInteractions("#order-history-container");

        function enterProfileEditMode() {
            $("#profile-field-name-display, #profile-field-phone-display").hide();
            $("#profile-field-name-input, #profile-field-phone-input").show();
            $("#profile-info-edit-actions").show();
            $("#profile-info-edit-btn, #profile-welcome-edit-btn").hide();
            $("#profile-field-name-input").trigger("focus");
        }

        function exitProfileEditMode() {
            $("#profile-field-name-display, #profile-field-phone-display").show();
            $("#profile-field-name-input, #profile-field-phone-input").hide();
            $("#profile-info-edit-actions").hide();
            $("#profile-info-edit-btn, #profile-welcome-edit-btn").show();
        }

        $("#profile-info-edit-btn, #profile-welcome-edit-btn").on("click", function () {
            enterProfileEditMode();
        });

        $("#profile-info-cancel-btn").on("click", function () {
            $("#profile-field-name-input").val($("#profile-field-name-display").text());
            const currentPhone = $("#profile-field-phone-display").text();
            $("#profile-field-phone-input").val(currentPhone === "Not set" ? "" : currentPhone);
            exitProfileEditMode();
        });

        $("#profile-info-save-btn").on("click", function () {
            const name = $("#profile-field-name-input").val().trim();
            const phone = $("#profile-field-phone-input").val().trim();

            if (!name) {
                alert("Please enter your name.");
                return;
            }

            if (phone.replace(/\D/g, "").length < 10) {
                alert("Please enter a valid phone number.");
                return;
            }

            saveProfileEdit(name, phone)
                .done(function (data) {
                    $("#profile-field-name-display").text(data.name);
                    $("#profile-welcome-name").text(data.name);
                    $("#profile-field-phone-display").text(data.phone || "Not set");
                    exitProfileEditMode();
                })
                .fail(function (xhr) {
                    const res = JSON.parse(xhr.responseText);
                    alert(res.error || "Failed to update profile.");
                });
        });

        initAddressBook("#address-book-container", "#add-address-btn");

        $("#profile-premium-toggle").on("click", function () {
            if ($(this).hasClass("Toggle_On")) {
                if (!confirm("Cancel your HeyDaniel+ membership? You'll lose access to same-day delivery.")) {
                    return;
                }

                cancelMembership(function () {
                    window.location.reload();
                }, function (message) {
                    alert(message);
                });
            } else {
                openMembershipModal();
            }
        });
    });
</script>
