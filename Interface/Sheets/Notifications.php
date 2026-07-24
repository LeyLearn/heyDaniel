<?php
$path = "../../";
$pageTitle = "Notifications - HeyDaniel";
$metaDescription = "View your notifications on HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php if (isset($_SESSION['user_id'])) { ?>
        <?php require $path . "Interface/Sections/Header.php"; ?>

        <section class="Container Profile_Layout">
            <?php $activeAccountNav = 'notifications'; require $path . "Interface/Sections/AccountNav.php"; ?>

            <div class="Profile_Main">
                <div class="Profile_Section">
                    <div class="Order_History_Header">
                        <h3>Notifications</h3>
                    </div>

                    <div id="notifications-empty" class="Order_Empty" style="display:none;">
                        <div class="Order_Empty_Icon_Wrap">
                            <img src="<?php echo $path ?>Assets/Icons/notification.svg" alt="" class="Order_Empty_Icon" />
                        </div>
                        <p class="Order_Empty_Text">No notifications yet.</p>
                    </div>

                    <div id="notifications-list">
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

            $.ajax({
                method: "POST",
                url: "/HeyDaniel/Server/index.php",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify({
                    action: "notifications",
                    device_type: "Web"
                }),
                success: function (data) {
                    const notifications = data.notifications || [];
                    const $list = $("#notifications-list").empty();

                    if (!notifications.length) {
                        $("#notifications-empty").show();
                        return;
                    }

                    notifications.forEach(function (notification) {
                        const notifDate = new Date(notification.date_added);
                        const formattedDate = !isNaN(notifDate)
                            ? notifDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
                            : notification.date_added;

                        $list.append(
                            $('<div class="Notification_Row"></div>').toggleClass('Notification_Unread', !notification.is_read).data('id', notification.id).append(
                                $('<div class="Notification_Icon Icon_Circle"></div>').append('<i class="fas fa-bell" aria-hidden="true"></i>'),
                                $('<div class="Notification_Info"></div>').append(
                                    $('<p class="Notification_Title"></p>').text(notification.title),
                                    $('<p class="Notification_Body"></p>').text(notification.body)
                                ),
                                $('<span class="Notification_Date"></span>').text(formattedDate)
                            )
                        );
                    });
                },
                error: function (xhr) {
                    const res = JSON.parse(xhr.responseText);
                    console.log("notifications failed:", res.error);
                    $("#notifications-list").empty();
                    $("#notifications-empty").show();
                }
            });

            $(document).on("click", ".Notification_Row.Notification_Unread", function () {
                const $row = $(this);
                const notificationId = $row.data('id');

                $.ajax({
                    method: "POST",
                    url: "/HeyDaniel/Server/index.php",
                    contentType: "application/json",
                    dataType: "json",
                    data: JSON.stringify({
                        action: "mark_notification_read",
                        device_type: "Web",
                        notification_id: notificationId
                    }),
                    success: function (data) {
                        if (data.success) {
                            $row.removeClass("Notification_Unread");
                        }
                    },
                    error: function (xhr) {
                        const res = JSON.parse(xhr.responseText);
                        console.log("mark_notification_read failed:", res.error);
                    }
                });
            });
        });
    </script>
</body>

</html>
