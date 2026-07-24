<?php
$path = "../../";
$pageTitle = "My Addresses - HeyDaniel";
$metaDescription = "Manage your saved addresses on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php if (isset($_SESSION['user_id'])) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <section class="Container Profile_Layout">
            <?php $activeAccountNav = 'addresses'; require $path . "Interface/Sections/AccountNav.php"; ?>

            <div class="Profile_Main">
                <div class="Profile_Section">
                    <div class="Order_History_Header">
                        <h3>My Addresses</h3>
                    </div>

                    <div class="Address_Book_Grid" id="addresses-grid">
                        <?php for ($i = 0; $i < 2; $i++) { ?>
                            <div class="Skeleton_List_Row">
                                <div class="Skeleton_Block Skeleton_List_Row_Icon"></div>
                                <div class="Skeleton_List_Row_Info">
                                    <div class="Skeleton_Block"></div>
                                    <div class="Skeleton_Block"></div>
                                </div>
                            </div>
                        <?php } ?>
                        <button type="button" class="Add_Address_Card Secondary_Light_Btn Secondary_Light_Btn--dashed" id="add-address-btn">
                            <span class="Add_Address_Plus">+</span> Add New Address
                        </button>
                    </div>

                    <p class="Addresses_Cap_Note">You can add up to 10 addresses.</p>
                </div>
            </div>
        </section>

        <?php require $path . "Interface/Sections/AddressModal.php"; ?>
    <?php } else {
        require $path . "Interface/Sections/Credential.php";
    } ?>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            function loadAddresses() {
                listAddressesRequest().done(function (data) {
                    $("#addresses-grid .Skeleton_List_Row, #addresses-grid .Address_Card").remove();
                    const addresses = data.addresses || [];
                    addresses.forEach(function (address) {
                        buildAddressCard(address).insertBefore("#add-address-btn");
                    });
                    $("#add-address-btn").toggle(addresses.length < 10);
                }).fail(function (xhr) {
                    const res = JSON.parse(xhr.responseText);
                    console.log("loadAddresses failed:", res.error);
                    $("#addresses-grid .Skeleton_List_Row").remove();
                });
            }

            loadAddresses();

            $("#profile-logout-btn").on("click", function () {
                logout();
            });

            initAddressBook("#addresses-grid", "#add-address-btn");
        });
    </script>
</body>

</html>
