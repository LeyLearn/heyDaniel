<?php require $path . "Interface/Sections/Header.php"; ?>

<section class="Container Profile_Layout">
    <aside class="Profile_Nav">
        <a href="#profile-info" class="Profile_Nav_Link Active">
            <img src="<?php echo $path ?>Assets/Icons/user.svg" alt="" />
            <span>My Profile</span>
        </a>
        <a href="#profile-orders" class="Profile_Nav_Link">
            <img src="<?php echo $path ?>Assets/Icons/history.svg" alt="" />
            <span>Orders</span>
        </a>
        <a href="/HeyDaniel/Interface/Sheets/Saved.php" class="Profile_Nav_Link">
            <img src="<?php echo $path ?>Assets/Icons/heart.svg" alt="" />
            <span>Wishlist</span>
        </a>
        <a href="/HeyDaniel/Interface/Sheets/Cart.php" class="Profile_Nav_Link">
            <img src="<?php echo $path ?>Assets/Icons/cart.svg" alt="" />
            <span>Cart</span>
        </a>
        <button type="button" class="Profile_Nav_Link" data-coming-soon="Addresses">
            <img src="<?php echo $path ?>Assets/Icons/location.svg" alt="" />
            <span>Addresses</span>
        </button>
        <button type="button" class="Profile_Nav_Link" data-coming-soon="Payment Methods">
            <img src="<?php echo $path ?>Assets/Icons/credit-card.svg" alt="" />
            <span>Payment Methods</span>
        </button>
        <button type="button" class="Profile_Nav_Link" data-coming-soon="Notifications">
            <img src="<?php echo $path ?>Assets/Icons/notification.svg" alt="" />
            <span>Notifications</span>
        </button>
        <button type="button" class="Profile_Nav_Link" data-coming-soon="Settings">
            <img src="<?php echo $path ?>Assets/Icons/setting.svg" alt="" />
            <span>Settings</span>
        </button>
        <button type="button" class="Profile_Nav_Link" data-coming-soon="Help & Support">
            <img src="<?php echo $path ?>Assets/Icons/help.svg" alt="" />
            <span>Help &amp; Support</span>
        </button>
        <button type="button" class="Profile_Nav_Link Profile_Nav_Logout" id="profile-logout-btn">
            <img src="<?php echo $path ?>Assets/Icons/logout.svg" alt="" />
            <span>Log out</span>
        </button>
    </aside>

    <div class="Profile_Main">
        <div class="Profile_Welcome" id="profile-info">
            <div class="Profile_Avatar"><?php echo htmlspecialchars($firstInitial) ?></div>
            <div class="Profile_Welcome_Text">
                <p class="Profile_Welcome_Tag">Welcome back,</p>
                <h1><?php echo htmlspecialchars($userName) ?></h1>
                <?php if ($isMember) { ?>
                    <span class="Profile_Member_Badge">HeyDaniel Member</span>
                <?php } ?>
            </div>
            <button type="button" class="Secondary_Light_Btn Edit_Profile_Btn" data-coming-soon="Editing your profile">Edit Profile</button>
        </div>

        <div class="Profile_Grid">
            <div class="Profile_Section">
                <h3>Profile Information</h3>

                <div class="Profile_Field">
                    <label>Full Name</label>
                    <p><?php echo htmlspecialchars($userName) ?></p>
                </div>
                <div class="Profile_Field">
                    <label>Email Address</label>
                    <p><?php echo htmlspecialchars($userEmail) ?></p>
                </div>
                <?php if (!empty($userPhone)) { ?>
                    <div class="Profile_Field">
                        <label>Phone Number</label>
                        <p><?php echo htmlspecialchars($userPhone) ?></p>
                    </div>
                <?php } ?>
            </div>

            <div class="Profile_Section" id="profile-orders">
                <h3>Order History</h3>
                <p id="order-history-empty" class="Store_Empty" style="display:none;">You haven't placed any orders yet.</p>
                <div id="order-history-container"></div>
            </div>
        </div>

        <div class="Profile_Section">
            <h3>Address Book</h3>
            <div class="Address_Book_Grid">
                <?php if (!empty($userAddress)) { ?>
                    <div class="Address_Card">
                        <img src="<?php echo $path ?>Assets/Icons/location.svg" alt="" />
                        <div>
                            <p class="Address_Card_Label">Home</p>
                            <p class="Profile_Address">
                                <?php echo htmlspecialchars($userAddress) ?><?php echo !empty($userUnit) ? ', ' . htmlspecialchars($userUnit) : '' ?><br>
                                <?php echo htmlspecialchars($userCity) ?>, <?php echo htmlspecialchars($userState) ?> <?php echo htmlspecialchars($userZip) ?>
                            </p>
                        </div>
                    </div>
                <?php } ?>
                <button type="button" class="Add_Address_Card" data-coming-soon="Adding a new address">
                    <span class="Add_Address_Plus">+</span> Add New Address
                </button>
            </div>
        </div>
    </div>
</section>

<script>
    $(document).ready(function () {
        orderHistory();

        $("#profile-logout-btn").on("click", function () {
            logout();
        });

        $(".Profile_Nav_Link[href^='#']").on("click", function () {
            $(".Profile_Nav_Link").removeClass("Active");
            $(this).addClass("Active");
        });

        $("[data-coming-soon]").on("click", function () {
            alert($(this).data("coming-soon") + " is coming soon.");
        });
    });
</script>
