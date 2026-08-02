<?php
// Required by Cart.php's routing check when the user has an active order
// being picked — expects $path/$pdo/session state to already be set up by
// the caller, since this is only ever reached that way, not linked to
// directly.
$pageTitle = "Order Processing - HeyDaniel";
$metaDescription = "Track your order as it's being picked and packed by HeyDaniel.";
?>
<!DOCTYPE html>
<html lang="en">
<?php include_once $path . "Interface/Sections/Head.php"; ?>

<body>
    <?php require $path . "Interface/Sections/Header.php"; ?>

    <section class="Container Profile_Layout">
        <?php $activeAccountNav = 'cart'; require $path . "Interface/Sections/AccountNav.php"; ?>

        <div class="Profile_Main">
            <div class="Cart_Layout">
                <div class="Cart_Items">
                    <div class="Section_Header">
                        <h2><span id="cart-section-title">Order Processing</span> <span class="Section_Header_Count" id="cart-item-count"></span></h2>
                        <button type="button" class="Order_Action_Btn" id="cancel-order-btn" style="display:none;"><img src="<?php echo $path ?>Assets/Icons/close.svg" alt="" />Cancel Order</button>
                        <button type="button" class="Order_Action_Btn" id="reschedule-delivery-btn" style="display:none;"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5m-9-6h.008v.008H12v-.008ZM12 15h.008v.008H12V15Zm0 2.25h.008v.008H12v-.008ZM9.75 15h.008v.008H9.75V15Zm0 2.25h.008v.008H9.75v-.008ZM7.5 15h.008v.008H7.5V15Zm0 2.25h.008v.008H7.5v-.008Zm6.75-4.5h.008v.008h-.008v-.008Zm0 2.25h.008v.008h-.008V15Zm0 2.25h.008v.008h-.008v-.008Zm2.25-4.5h.008v.008H16.5v-.008Zm0 2.25h.008v.008H16.5V15Z" /></svg>Reschedule</button>
                        <div class="Cart_Sort_Row" id="cart-sort-row" style="display:none;">
                            <label class="Store_Sort_Label" for="cart-sort">Sort by</label>
                            <select id="cart-sort" class="Store_Select Store_Select--sm">
                                <option value="status">Status</option>
                                <option value="availability">Availability</option>
                                <option value="name">Name (A-Z)</option>
                            </select>
                        </div>
                    </div>

                    <div class="Order_Progress_Tracker" id="order-progress-tracker">
                        <div class="Order_Progress_Eta_Row">
                            <div class="Order_Progress_Eta_Left">
                                <span class="Icon_Circle Order_Progress_Eta_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span>
                                <div class="Order_Progress_Eta_Text">
                                    <span class="Order_Progress_Eta_Label">Estimated delivery</span>
                                    <h3 class="Order_Progress_Eta_Window" id="order-progress-eta-window">&mdash;</h3>
                                    <span class="Order_Progress_Eta_Pending_Badge" id="order-progress-eta-pending-badge" style="display:none;">Pending</span>
                                    <span class="Order_Progress_Eta_Away">
                                        <i class="fas fa-clock" aria-hidden="true"></i>
                                        <span id="order-progress-eta-minutes">&mdash;</span>
                                        <span class="Order_Progress_Eta_Away_Dot" id="order-progress-eta-away-dot">&bull;</span>
                                        <span id="order-progress-eta-note">Arriving soon</span>
                                    </span>
                                </div>
                            </div>

                            <div class="Order_Progress_Mini_Status" id="order-progress-mini-status">
                                <span class="Icon_Circle Order_Progress_Mini_Status_Icon" id="order-progress-mini-status-icon"></span>
                                <div class="Order_Progress_Mini_Status_Text">
                                    <strong id="order-progress-mini-status-title">Getting your order ready</strong>
                                    <span id="order-progress-mini-status-sub">Your shopper is picking your items.</span>
                                </div>
                                <div class="Order_Progress_Mini_Dots" id="order-progress-mini-dots"></div>
                            </div>
                        </div>

                        <div class="Order_Progress_Steps">
                            <div class="Order_Progress_Step" data-step="1">
                                <span class="Icon_Circle Order_Progress_Step_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg></span>
                                <span class="Order_Progress_Step_Label">Order Received</span>
                                <span class="Order_Progress_Step_Time" id="order-progress-step-1-time">&mdash;</span>
                            </div>
                            <div class="Order_Progress_Bar" id="order-progress-bar-1"><div class="Order_Progress_Bar_Fill"></div></div>

                            <div class="Order_Progress_Step" data-step="2">
                                <span class="Icon_Circle Order_Progress_Step_Icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7v-1a2 2 0 0 1 2 -2h2" /><path d="M4 17v1a2 2 0 0 0 2 2h2" /><path d="M16 4h2a2 2 0 0 1 2 2v1" /><path d="M16 20h2a2 2 0 0 0 2 -2v-1" /><path d="M5 11h1v2h-1z" /><path d="M10 11l0 2" /><path d="M14 11h1v2h-1z" /><path d="M19 11l0 2" /></svg></span>
                                <span class="Order_Progress_Step_Label">Picking Items</span>
                                <span class="Order_Progress_Step_Time" id="order-progress-step-2-time">Upcoming</span>
                            </div>
                            <div class="Order_Progress_Bar" id="order-progress-bar-2"><div class="Order_Progress_Bar_Fill"></div></div>

                            <div class="Order_Progress_Step" data-step="3">
                                <span class="Icon_Circle Order_Progress_Step_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg></span>
                                <span class="Order_Progress_Step_Label">On the Way</span>
                                <span class="Order_Progress_Step_Time" id="order-progress-step-3-time">Upcoming</span>
                            </div>
                            <div class="Order_Progress_Bar" id="order-progress-bar-3"><div class="Order_Progress_Bar_Fill"></div></div>

                            <div class="Order_Progress_Step" data-step="4">
                                <span class="Icon_Circle Order_Progress_Step_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg></span>
                                <span class="Order_Progress_Step_Label">Delivered</span>
                                <span class="Order_Progress_Step_Time" id="order-progress-step-4-time">Upcoming</span>
                            </div>
                        </div>
                    </div>

                    <div class="Quality_Banner">
                        <span class="Icon_Circle Quality_Banner_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg></span>
                        <div class="Quality_Banner_Text">
                            <strong>We prioritize quality &amp; freshness</strong>
                            <span>If any item is unavailable, we'll find you the best alternative.</span>
                        </div>
                        <a href="#" class="Quality_Banner_Link">Learn more <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                    </div>

                    <p id="cart-empty-message" class="Store_Empty" style="display:none;">No items are currently being processed.</p>

                    <div class="Row_Card_List" id="cart-items-container">
                        <?php for ($i = 0; $i < 3; $i++) { ?>
                            <div class="Skeleton_Row_Card">
                                <div class="Skeleton_Block Skeleton_Row_Card_Image"></div>
                                <div class="Skeleton_Row_Card_Info">
                                    <div class="Skeleton_Block"></div>
                                    <div class="Skeleton_Block"></div>
                                    <div class="Skeleton_Block"></div>
                                </div>
                                <div class="Skeleton_Block Skeleton_Row_Card_Price"></div>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="List_Pagination" id="cart-pagination" style="display:none;">
                        <span class="List_Pagination_Label" id="cart-pagination-label"></span>
                        <div class="List_Pagination_Pages">
                            <button type="button" class="List_Page_Btn List_Page_Nav" id="cart-page-prev" aria-label="Previous page">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                            </button>
                            <span class="List_Pagination_Numbers" id="cart-pagination-numbers"></span>
                            <button type="button" class="List_Page_Btn List_Page_Nav" id="cart-page-next" aria-label="Next page">
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <aside class="Cart_Summary" id="cart-summary" style="display:none;">
                    <div class="Order_Status_Card">
                        <div class="Order_Status_Head">
                            <span class="Order_Status_Dot"></span>
                            <span class="Order_Status_Head_Label">Current Status</span>
                        </div>
                        <h3 class="Order_Status_Headline" id="order-status-headline">Your shopper is picking your items</h3>
                        <p class="Order_Status_Sub" id="order-status-sub">We'll update this as your order moves along.</p>

                        <div class="Order_Status_Shopper">
                            <span class="Order_Status_Shopper_Photo"><img src="<?php echo $path ?>Assets/Icons/user.svg" alt="" /></span>
                            <div class="Order_Status_Shopper_Text">
                                <span class="Order_Status_Shopper_Label">Your Shopper</span>
                                <span class="Order_Status_Shopper_Name">
                                    Trevor Santiago
                                    <span class="Order_Status_Shopper_Rating"><i class="fas fa-star" aria-hidden="true"></i> 4.7</span>
                                    <span class="Order_Status_Shopper_Badge">Top Shopper</span>
                                </span>
                            </div>
                            <div class="Order_Status_Shopper_Actions">
                                <button type="button" class="Icon_Circle Order_Status_Action_Btn" id="order-status-message-btn" aria-label="Message your shopper">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                                </button>
                                <button type="button" class="Icon_Circle Order_Status_Action_Btn" id="order-status-call-btn" aria-label="Call your shopper">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 0 0-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97a1.125 1.125 0 0 0 .417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg>
                                </button>
                            </div>
                        </div>
                        <span class="Order_Status_Updated" id="order-status-updated">Last updated: just now</span>
                    </div>

                    <div class="Order_Status_Card Order_Message_Placeholder">
                        <div class="Order_Status_Detail_Row">
                            <span class="Icon_Circle Order_Status_Detail_Icon"><i class="fas fa-comment-dots" aria-hidden="true"></i></span>
                            <span class="Order_Status_Detail_Label">Messages</span>
                        </div>
                        <p class="Order_Message_Placeholder_Text" id="order-message-preview">Chat with your shopper about this order.</p>
                        <button type="button" class="Secondary_Light_Btn Secondary_Light_Btn--full" id="order-message-open-btn">
                            <i class="fas fa-comment-dots" aria-hidden="true"></i> Open chat
                        </button>
                    </div>

                    <div class="Order_Status_Card">
                        <div class="Order_Status_Detail_Row">
                            <span class="Icon_Circle Order_Status_Detail_Icon"><i class="fas fa-clock" aria-hidden="true"></i></span>
                            <span class="Order_Status_Detail_Label">Estimated Delivery</span>
                        </div>
                        <h3 class="Order_Status_Detail_Window" id="order-status-eta-window">&mdash;</h3>
                        <div class="Order_Status_Detail_Foot">
                            <span class="Order_Status_Detail_Date" id="order-status-eta-date">&mdash;</span>
                            <span class="Order_Status_Detail_Pill" id="order-status-eta-pill">&mdash;</span>
                        </div>
                    </div>

                    <div class="Order_Status_Card Order_Status_Toggle_Card">
                        <span class="Icon_Circle Order_Status_Detail_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0M3.124 7.5A8.969 8.969 0 0 1 5.292 3m13.416 0a8.969 8.969 0 0 1 2.168 4.5" /></svg></span>
                        <div class="Order_Status_Toggle_Text">
                            <span class="Order_Status_Detail_Label">Get real-time updates</span>
                            <p>We'll notify you when your order is out for delivery.</p>
                        </div>
                        <button type="button" class="Toggle_Switch Toggle_On" id="order-status-notify-toggle" aria-label="Toggle real-time updates" aria-pressed="true">
                            <span class="Toggle_Knob"></span>
                        </button>
                    </div>

                    <div class="Employee_Profile_Card" id="cart-employee-card">
                        <div class="Employee_Profile_Header">
                            <div class="Employee_Profile_Photo_Wrap">
                                <span class="Employee_Profile_Photo"><img src="<?php echo $path ?>Assets/Icons/user.svg" alt="" /></span>
                                <span class="Employee_Profile_Photo_Status_Dot"></span>
                            </div>
                            <h3 class="Employee_Profile_Name">Trevor Santiago</h3>
                            <p class="Employee_Profile_Title">Personal Shopper</p>
                            <p class="Employee_Profile_Tier">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 8.25 6.75-5.25 6.75 5.25-6.75 12-6.75-12Zm0 0h13.5" /></svg>
                                Platinum <span class="Employee_Profile_Tier_Dot">&bull;</span> Level 4
                            </p>
                        </div>

                        <div class="Employee_Profile_Contact">
                            <div class="Employee_Profile_Contact_Item">
                                <span class="Icon_Circle Employee_Profile_Contact_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0-.83.67-1.5 1.5-1.5h16.5c.83 0 1.5.67 1.5 1.5v10.5c0 .83-.67 1.5-1.5 1.5H3.75a1.5 1.5 0 0 1-1.5-1.5V6.75Z" /><path stroke-linecap="round" stroke-linejoin="round" d="m3 7 9 6 9-6" /></svg></span>
                                <span class="Employee_Profile_Contact_Label">Email</span>
                                <span class="Employee_Profile_Contact_Value">trevor.santiago@heydaniel.com</span>
                            </div>
                            <div class="Employee_Profile_Contact_Item">
                                <span class="Icon_Circle Employee_Profile_Contact_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 0 0-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97a1.125 1.125 0 0 0 .417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" /></svg></span>
                                <span class="Employee_Profile_Contact_Label">Phone</span>
                                <span class="Employee_Profile_Contact_Value">234-232-1234</span>
                            </div>
                            <div class="Employee_Profile_Contact_Item">
                                <span class="Icon_Circle Employee_Profile_Contact_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg></span>
                                <span class="Employee_Profile_Contact_Label">Status</span>
                                <span class="Employee_Profile_Contact_Value Employee_Profile_Status_Active">Picking your order <i class="Employee_Profile_Status_Dot"></i></span>
                            </div>
                        </div>

                        <div class="Employee_Profile_Rating">
                            <div class="Employee_Profile_Rating_Head">
                                <span class="Employee_Profile_Rating_Label">Satisfaction Rating</span>
                                <span class="Employee_Profile_Rating_Score"><strong>4.7</strong> out of 5</span>
                            </div>
                            <div class="Employee_Profile_Rating_Foot">
                                <span class="Employee_Profile_Stars" aria-label="4.7 out of 5 stars">
                                    <span class="Employee_Profile_Star Employee_Profile_Star_Full">&#9733;</span>
                                    <span class="Employee_Profile_Star Employee_Profile_Star_Full">&#9733;</span>
                                    <span class="Employee_Profile_Star Employee_Profile_Star_Full">&#9733;</span>
                                    <span class="Employee_Profile_Star Employee_Profile_Star_Full">&#9733;</span>
                                    <span class="Employee_Profile_Star Employee_Profile_Star_Half">&#9733;</span>
                                </span>
                                <span class="Employee_Profile_Review_Count">(128 reviews)</span>
                            </div>
                        </div>

                        <div class="Employee_Profile_Stats">
                            <div class="Employee_Profile_Stat">
                                <span class="Icon_Circle Employee_Profile_Stat_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 7.5V6.75a3.75 3.75 0 1 1 7.5 0v.75m-8.25 3v6.75a2.25 2.25 0 0 0 2.25 2.25h6a2.25 2.25 0 0 0 2.25-2.25v-6.75m-13.5 0h13.5m-13.5 0a1.5 1.5 0 0 1-1.5-1.5V9a1.5 1.5 0 0 1 1.5-1.5h13.5A1.5 1.5 0 0 1 21 9v.75a1.5 1.5 0 0 1-1.5 1.5" /></svg></span>
                                <div class="Employee_Profile_Stat_Body">
                                    <span class="Employee_Profile_Stat_Label">Orders Handled</span>
                                    <span class="Employee_Profile_Stat_Value">248</span>
                                </div>
                                <div class="Employee_Profile_Stat_Aside">
                                    <span class="Employee_Profile_Stat_Aside_Label">This month</span>
                                    <span class="Employee_Profile_Stat_Aside_Value">18 orders</span>
                                </div>
                            </div>

                            <div class="Employee_Profile_Stat">
                                <span class="Icon_Circle Employee_Profile_Stat_Icon Employee_Profile_Fulfillment_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.35-1.045m0 0-5.25-1.5m5.25 1.5-1.5 5.25" /></svg></span>
                                <div class="Employee_Profile_Stat_Body">
                                    <span class="Employee_Profile_Stat_Label">Average Fulfillment</span>
                                    <span class="Employee_Profile_Stat_Value">97%</span>
                                </div>
                                <div class="Employee_Profile_Stat_Aside">
                                    <span class="Employee_Profile_Stat_Aside_Label">This month</span>
                                    <span class="Employee_Profile_Stat_Aside_Value">+4.3%</span>
                                </div>
                            </div>

                            <div class="Employee_Profile_Stat">
                                <span class="Icon_Circle Employee_Profile_Stat_Icon Employee_Profile_Rating_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.964 0a9 9 0 1 0-11.964 0m11.964 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg></span>
                                <div class="Employee_Profile_Stat_Body">
                                    <span class="Employee_Profile_Stat_Label">Customer Rating</span>
                                    <span class="Employee_Profile_Stat_Value">4.7 / 5</span>
                                </div>
                                <div class="Employee_Profile_Stat_Aside">
                                    <span class="Employee_Profile_Stat_Aside_Label">128 reviews</span>
                                    <span class="Employee_Profile_Stat_Aside_Value">97% positive</span>
                                </div>
                            </div>
                        </div>

                        <div class="Employee_Profile_Level_Section">
                            <div class="Employee_Profile_Level_Head">
                                <span class="Employee_Profile_Level_Head_Label">Employee Level</span>
                                <span class="Employee_Profile_Level_Badge">Level 4</span>
                            </div>
                            <div class="Employee_Profile_Level_Foot">
                                <span class="Icon_Circle Employee_Profile_Stat_Icon Employee_Profile_Level_Icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m5.25 8.25 6.75-5.25 6.75 5.25-6.75 12-6.75-12Zm0 0h13.5M9 8.25l3 12 3-12M9 8.25 12 3m0 5.25L15 3" /></svg></span>
                                <span class="Employee_Profile_Level_Value">Platinum</span>
                            </div>

                            <div class="Employee_Profile_Level_Track">
                                <span class="Employee_Profile_Level_Dot"></span>
                                <span class="Employee_Profile_Level_Dot"></span>
                                <span class="Employee_Profile_Level_Dot"></span>
                                <span class="Employee_Profile_Level_Dot Employee_Profile_Level_Dot_Active"></span>
                                <span class="Employee_Profile_Level_Dot"></span>
                            </div>
                            <div class="Employee_Profile_Level_Track_Labels">
                                <span>Level 1</span>
                                <span>Level 2</span>
                                <span>Level 3</span>
                                <span class="Employee_Profile_Level_Track_Label_Active">Level 4</span>
                                <span>Level 5</span>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    <div class="Address_Modal_Overlay" id="reschedule-modal-overlay" style="display:none;">
        <div class="Address_Modal_Box">
            <div class="Address_Modal_Header">
                <div class="Reschedule_Modal_Title_Row">
                    <span class="Reschedule_Modal_Icon"><i class="fas fa-calendar-plus" aria-hidden="true"></i></span>
                    <div>
                        <h3>Reschedule</h3>
                        <p class="Reschedule_Modal_Subtitle">Select a new time for today</p>
                    </div>
                </div>
                <button type="button" class="Address_Modal_Close_Btn Btn_Icon_Ghost" id="reschedule-modal-close" aria-label="Close">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
            <div class="Address_Modal_Body">
                <span class="Reschedule_Date_Chip"><i class="fas fa-calendar-alt" aria-hidden="true"></i> Today &middot; <span id="reschedule-date-text"></span></span>
                <div class="Reschedule_Slot_Options" id="reschedule-window-options"></div>
                <p class="credential-error" id="reschedule-modal-error" style="display:none;"></p>
            </div>
            <div class="Address_Modal_Footer Reschedule_Modal_Footer">
                <button type="button" class="Profile_Info_Save_Btn Profile_Info_Save_Btn--full" id="reschedule-modal-save" disabled><i class="fas fa-check-circle" aria-hidden="true"></i> Confirm reschedule</button>
                <button type="button" class="Secondary_Light_Btn Secondary_Light_Btn--full" id="reschedule-modal-cancel"><i class="fas fa-times" aria-hidden="true"></i> Cancel</button>
            </div>
        </div>
    </div>

    <!-- Chat modal - a slide-in drawer matching #side-bar (Header.php, the
         zip-code-entry panel) exactly, per request: native <dialog>, same
         showModal()/slide-transition/backdrop-click lifecycle (see the JS
         below), same .box-title-style colored header. Not the centered
         .Modal/.Zip_Conflict_Header popup used for the zip *conflict*
         warning - that one's a different, unrelated pattern. -->
    <dialog id="chat-modal">
        <div class="box-title">
            <button type="button" class="close-icon-location Btn_Icon_Ghost Btn_Icon_Ghost--inverse" id="chat-modal-close" aria-label="Close">
                <img src="<?php echo $path ?>Assets/Icons/close.svg" alt="" />
            </button>
            <h1>Chat with your shopper</h1>
        </div>
        <div class="Chat_Modal_Body" id="chat-modal-messages">
            <p class="Chat_Modal_Empty" id="chat-modal-empty" style="display:none;">No messages yet — say hello!</p>
        </div>
        <div class="Chat_Modal_Footer">
            <input type="text" class="Chat_Modal_Input" id="chat-modal-input" placeholder="Type a message…" maxlength="2000" />
            <button type="button" class="Chat_Modal_Send_Btn" id="chat-modal-send" aria-label="Send message">
                <i class="fas fa-paper-plane" aria-hidden="true"></i>
            </button>
        </div>
        <p class="credential-error" id="chat-modal-error" style="display:none;"></p>
    </dialog>

    <?php require $path . "Interface/Sections/Footer.php"; ?>

    <script>
        $(document).ready(function () {
            cartIsProcessing = true;
            summary();
            initOrderStatusToggle();

            // Live updates: a shopper picking this order can change its status/
            // item state server-side at any moment, and none of that reaches this
            // tab on its own. Primary path is a push from the order-status
            // WebSocket daemon (Server/WebSocket/run.php - a separate process,
            // see that file for how to start it) — cheap regardless of how many
            // tabs are watching, since the daemon does one DB check per order
            // per tick and fans the signal out, instead of every tab polling on
            // its own. cartItem(false) keeps whatever sort/page the customer is
            // on instead of resetting it, same as the reschedule-save flow.
            let liveUpdateFallbackTimer = null;
            function refreshOrder() {
                cartItem(false);
                summary();
                // The live-update signal (WebSocket push, or the polling
                // fallback below) fires for any change to this order,
                // messages included (the daemon's fingerprint covers the
                // Messages table too) - only worth re-fetching the thread if
                // the modal's actually open. loadChatMessages() is declared
                // further down in this same ready() block, but function
                // declarations are hoisted, so it's already callable here.
                if ($("#chat-modal").hasClass("Sidebar_Open")) {
                    loadChatMessages();
                }
                // Order events can create notification rows server-side -
                // re-check here so the unread badge updates and the ding
                // (notificationsBadge()'s count-increase check) fires live,
                // not just on the next page load.
                notificationsBadge();
            }
            function startPollingFallback() {
                if (liveUpdateFallbackTimer) {
                    return;
                }
                liveUpdateFallbackTimer = setInterval(refreshOrder, 15000);
            }

            cartItem(true, function () {
                const socket = connectOrderStatusSocket(window.currentOrderId, refreshOrder);
                if (!socket) {
                    startPollingFallback();
                    return;
                }
                // The daemon not running, or dropping mid-session, shouldn't
                // mean the page stops updating — fall back to polling rather
                // than going silent.
                socket.onerror = startPollingFallback;
                socket.onclose = startPollingFallback;
            });

            $("#profile-logout-btn").on("click", function () {
                logout();
            });

            $("#cancel-order-btn").on("click", function () {
                showAppConfirm("Cancel this order? This can't be undone.", function () {
                    cancelOrder()
                        .done(function () {
                            location.reload();
                        })
                        .fail(function (xhr) {
                            const res = JSON.parse(xhr.responseText);
                            showAppAlert(res.error || "Failed to cancel order.");
                        });
                }, { title: "Cancel order", confirmLabel: "Cancel order" });
            });

            $("#reschedule-delivery-btn").on("click", function () {
                const $options = $("#reschedule-window-options").empty();
                $("#reschedule-modal-save").prop("disabled", true);
                $("#reschedule-modal-error").hide();
                $("#reschedule-date-text").text(new Date().toLocaleDateString(undefined, { month: "long", day: "numeric", year: "numeric" }));
                $("#reschedule-modal-overlay").show();

                getRescheduleWindows()
                    .done(function (res) {
                        (res.windows || []).forEach(function (win) {
                            $("<label></label>")
                                .addClass("Reschedule_Slot_Card")
                                .attr("data-window", win.label)
                                .append(
                                    $("<input type='radio' name='reschedule-window' />").val(win.label),
                                    $("<div></div>").addClass("Reschedule_Slot_Info").append(
                                        $("<span></span>").addClass("Reschedule_Slot_Time").text(win.time || ""),
                                        $("<span></span>").addClass("Reschedule_Slot_Meta").text(win.day || "")
                                    )
                                )
                                .appendTo($options);
                        });
                    })
                    .fail(function () {
                        $("#reschedule-modal-error").text("Couldn't load delivery windows.").show();
                    });
            });

            $("#reschedule-modal-close, #reschedule-modal-cancel").on("click", function () {
                $("#reschedule-modal-overlay").hide();
            });

            $("#reschedule-modal-overlay").on("click", function (e) {
                if (e.target === this) {
                    $("#reschedule-modal-overlay").hide();
                }
            });

            $("#reschedule-window-options").on("change", "input[name='reschedule-window']", function () {
                $("#reschedule-window-options .Reschedule_Slot_Card").removeClass("Selected");
                $(this).closest(".Reschedule_Slot_Card").addClass("Selected");
                $("#reschedule-modal-save").prop("disabled", false);
                $("#reschedule-modal-error").hide();
            });

            $("#reschedule-modal-save").on("click", function () {
                const windowLabel = $("#reschedule-window-options input[name='reschedule-window']:checked").val();
                if (!windowLabel) {
                    $("#reschedule-modal-error").text("Pick a delivery window first.").show();
                    return;
                }
                rescheduleDelivery(windowLabel)
                    .done(function () {
                        $("#reschedule-modal-overlay").hide();
                        cartItem(false);
                    })
                    .fail(function (xhr) {
                        const res = JSON.parse(xhr.responseText);
                        $("#reschedule-modal-error").text(res.error || "Failed to reschedule delivery.").show();
                    });
            });

            $("#cart-sort").on("change", function () {
                setCartSort($(this).val());
            });

            $("#cart-pagination-numbers").on("click", ".List_Page_Btn", function () {
                setCartPage(parseInt($(this).data("page"), 10));
            });

            $("#cart-page-prev").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setCartPage(cartPageCurrentPage - 1);
                }
            });

            $("#cart-page-next").on("click", function () {
                if (!$(this).prop("disabled")) {
                    setCartPage(cartPageCurrentPage + 1);
                }
            });

            $("#order-status-call-btn").on("click", function () {
                showAppAlert("Calling your shopper is coming soon.");
            });

            // ---------- Chat modal ----------
            function scrollChatToBottom() {
                const $body = $("#chat-modal-messages");
                $body.scrollTop($body[0].scrollHeight);
            }

            function renderChatMessages(messages) {
                const $body = $("#chat-modal-messages");
                $body.find(".Chat_Message").remove();
                $("#chat-modal-empty").toggle(messages.length === 0);

                messages.forEach(function (msg) {
                    const isCustomer = msg.sender_type === "customer";
                    const time = new Date(msg.date_sent.replace(" ", "T")).toLocaleTimeString(undefined, { hour: "numeric", minute: "2-digit" });
                    $("<div></div>")
                        .addClass("Chat_Message")
                        .addClass(isCustomer ? "Chat_Message_Customer" : "Chat_Message_Employee")
                        .append(
                            $("<span></span>").text(msg.message),
                            $("<span></span>").addClass("Chat_Message_Time").text(time)
                        )
                        .appendTo($body);
                });

                scrollChatToBottom();
            }

            function loadChatMessages() {
                if (!window.currentOrderId) {
                    return;
                }
                fetchOrderMessages(window.currentOrderId, renderChatMessages, function (message) {
                    $("#chat-modal-error").text(message).show();
                });
            }

            // Slide-in lifecycle deliberately matches #side-bar's
            // openSidebar()/closeSidebar() in Interaction.js exactly (see
            // that file's comments for why each piece is needed) - native
            // showModal(), a requestAnimationFrame before adding the
            // slide-open class so the transition actually animates instead
            // of snapping straight to open, and the dialog only formally
            // close()s once its slide-out transition finishes (or
            // immediately via the native "close" event, e.g. Escape).
            const $chatModal = $("#chat-modal");

            function openChatModal() {
                if (!window.currentOrderId) {
                    showAppAlert("Your order isn't ready yet — try again in a moment.");
                    return;
                }
                $("#chat-modal-error").hide();
                $chatModal.get(0).showModal();
                $("html, body").addClass("No_Scroll");
                requestAnimationFrame(function () {
                    $chatModal.addClass("Sidebar_Open");
                });
                $("#chat-modal-input").val("").trigger("focus");
                loadChatMessages();
            }

            function closeChatModal() {
                $chatModal.removeClass("Sidebar_Open");
            }

            $chatModal.on("transitionend", function (e) {
                if (e.originalEvent.propertyName === "right" && !$chatModal.hasClass("Sidebar_Open")) {
                    this.close();
                }
            });

            $chatModal.on("click", function (e) {
                const rect = this.getBoundingClientRect();
                const clickedInsidePanel = e.clientX >= rect.left && e.clientX <= rect.right
                    && e.clientY >= rect.top && e.clientY <= rect.bottom;

                if (!clickedInsidePanel) {
                    closeChatModal();
                }
            });

            $chatModal.on("close", function () {
                $chatModal.removeClass("Sidebar_Open");
                $("html, body").removeClass("No_Scroll");
            });

            $("#order-status-message-btn, #order-message-open-btn").on("click", openChatModal);

            $("#chat-modal-close").on("click", closeChatModal);

            function sendChatMessage() {
                const $input = $("#chat-modal-input");
                const message = $input.val().trim();
                if (!message) {
                    return;
                }

                $("#chat-modal-error").hide();
                $("#chat-modal-send").prop("disabled", true);

                sendOrderMessage(window.currentOrderId, message, function () {
                    $input.val("");
                    $("#chat-modal-send").prop("disabled", false);
                    loadChatMessages();
                }, function (errorMessage) {
                    $("#chat-modal-send").prop("disabled", false);
                    $("#chat-modal-error").text(errorMessage).show();
                });
            }

            $("#chat-modal-send").on("click", sendChatMessage);

            $("#chat-modal-input").on("keydown", function (e) {
                if (e.key === "Enter") {
                    sendChatMessage();
                }
            });

        });
    </script>

</body>

</html>
