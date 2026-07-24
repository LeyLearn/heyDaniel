<?php
$path = "../../";
$pageTitle = "Settings - HeyDaniel";
$metaDescription = "Manage your account settings on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php if (isset($_SESSION['user_id'])) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <section class="Container Profile_Layout">
            <?php $activeAccountNav = 'settings'; require $path . "Interface/Sections/AccountNav.php"; ?>

            <div class="Profile_Main">
                <div class="Profile_Section">
                    <div class="Order_History_Header">
                        <h3>Settings</h3>
                    </div>

                    <div class="Settings_List">
                        <a href="/HeyDaniel/Interface/Sheets/Profile.php" class="Settings_Row Btn_Nav_Row">
                            <div class="Settings_Row_Text">
                                <p class="Settings_Row_Title">Account Settings</p>
                                <p class="Settings_Row_Desc">Manage your personal information.</p>
                            </div>
                            <i class="fas fa-chevron-right Settings_Row_Chevron" aria-hidden="true"></i>
                        </a>

                        <button type="button" class="Settings_Row Btn_Nav_Row" id="settings-security-toggle">
                            <div class="Settings_Row_Text">
                                <p class="Settings_Row_Title">Security</p>
                                <p class="Settings_Row_Desc">Change your password and manage account security.</p>
                            </div>
                            <i class="fas fa-chevron-right Settings_Row_Chevron" aria-hidden="true"></i>
                        </button>

                        <div class="Settings_Security_Panel" id="settings-security-panel" style="display:none;">
                            <div class="Address_Modal_Field">
                                <label for="settings-current-password">Current Password</label>
                                <input type="password" id="settings-current-password" autocomplete="current-password" />
                            </div>
                            <div class="Address_Modal_Field">
                                <label for="settings-new-password">New Password</label>
                                <input type="password" id="settings-new-password" autocomplete="new-password" />
                            </div>
                            <div class="Address_Modal_Field">
                                <label for="settings-confirm-password">Confirm New Password</label>
                                <input type="password" id="settings-confirm-password" autocomplete="new-password" />
                            </div>
                            <button type="button" class="Profile_Info_Save_Btn" id="settings-security-save">Update Password</button>
                        </div>

                        <button type="button" class="Settings_Row Btn_Nav_Row" data-coming-soon="Privacy settings">
                            <div class="Settings_Row_Text">
                                <p class="Settings_Row_Title">Privacy</p>
                                <p class="Settings_Row_Desc">Manage your privacy preferences.</p>
                            </div>
                            <i class="fas fa-chevron-right Settings_Row_Chevron" aria-hidden="true"></i>
                        </button>

                        <button type="button" class="Settings_Row Btn_Nav_Row" data-coming-soon="Language & Region settings">
                            <div class="Settings_Row_Text">
                                <p class="Settings_Row_Title">Language &amp; Region</p>
                                <p class="Settings_Row_Desc">Set your preferred language and region.</p>
                            </div>
                            <i class="fas fa-chevron-right Settings_Row_Chevron" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
    <?php } else {
        require $path . "Interface/Sections/Credential.php";
    } ?>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            $("#profile-logout-btn").on("click", function () {
                logout();
            });

            $("#settings-security-toggle").on("click", function () {
                $("#settings-security-panel").slideToggle(200);
            });

            $("#settings-security-save").on("click", function () {
                const currentPassword = $("#settings-current-password").val();
                const newPassword = $("#settings-new-password").val();
                const confirmPassword = $("#settings-confirm-password").val();

                if (!currentPassword || !newPassword || !confirmPassword) {
                    alert("Please fill in all password fields.");
                    return;
                }

                $.ajax({
                    method: "POST",
                    url: "/HeyDaniel/Server/index.php",
                    contentType: "application/json",
                    dataType: "json",
                    data: JSON.stringify({
                        action: "change_account_password",
                        device_type: "Web",
                        current_password: currentPassword,
                        new_password: newPassword,
                        confirm_password: confirmPassword
                    }),
                    success: function () {
                        alert("Password updated.");
                        $("#settings-current-password, #settings-new-password, #settings-confirm-password").val("");
                        $("#settings-security-panel").slideUp(200);
                    },
                    error: function (xhr) {
                        const res = JSON.parse(xhr.responseText);
                        alert(res.error || "Failed to update password.");
                    }
                });
            });
        });
    </script>
</body>

</html>
