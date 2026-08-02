
// Maps a Stripe PaymentMethod's card.brand (e.g. "visa", "mastercard") to a
// realistic full-color logo shipped locally in Assets/Icons/CardBrands —
// falls back to null (generic card icon) for brands we don't carry an asset
// for, e.g. Stripe's own "unknown".
const CARD_BRAND_LOGOS = ["visa", "mastercard", "amex", "discover", "diners", "jcb", "unionpay"];

function cardBrandLogoUrl(brand) {
    const normalized = (brand || "").toLowerCase();
    if (CARD_BRAND_LOGOS.indexOf(normalized) === -1) {
        return null;
    }
    return "/HeyDaniel/Assets/Icons/CardBrands/" + normalized + ".svg";
}

function cardBrandIconHTML(brand) {
    const logoUrl = cardBrandLogoUrl(brand);
    if (logoUrl) {
        return '<img src="' + logoUrl + '" alt="' + brand + '" class="Card_Brand_Logo" />';
    }
    return '<i class="fas fa-credit-card" aria-hidden="true"></i>';
}

function setButtonLoading(callback, selector) {
    const $btn = $(selector);
    $btn.prop("disabled", true);
    $btn.html('<img src="../../Assets/Icons/load.svg" alt="HeyDaniel loading..." class="btn-loading-icon">');

    setTimeout(() => {
        callback();
    }, 1000);
}

function stopButtonLoading(selector, text) {
    const $btn = $(selector);
    $btn.html(text);
    $btn.prop("disabled", false);
}

// Cart/wishlist actions require an account; guests are sent to sign in
// instead of firing a request that the backend would just reject.
function getIsLoggedIn() {
    if (typeof isLoggedIn !== "undefined") {
        return !!isLoggedIn;
    }
    if (typeof isloggedin !== "undefined") {
        return !!isloggedin;
    }
    if (typeof window !== "undefined") {
        return !!(window.isLoggedIn || window.isloggedin);
    }
    return false;
}

function getIsMember() {
    if (typeof isMember !== "undefined") {
        return !!isMember;
    }
    if (typeof window !== "undefined") {
        return !!window.isMember;
    }
    return false;
}

// Same-day orders that are pending/processing take over the cart: further
// "add" actions go into that live order instead of a fresh Cart, so the
// button text and cart icon need to reflect that instead of the normal cart.
function getHasActiveOrder() {
    if (typeof window !== "undefined") {
        return !!window.hasActiveOrder;
    }
    return false;
}

// Inline (not <img src>) so the icon's stroke="currentColor" actually
// inherits the nav's color instead of rendering as an isolated black image,
// and so the process icon can be spun via CSS animation.
const CART_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" /></svg>';
// Clock — Pending.
const PENDING_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 7.5V12l3 2" /></svg>';
// Spinning ring — Processing.
const PROCESS_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" class="Process_Spin"><path d="M12 3a9 9 0 0 1 9 9" opacity="0.25" /><path d="M21 12a9 9 0 0 1-9 9" /><path d="M12 21a9 9 0 0 1-9-9" opacity="0.25" /><path d="M3 12a9 9 0 0 1 9-9" /></svg>';
// Truck (same path as Assets/Icons/truck.svg) — Shipped.
const SHIPPED_ICON_SVG = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>';

const STATUS_ICON_SVG = {
    Pending: PENDING_ICON_SVG,
    Processing: PROCESS_ICON_SVG,
    Shipped: SHIPPED_ICON_SVG
};

const ORDER_STATUS_CLASSES = "Status_Pending Status_Processing Status_Shipped";
const DISPLAY_ORDER_STATUSES = ["Pending", "Processing", "Shipped"];

// The backend stores/sends OrderStatus lowercase ("pending", "processing", ...)
// - every status comparison and lookup table in this file expects the
// capitalized form ("Pending"), so any order_status value coming from the
// API gets capitalized here, once, right at the boundary.
function capitalizeOrderStatus(status) {
    if (!status) {
        return status;
    }
    return status.charAt(0).toUpperCase() + status.slice(1).toLowerCase();
}

// Words the label rotates through every 5s while a status is active (a
// single-item list just displays statically — Pending/Shipped read better
// fixed, only Processing rotates through its list). The icon's aria-label
// stays fixed on the real status name (set once below) regardless, so
// screen readers aren't spammed with updates every 5 seconds.
const PENDING_DOTS_HTML = 'Pending<span class="Dot_Pulse"><span></span><span></span><span></span></span>';

const STATUS_WORD_ROTATION = {
    Pending: [PENDING_DOTS_HTML],
    Processing: ["Processing...", "Sorting...", "Assembling...", "Packaging..."],
    Shipped: ["En route"]
};
const STATUS_ROTATION_INTERVAL_MS = 5000;
let statusRotationTimer = null;

// Re-applies the active-order UI state (nav cart icons + any already-painted
// "Add to cart" buttons) — called whenever fresh has_active_order/order_status
// values come back from DeviceCheck()/cartIcon(), since those two AJAX calls
// race against product-card rendering on page load.
//
// order_status is deliberately broader than has_active_order (it also covers
// Shipped) and only controls the icon/label's appearance — hasActiveOrder
// alone still decides the "Add to cart" vs "Add to order" button text, since
// that's tied to actual cart-blocking behavior (Processing/Pending only).
function applyActiveOrderUI() {
    const hasActiveOrder = getHasActiveOrder();
    const orderStatus = window.orderStatus || null;
    const isDisplayActive = DISPLAY_ORDER_STATUSES.includes(orderStatus);

    const iconMarkup = isDisplayActive ? STATUS_ICON_SVG[orderStatus] : CART_ICON_SVG;
    const iconAriaLabel = isDisplayActive ? ("Order " + orderStatus.toLowerCase()) : "Shopping cart";
    const statusClass = isDisplayActive ? "Status_" + orderStatus : null;

    $("#nav-cart-icon, #nav-cart-label, #account-nav-cart-icon, #account-nav-cart-label, #bottomnav-cart-icon, #bottomnav-cart-label")
        .removeClass(ORDER_STATUS_CLASSES)
        .addClass(statusClass);

    $("#nav-cart-icon").html(iconMarkup).attr("aria-label", iconAriaLabel);
    $("#account-nav-cart-icon").html(iconMarkup);
    $("#bottomnav-cart-icon").html(iconMarkup);

    if (statusRotationTimer) {
        clearInterval(statusRotationTimer);
        statusRotationTimer = null;
    }

    const words = isDisplayActive ? STATUS_WORD_ROTATION[orderStatus] : null;
    if (words) {
        // .html(), not .text() — Pending's entry carries the animated-dots
        // markup (see PENDING_DOTS_HTML). Every entry here is a hardcoded
        // developer string, never user input, so this is safe.
        $("#nav-cart-label, #account-nav-cart-label, #bottomnav-cart-label").html(words[0]);

        if (words.length > 1) {
            let wordIndex = 1 % words.length;
            statusRotationTimer = setInterval(function () {
                $("#nav-cart-label, #account-nav-cart-label, #bottomnav-cart-label").html(words[wordIndex]);
                wordIndex = (wordIndex + 1) % words.length;
            }, STATUS_ROTATION_INTERVAL_MS);
        }
    } else {
        $("#nav-cart-label, #account-nav-cart-label, #bottomnav-cart-label").text("Cart");
    }

    $(".Add_To_Cart_Btn").text(hasActiveOrder ? "Add to order" : "Add to cart");
}

function requireLogin() {
    if (!getIsLoggedIn()) {
        window.location.href = "/HeyDaniel/Interface/Sheets/Credential";
        return false;
    }
    return true;
}

function DeviceCheck() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "device_check",
            device_type: "Web",
            screen_resolution: screen.width + "x" + screen.height
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message)
            }
            else {
                window.hasActiveOrder = !!data.has_active_order;
                window.orderStatus = capitalizeOrderStatus(data.order_status) || null;
                applyActiveOrderUI();

                if (data.is_device_known === false) {
                    $("#app-skeleton").fadeOut(150, function () {
                        $(".header-summary").css("opacity", 1);
                    });

                    $.getJSON("https://ipapi.co/json/", function (response) {
                        DeviceLog(response.postal, false);
                    });
                }
                else {
                    function updateUI(data) {

                        const city = data.city + ", ";
                        const state = data.state + ", ";
                        const zip = data.zipcode;

                        // Header_Join_Membership_Btn — pulled off the header, kept here for reuse elsewhere.
                        // (getIsLoggedIn() && !getIsMember())
                        //     ? `<button type="button" class="Header_Join_Membership_Btn" data-open-membership-modal><i class="fas fa-crown" aria-hidden="true"></i> Join HeyDaniel+ &mdash; $9.99/mo</button>`
                        //     : ``;
                        const joinBtnHTML = ``;

                        const bannerHTML = data.same_day_eligible
                            ? `<h1>Premium same-day delivery on everything</h1><p>The best way to order. The fastest way to deliver.</p>${joinBtnHTML}`
                            : `<h1>Everything you need, all in one place</h1><p>The simplest way to order. Built for speed.</p>${joinBtnHTML}`;

                        $(".location-city").text(city);
                        $(".location-state").text(state);
                        $(".location-zip").text(zip);
                        $(".summary-advertisement").html(bannerHTML);

                        //  REMOVE skeleton ONLY when everything is painted
                        $("#app-skeleton").fadeOut(150, function () {
                            $(".header-summary").css("opacity", 1);
                        });
                    }

                    updateUI(data);
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("DeviceCheck failed:", res.error);
        }
    });
}

let pendingZipChange = null;

function showZipConflictModal(zipcode, clicked, perishableItems) {
    pendingZipChange = { zipcode: zipcode, clicked: clicked };

    // The confirmation modal needs to sit above the side-bar's native
    // <dialog>, which always renders in the browser's top layer regardless
    // of z-index — so the side-bar has to close first.
    $("#side-bar").removeClass("Sidebar_Open");
    $("html, body").removeClass("No_Scroll");

    const $items = $("#zip-conflict-modal .zip-conflict-items");
    $items.empty();
    perishableItems.forEach(function (item) {
        $items.append($("<li></li>").text(item.name));
    });
    $("#zip-conflict-modal .zip-conflict-count").text(perishableItems.length);

    $("#zip-conflict-modal").addClass("active");
}

function hideZipConflictModal() {
    pendingZipChange = null;
    $("#zip-conflict-modal").removeClass("active");
}

function confirmZipChange() {
    if (!pendingZipChange) {
        return;
    }
    DeviceLog(pendingZipChange.zipcode, pendingZipChange.clicked, true);
    hideZipConflictModal();
}

function DeviceLog(zipcode, clicked, confirmRemovePerishables, confirmCancelOrder) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "device_log",
            device_type: "Web",
            zipcode: zipcode,
            confirm_remove_perishables: !!confirmRemovePerishables,
            confirm_cancel_order: !!confirmCancelOrder
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message)
            } else if (data.requires_order_cancel_confirmation) {
                // Same reason as showZipConflictModal(): the confirm modal
                // needs to sit above the side-bar's native <dialog>, which
                // always renders in the browser's top layer regardless of
                // z-index — so the side-bar has to close first.
                $("#side-bar").removeClass("Sidebar_Open");
                $("html, body").removeClass("No_Scroll");

                showAppConfirm(
                    "This zip code can't support your active order, and it will be cancelled if you continue.",
                    function () {
                        DeviceLog(zipcode, clicked, false, true);
                    },
                    {
                        title: "Cancel your order?",
                        confirmLabel: "Yes, cancel order",
                        cancelLabel: "No, go back"
                    }
                );
            } else if (data.requires_confirmation) {
                showZipConflictModal(zipcode, clicked, data.perishable_items);
                if (data.order_in_transit) {
                    showAppAlert("You have an order on the way — it'll still be delivered to the address on file.", { title: "Order in transit" });
                }
            } else {
                function updateUI(data) {

                    const city = data.city + ", ";
                    const state = data.state + ", ";
                    const zip = data.zipcode;

                    const bannerHTML = data.same_day_eligible
                        ? `<h1>Premium same-day delivery on everything</h1><p>The best way to order. The fastest way to deliver.</p>`
                        : `<h1>Everything you need, all in one place</h1><p>The simplest way to order. Built for speed.</p>`;

                    // 🔒 stop visual repaint during update
                    const $ui = $(".location-city").closest("header");

                    $ui.css("visibility", "hidden");

                    $(".location-city").text(city);
                    $(".location-state").text(state);
                    $(".location-zip").text(zip);
                    // Sliding the drawer shut and fully closing the native
                    // <dialog> (backdrop, scroll-lock) is handled centrally
                    // in Interaction.js via this same class removal, so the
                    // close behavior stays identical regardless of what
                    // triggered it.
                    $("#side-bar").removeClass("Sidebar_Open")
                    $(".summary-advertisement").html(bannerHTML);

                    $ui.css("visibility", "visible");

                    // 🔥 REMOVE skeleton ONLY when everything is painted
                    $("#app-skeleton").fadeOut(150, function () {
                        $(".header-summary").css("opacity", 1);
                        if (clicked) {
                            $(".prev-zip").attr("class", "history");
                            $(".history").html(`<img src="/HeyDaniel/Assets/Icons/history.svg" alt="heyDaniel history" class="history-icon" />
                <p>${zipcode}</p>`)
                        }
                    });
                }

                updateUI(data);
                refreshProductSliders();
                refreshHomepageCollections();
                summary();
                cartIcon();

                if (data.order_in_transit) {
                    showAppAlert("You have an order on the way — it'll still be delivered to the address on file.", { title: "Order in transit" });
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("DeviceLog failed:", res.error);
        }
    });
}

// cart

function addProduct(product_id, onSuccess) {
    if (!requireLogin()) {
        return;
    }

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "cart_add",
            device_type: "Web",
            product_id: product_id
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                summary();
                cartIcon();

                if (typeof onSuccess === "function") {
                    onSuccess(data);
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("addProduct failed:", res.error);
        }
    });
}

function cartIcon() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "cart_icon",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                const totalCount = data.total_count;
                $(".nav-cart .Nav_Icon_Badge").text(totalCount).toggle(totalCount > 0);
                $("#account-nav-cart-badge").text(totalCount).toggle(totalCount > 0);
                $("#bottomnav-cart-badge").text(totalCount).toggle(totalCount > 0);

                // Persistent mini cart bar (Home/Store only — see Footer.php)
                $("#mini-cart-bar-count").text(totalCount + (totalCount === 1 ? " item" : " items"));
                $("#mini-cart-bar").toggleClass("Visible", totalCount > 0);

                window.hasActiveOrder = !!data.has_active_order;
                window.orderStatus = capitalizeOrderStatus(data.order_status) || null;
                applyActiveOrderUI();
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("cartIcon failed:", res.error);
        }
    });
}

function notificationsBadge() {
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
            const unreadCount = notifications.filter(function (n) { return !n.is_read; }).length;
            $("#account-nav-notifications-badge").text(unreadCount).toggle(unreadCount > 0);
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("notificationsBadge failed:", res.error);
        }
    });
}

const CART_PAGE_SIZE = 5;
let cartPageAllItems = [];
let cartPageCurrentPage = 1;
let cartIsProcessing = false;
let cartPageSortBy = 'status';
const PICK_STATUS_SORT_RANK = { in_stock: 0, processing: 1, pending: 1, partial: 2, out_of_stock: 3 };

// Cart.php and Process.php are now two separate pages/templates (routed
// server-side — Cart.php requires Process.php when an order is actively
// being picked, otherwise renders the normal cart itself) rather than one
// page that toggled between states client-side. Each page sets
// cartIsProcessing once, up front, before calling cartItem(); this just
// fetches/renders the right item list for whichever page is loaded.
// Opens a connection to the order-status WebSocket daemon (Server/WebSocket/
// run.php - a separate long-running process, not served through Apache) and
// subscribes to push updates for one order. The server only ever sends a
// bare {"type":"order_update"} signal, never order data itself - onUpdate is
// expected to react by re-fetching through the normal REST calls (cartItem/
// summary), so there's one source of truth for how order data is shaped.
// Returns null immediately (caller should fall back to polling) if this
// browser has no WebSocket support or the connection can't even be
// attempted; onerror/onclose still need to be wired by the caller to catch
// a connection that fails after the fact (e.g. the daemon isn't running).
function connectOrderStatusSocket(orderId, onUpdate) {
    if (!orderId || typeof WebSocket === "undefined") {
        return null;
    }

    let socket;
    try {
        socket = new WebSocket("ws://" + window.location.hostname + ":8080");
    } catch (e) {
        return null;
    }

    socket.onopen = function () {
        socket.send(JSON.stringify({ action: "subscribe", order_id: orderId }));
    };

    socket.onmessage = function (event) {
        let data;
        try {
            data = JSON.parse(event.data);
        } catch (e) {
            return;
        }
        if (data.type === "order_update" && typeof onUpdate === "function") {
            onUpdate();
        }
    };

    return socket;
}

function fetchOrderMessages(orderId, onSuccess, onError) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "get_messages",
            device_type: "Web",
            order_id: orderId
        }),
        success: function (data) {
            if (typeof onSuccess === "function") {
                onSuccess(data.messages || []);
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            if (typeof onError === "function") {
                onError(res.error || "Couldn't load messages.");
            }
        }
    });
}

function sendOrderMessage(orderId, message, onSuccess, onError) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "send_message",
            device_type: "Web",
            order_id: orderId,
            message: message
        }),
        success: function (data) {
            if (data.success) {
                if (typeof onSuccess === "function") {
                    onSuccess();
                }
            } else if (typeof onError === "function") {
                onError(data.error || "Couldn't send message.");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            if (typeof onError === "function") {
                onError(res.error || "Couldn't send message.");
            }
        }
    });
}

function cartItem(resetPage, onLoaded) {
    if (resetPage === undefined) {
        resetPage = true;
    }

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "cart_items",
            device_type: "Web"
        }),
        success: function (data) {
            cartPageAllItems = data.cart_items || [];
            window.currentOrderId = data.order_id || null;
            if (resetPage) {
                cartPageCurrentPage = 1;
            }
            if (typeof onLoaded === "function") {
                onLoaded();
            }

            if (cartIsProcessing && $("#cart-section-title").length) {
                const orderStatus = capitalizeOrderStatus(data.order_status);
                $("#cart-section-title").text("Order " + (orderStatus || "Processing"));
                updateOrderProgressTracker(orderStatus, cartPageAllItems, data.order_placed_at, data.scheduled_delivery_window, data.was_rescheduled);
                updateOrderStatusCards(orderStatus, cartPageAllItems, data.scheduled_delivery_window, data.was_rescheduled);
                updateOrderActionControl(orderStatus);
            }

            if (data.message || !cartPageAllItems.length) {
                $("#cart-items-container").empty();
                $("#cart-empty-message").text(cartIsProcessing ? "No items are currently being processed." : "Your cart is empty.").show();
                $("#cart-summary").hide();
                $("#cart-pagination").hide();
                $("#cart-item-count").text("0 items");
                return;
            }

            $("#cart-empty-message").hide();
            $("#cart-summary").show();
            $("#cart-item-count").text(cartPageAllItems.length + (cartPageAllItems.length === 1 ? " item" : " items"));

            renderCartPage();
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("cartItem failed:", res.error);
        }
    });
}

const ORDER_PROGRESS_STAGE = { Pending: 1, Processing: 1, Shipped: 2, Delivered: 3 };

// How many of the order's items have actually been resolved (found,
// partial, or out of stock) vs still "processing"/"pending" — the real
// signal behind "Picking Items" progress, since there's no backend field
// for it directly.
function pickingProgressFraction(items) {
    if (!items || !items.length) {
        return 0;
    }
    const resolvedCount = items.filter(function (item) {
        return item.pick_status !== "processing" && item.pick_status !== "pending";
    }).length;
    return resolvedCount / items.length;
}

// 4-step model: 1 Order Received (Pending — nobody's picking yet, so this
// is where a Pending order actually sits, not step 2), 2 Picking Items
// (Processing, driven by pickingProgressFraction), 3 On the Way (fires once
// order_status hits Shipped — Cart.php now routes here for Pending/
// Processing/Shipped orders), 4 Delivered (order_status has left this page
// by then, since Delivered/Cancelled drop out of the active-order set).
function computeOrderProgressStep(orderStatus, items) {
    const orderStage = ORDER_PROGRESS_STAGE[orderStatus] || 1;
    const fraction = pickingProgressFraction(items);
    let step;
    if (orderStage === 3) {
        step = 4;
    } else if (orderStage === 2) {
        step = 3;
    } else if (orderStatus === "Pending") {
        step = 1;
    } else {
        step = 2;
    }
    return { step: step, fraction: fraction };
}

const ORDER_PROGRESS_TOTAL_STEPS = 4;

// Same icon paths used in the 4-step row (Process.php), reused here for the
// mini status card so the icon swaps to match whichever step is active.
// Step 2's "Getting your order ready" card gets its own clipboard-check
// icon rather than reusing the tracker row's Picking Items icon.
const ORDER_PROGRESS_RECEIVED_ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';
const ORDER_PROGRESS_READY_ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v9.375m-8.25-3 1.5 1.5 3-3.75" /></svg>';
const ORDER_PROGRESS_TRUCK_ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>';
const ORDER_PROGRESS_HOME_ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>';

const ORDER_PROGRESS_STEP_ICON = {
    1: ORDER_PROGRESS_RECEIVED_ICON,
    2: ORDER_PROGRESS_READY_ICON,
    3: ORDER_PROGRESS_TRUCK_ICON,
    4: ORDER_PROGRESS_HOME_ICON
};

const ORDER_PROGRESS_STEP_COPY = {
    1: { title: "Order received", sub: "Waiting for a shopper to start your order." },
    2: { title: "Getting your order ready", sub: "Your shopper is picking your items." },
    3: { title: "On the way to you", sub: "We're almost there!" },
    4: { title: "Delivered", sub: "Enjoy your order!" }
};

// A stable per-page-load ETA window (now + 45 to +75 minutes) — there's no
// real delivery-time estimate from the backend, so this is a placeholder,
// same spirit as the hardcoded Personal Shopper stats. Computed once and
// cached so it doesn't jump around on every poll.
let cachedEtaWindow = null;
// A rescheduled order has a real (customer-picked) window to show instead
// of the fake computed one — skips the cache entirely since it comes fresh
// off the server each time, not a client-side placeholder.
function getEtaWindow(scheduledWindow) {
    if (scheduledWindow) {
        return { window: scheduledWindow, minutesAway: null, dateLabel: scheduledWindow };
    }
    if (cachedEtaWindow) {
        return cachedEtaWindow;
    }
    const now = new Date();
    const start = new Date(now.getTime() + 45 * 60000);
    const end = new Date(now.getTime() + 75 * 60000);
    const fmt = function (d) {
        return d.toLocaleTimeString(undefined, { hour: "numeric", minute: "2-digit" });
    };
    const dateFmt = now.toLocaleDateString(undefined, { weekday: undefined, month: "long", day: "numeric" });
    cachedEtaWindow = {
        window: fmt(start) + " – " + fmt(end),
        minutesAway: 45,
        dateLabel: "Today, " + dateFmt
    };
    return cachedEtaWindow;
}

// Top-of-page tracker on Process.php: ETA header, mini status card, and the
// 5-step row. No-op if it isn't on the page (Cart.php doesn't have it).
function updateOrderProgressTracker(orderStatus, items, orderPlacedAt, scheduledWindow, wasRescheduled) {
    const $tracker = $("#order-progress-tracker");
    if (!$tracker.length) {
        return;
    }

    const progress = computeOrderProgressStep(orderStatus, items);
    const step = progress.step;
    const eta = getEtaWindow(scheduledWindow);
    // A same-day order placed too late to make same-day delivery gets a
    // window auto-assigned at checkout (see submitCheckout()) — that's not
    // a customer-initiated reschedule, so it must not read "Rescheduled."
    // Only wasRescheduled (set by rescheduleDelivery()) means the customer
    // actually changed it.
    const isPending = !wasRescheduled && orderStatus === "Pending";

    let etaMinutesText;
    let etaNoteText;
    if (wasRescheduled) {
        etaMinutesText = "Rescheduled";
        etaNoteText = "Updated";
    } else if (isPending) {
        // No shopper has started yet, so a "X min away" countdown would be
        // misleading — nothing is actually en route. The delivery window
        // itself is swapped for a plain "Pending" badge too, since there's
        // no real window to show until the order moves off Pending.
        etaMinutesText = "Order received";
        etaNoteText = "";
    } else if (eta.minutesAway === null) {
        // A real, specific delivery window was already assigned (e.g. a
        // same-day order placed too late for same-day gets pinned to
        // tomorrow's opening window at checkout — see submitCheckout()) -
        // the window itself is the ETA, so there's no "X min away" countdown
        // to show. getEtaWindow() returns minutesAway: null for exactly this
        // case; previously nothing checked for it and this rendered the
        // literal string "null min away".
        etaMinutesText = "Scheduled";
        etaNoteText = "";
    } else {
        etaMinutesText = eta.minutesAway + " min away";
        etaNoteText = "Arriving soon";
    }

    $("#order-progress-eta-window").toggle(!isPending).text(eta.window);
    $("#order-progress-eta-pending-badge").toggle(isPending);
    $("#order-progress-eta-minutes").text(etaMinutesText);
    $("#order-progress-eta-note")
        .text(etaNoteText)
        .toggleClass("Order_Progress_Eta_Note_Badge", !!wasRescheduled);
    $("#order-progress-eta-away-dot").toggle(!wasRescheduled && !isPending && eta.minutesAway !== null);

    const copy = ORDER_PROGRESS_STEP_COPY[step] || ORDER_PROGRESS_STEP_COPY[2];
    $("#order-progress-mini-status-title").text(copy.title);
    $("#order-progress-mini-status-sub").text(copy.sub);
    $("#order-progress-mini-status-icon").html(ORDER_PROGRESS_STEP_ICON[step] || ORDER_PROGRESS_STEP_ICON[2]);

    const totalDots = 6;
    const filledDots = Math.min(totalDots, Math.round(((step - 1) / (ORDER_PROGRESS_TOTAL_STEPS - 1)) * totalDots));
    const $dots = $("#order-progress-mini-dots").empty();
    for (let i = 0; i < totalDots; i++) {
        $('<span class="Order_Progress_Mini_Dot"></span>').toggleClass("Filled", i < filledDots).appendTo($dots);
    }

    $tracker.find(".Order_Progress_Step").each(function () {
        const s = parseInt($(this).data("step"), 10);
        $(this).removeClass("Active Completed");
        let timeText = "Upcoming";
        if (s < step) {
            $(this).addClass("Completed");
            timeText = s === 1 && orderPlacedAt ? formatTimeOfDay(orderPlacedAt) : "Completed";
        } else if (s === step) {
            $(this).addClass("Active");
            timeText = "In progress";
        }
        $("#order-progress-step-" + s + "-time").text(timeText);
    });

    for (let i = 1; i <= ORDER_PROGRESS_TOTAL_STEPS - 1; i++) {
        let width = i < step ? 100 : 0;
        if (step === 2 && i === 2) {
            width = Math.round(progress.fraction * 100);
        }
        $("#order-progress-bar-" + i + " .Order_Progress_Bar_Fill").css("width", width + "%");
    }
}

function formatTimeOfDay(mysqlDateTime) {
    const parsed = new Date(mysqlDateTime.replace(" ", "T"));
    if (isNaN(parsed)) {
        return "Completed";
    }
    return parsed.toLocaleTimeString(undefined, { hour: "numeric", minute: "2-digit" });
}

let lastOrderStatusFetch = null;

// Right-column "Current Status" + "Estimated Delivery" cards on Process.php.
function updateOrderStatusCards(orderStatus, items, scheduledWindow, wasRescheduled) {
    if (!$("#order-status-headline").length) {
        return;
    }

    const progress = computeOrderProgressStep(orderStatus, items);
    const copy = ORDER_PROGRESS_STEP_COPY[progress.step] || ORDER_PROGRESS_STEP_COPY[2];
    $("#order-status-headline").text(copy.title);

    if (progress.step === 2 && items && items.length) {
        const resolvedCount = items.filter(function (item) {
            return item.pick_status !== "processing" && item.pick_status !== "pending";
        }).length;
        $("#order-status-sub").text("Your shopper has found " + resolvedCount + " of " + items.length + " items so far.");
    } else {
        $("#order-status-sub").text(copy.sub);
    }

    const eta = getEtaWindow(scheduledWindow);
    $("#order-status-eta-window").text(eta.window);
    $("#order-status-eta-date").text(eta.dateLabel);
    $("#order-status-eta-pill").html(
        wasRescheduled
            ? '<i class="fas fa-calendar-alt" aria-hidden="true"></i> Rescheduled'
            : orderStatus === "Pending"
                ? '<i class="fas fa-hourglass-half" aria-hidden="true"></i> Pending'
                : eta.minutesAway === null
                    ? '<i class="fas fa-calendar-alt" aria-hidden="true"></i> Scheduled'
                    : '<i class="fas fa-clock" aria-hidden="true"></i> ' + eta.minutesAway + " min away"
    );

    lastOrderStatusFetch = new Date();
    $("#order-status-updated").text("Last updated: just now");
}

// Header action next to the "Order Processing" title — exactly one of the
// three is ever shown, keyed off order_status: Pending can still be
// cancelled outright, Processing switches to Reschedule Delivery (a shopper
// is already picking, so cancelling out isn't offered), and Shipped swaps
// in the item-list Sort-by control since there's nothing left to cancel or
// reschedule once the driver's already en route.
function updateOrderActionControl(orderStatus) {
    const $cancel = $("#cancel-order-btn");
    const $reschedule = $("#reschedule-delivery-btn");
    const $sort = $("#cart-sort-row");

    $cancel.hide();
    $reschedule.hide();
    $sort.hide();

    if (orderStatus === "Processing") {
        $reschedule.show();
    } else if (orderStatus === "Shipped") {
        $sort.show();
    } else {
        $cancel.show();
    }
}

// "Last updated" ticks forward client-side between polls so it doesn't
// freeze at "just now" — cheap, avoids a fake-precision timestamp.
setInterval(function () {
    if (!lastOrderStatusFetch || !$("#order-status-updated").length) {
        return;
    }
    const minutesAgo = Math.floor((Date.now() - lastOrderStatusFetch.getTime()) / 60000);
    $("#order-status-updated").text(minutesAgo <= 0 ? "Last updated: just now" : "Last updated: " + minutesAgo + " min ago");
}, 30000);

// Visual-only preference toggle (no backend for delivery notifications yet)
// — persisted in localStorage so it survives a refresh, same non-destructive
// spirit as any other client-side display preference.
function initOrderStatusToggle() {
    const $toggle = $("#order-status-notify-toggle");
    if (!$toggle.length) {
        return;
    }
    const stored = localStorage.getItem("hd_realtime_updates");
    const isOn = stored === null ? true : stored === "true";
    $toggle.toggleClass("Toggle_On", isOn).attr("aria-pressed", isOn ? "true" : "false");

    $toggle.on("click", function () {
        const nowOn = !$toggle.hasClass("Toggle_On");
        $toggle.toggleClass("Toggle_On", nowOn).attr("aria-pressed", nowOn ? "true" : "false");
        localStorage.setItem("hd_realtime_updates", nowOn ? "true" : "false");
    });
}

// Icons render white on their own colored pill background now (badge sits
// inline in the row's status pill, not overlaid on a white circle on the
// image), so every icon is stroke="#fff" regardless of status. "in_stock"
// and "partial" reuse the exact paths from Assets/Icons/check.svg and
// alert.svg (just recolored), rather than a hand-drawn approximation.
// "processing" spins via its own .Pick_Status_Badge_Spin animation (Card.css).
const PICK_STATUS_META = {
    in_stock: {
        rowClass: "Pick_Status_In_Stock",
        badgeLabel: "Found",
        icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12" height="12" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>'
    },
    out_of_stock: {
        rowClass: "Pick_Status_Out_Of_Stock",
        badgeLabel: "Out of Stock",
        icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12" height="12" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18 18 6M6 6l12 12" /></svg>'
    },
    partial: {
        rowClass: "Pick_Status_Partial",
        badgeLabel: "Partial",
        icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12" height="12" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>'
    },
    processing: {
        rowClass: "Pick_Status_Processing",
        badgeLabel: "Processing",
        icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12" height="12" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="Pick_Status_Badge_Spin"><path d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" /></svg>'
    },
    // Order is Pending — nobody's picking yet (distinct from "processing",
    // which is an unresolved item *within* an order a shopper is actively
    // working). Static clock, no spin, since nothing's in motion yet.
    pending: {
        rowClass: "Pick_Status_Pending",
        badgeLabel: "Pending",
        icon: '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" width="12" height="12" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>'
    }
};

// Read-only picking-status row for Cart.php's "order in progress" view —
// no price, no quantity controls (can't edit/buy an order that's already
// being picked), just what's been found so far. Reuses the same Row_Card
// image/info markup as the editable cart row for visual consistency. The
// status pill overlays the image's top-left corner; the right side is just
// plain "X out of Y found" text (or "Out of stock"), except "processing"
// which keeps the 3-dot loading indicator since there's no count yet.
function buildProcessingItemCard(item) {
    const productId = item.product_id;
    const itemUrl = "/HeyDaniel/Interface/Sheets/Item?id=" + productId;
    const meta = PICK_STATUS_META[item.pick_status] || PICK_STATUS_META.processing;
    const isProcessingStatus = item.pick_status === "processing";
    const isPendingStatus = item.pick_status === "pending";
    const isUnresolvedStatus = isProcessingStatus || isPendingStatus;
    const foundQty = item.pick_status === "in_stock" ? item.quantity
        : item.pick_status === "out_of_stock" ? 0
        : item.pick_status === "partial" ? item.quantity_found
        : null;

    // Stars show found-ratio, not the product's real review rating — more
    // useful here than an unrelated review score while checking an order.
    // Processing/Pending have no found-count yet, so it's 0 stars, with the
    // status line on its own line underneath instead of inline next to the count.
    const $ratingRow = $('<p class="Product_Card_Rating"></p>').html(
        renderProductStars(isUnresolvedStatus ? 0 : (foundQty / item.quantity) * 5) +
        '<span class="Product_Card_Rating_Count">' + meta.badgeLabel + '</span>'
    );
    const $searchingText = isUnresolvedStatus
        ? $('<p class="Pick_Status_Searching"></p>').text(
            isPendingStatus ? "Waiting to be picked up by a shopper..." : "Looking for product availability..."
        )
        : null;

    const $foundIndicator = isUnresolvedStatus
        ? $('<div class="Pick_Status_Qty_Dots"></div>').append(
            $('<span class="Pick_Status_Qty_Dot"></span>'),
            $('<span class="Pick_Status_Qty_Dot"></span>'),
            $('<span class="Pick_Status_Qty_Dot"></span>')
        )
        : $('<span class="Pick_Status_Found_Text"></span>').text(
            item.pick_status === "out_of_stock" ? "Out of stock" : foundQty + " out of " + item.quantity + " found"
        );

    return $('<div class="Row_Card Pick_Status_Row"></div>')
        .addClass(meta.rowClass)
        .attr("data-product-id", productId)
        .append(
            $('<a class="Row_Card_Image_Wrap Pick_Status_Image_Wrap"></a>')
                .attr("href", itemUrl)
                .css("background-image", "url(" + item.picture + ")")
                .append(
                    $('<span class="Pick_Status_Badge"></span>').html(meta.icon + '<span>' + meta.badgeLabel + '</span>')
                ),
            $('<div class="Row_Card_Info"></div>').append(
                $('<p class="Product_Card_Brand"></p>').text(item.brand),
                $('<h3 class="Row_Card_Title"></h3>').append($('<a></a>').attr("href", itemUrl).text(item.name)),
                $ratingRow,
                $searchingText,
                $('<div class="Row_Card_Meta"></div>').append(
                    item.oz ? $('<span class="Product_Card_Size"></span>').text(item.oz) : null,
                    item.same_day_eligible
                        ? $('<span class="Product_Card_Delivery"></span>').text("Same-day eligible")
                        : $('<span class="Product_Card_Delivery Standard"></span>').text("Fast delivery")
                )
            ),
            $foundIndicator
        );
}

function setCartPage(page) {
    cartPageCurrentPage = page;
    renderCartPage();
}

function setCartSort(sortBy) {
    cartPageSortBy = sortBy;
    cartPageCurrentPage = 1;
    renderCartPage();
}

function sortProcessingItems(items, sortBy) {
    const sorted = items.slice();
    if (sortBy === 'name') {
        sorted.sort(function (a, b) { return a.name.localeCompare(b.name); });
    } else if (sortBy === 'availability') {
        sorted.sort(function (a, b) {
            return (a.quantity_found / a.quantity) - (b.quantity_found / b.quantity);
        });
    } else {
        sorted.sort(function (a, b) {
            return PICK_STATUS_SORT_RANK[a.pick_status] - PICK_STATUS_SORT_RANK[b.pick_status];
        });
    }
    return sorted;
}

function renderCartPage() {
    const $container = $("#cart-items-container").empty();
    const items = cartIsProcessing ? sortProcessingItems(cartPageAllItems, cartPageSortBy) : cartPageAllItems;

    const totalPages = Math.max(1, Math.ceil(items.length / CART_PAGE_SIZE));
    if (cartPageCurrentPage > totalPages) {
        cartPageCurrentPage = totalPages;
    }

    const startIndex = (cartPageCurrentPage - 1) * CART_PAGE_SIZE;
    const pageItems = items.slice(startIndex, startIndex + CART_PAGE_SIZE);

    pageItems.forEach(function (item) {
        if (cartIsProcessing) {
            $container.append(buildProcessingItemCard(item));
            return;
        }

        const product = {
            product_id: item.product_id,
            brand: item.brand,
            name: item.name,
            oz: item.oz,
            price: item.price,
            picture: item.picture,
            is_on_sale: item.is_on_sale,
            sale_price: item.sale_price,
            is_bogo: item.is_bogo,
            is_saved: item.is_saved,
            in_cart: true,
            quantity: item.quantity,
            rating: item.ratings,
            review_count: item.review_count,
            total_price: item.total_price,
            same_day_eligible: item.same_day_eligible
        };

        const $card = buildProductCard(product, false, {
            layout: "row",
            showLineTotal: true,
            showWishlistBtn: true,
            onQuantityEmpty: function () {
                cartItem(false);
            }
        });

        $container.append($card);
    });

    $("#cart-pagination-label").text(
        "Showing " + (startIndex + 1) + " to " + Math.min(startIndex + CART_PAGE_SIZE, items.length) + " of " + items.length + " items"
    );

    const $pages = $("#cart-pagination-numbers").empty();
    for (let i = 1; i <= totalPages; i++) {
        $('<button type="button" class="List_Page_Btn"></button>')
            .toggleClass("active", i === cartPageCurrentPage)
            .attr("data-page", i)
            .text(i)
            .appendTo($pages);
    }

    $("#cart-page-prev").prop("disabled", cartPageCurrentPage <= 1);
    $("#cart-page-next").prop("disabled", cartPageCurrentPage >= totalPages);

    $("#cart-pagination").show();
}

// users
function register(userName, userEmail, userPassword) {
    setButtonLoading(function () {
        $.ajax({
            method: "POST",
            url: "/HeyDaniel/Server/index.php",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify({
                action: "register",
                user_name: userName,
                user_email: userEmail,
                user_pass: userPassword
            }),
            success: function (data) {
                if (data.message) {
                    stopButtonLoading(".register-bnt", "Register");
                    $(".credential-error").fadeIn(0, function(){
                        $(this).html(`<img src="../../Assets/Icons/alert.svg" alt="heyDaniel error"><p>`+ data.message +`</p>`)
                    })
                } else if(data.success) {
                    login(userEmail, userPassword, ".register-bnt", "Register")
                }
            },
            error: function (xhr) {
                stopButtonLoading(".register-bnt", "Register");
                const res = JSON.parse(xhr.responseText);
                console.log("register failed:", res.error);
            }
        });
    }, ".register-bnt");
}

function login(userEmail, userPass, triggerSelector = ".login-bnt", triggerText = "Login") {
    setButtonLoading(function () {
        $.ajax({
            method: "POST",
            url: "/HeyDaniel/Server/index.php",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify({
                action: "login",
                device_type: "Web",
                user_email: userEmail,
                user_pass: userPass
            }),
            success: function (data) {
                if (data.message) {
                    stopButtonLoading(triggerSelector, triggerText);
                    $(".credential-error").fadeIn(0, function(){
                        $(this).html(`<img src="../../Assets/Icons/alert.svg" alt="heyDaniel error"><p>`+ data.message +`</p>`)
                    })
                } else {
                    location.reload();
                }
            },
            error: function (xhr) {
                stopButtonLoading(triggerSelector, triggerText);
                const res = JSON.parse(xhr.responseText);
                console.log("login failed:", res.error);
            }
        });
    }, triggerSelector);
}

function sendResetCode(email) {
    setButtonLoading(function () {
        $.ajax({
            method: "POST",
            url: "/HeyDaniel/Server/index.php",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify({
                action: "collect_email",
                email: email,
                is_updating_password: true
            }),
            success: function (data) {
                stopButtonLoading(".forgot-send-code-bnt", "Send reset code");
                if (data.error || !data.is_registered) {
                    $(".credential-error").fadeIn(0, function () {
                        $(this).html(`<img src="../../Assets/Icons/alert.svg" alt="heyDaniel error"><p>` + (data.error || data.message) + `</p>`)
                    })
                } else {
                    $(".forgot-step-request").addClass("hidden").hide();
                    $(".forgot-step-reset").removeClass("hidden").show();
                }
            },
            error: function (xhr) {
                stopButtonLoading(".forgot-send-code-bnt", "Send reset code");
                let message = "Something went wrong. Please try again.";
                try {
                    const res = JSON.parse(xhr.responseText);
                    message = res.error || res.message || message;
                } catch (e) {
                    // response wasn't JSON — fall back to the default message above
                }
                console.log("sendResetCode failed:", message);
                $(".credential-error").fadeIn(0, function () {
                    $(this).html(`<img src="../../Assets/Icons/alert.svg" alt="heyDaniel error"><p>` + message + `</p>`)
                })
            }
        });
    }, ".forgot-send-code-bnt");
}

function resetPassword(email, code, newPassword, confirmPassword) {
    setButtonLoading(function () {
        $.ajax({
            method: "POST",
            url: "/HeyDaniel/Server/index.php",
            contentType: "application/json",
            dataType: "json",
            data: JSON.stringify({
                action: "change_password",
                user_email: email,
                unique_code: code,
                new_password: newPassword,
                confirm_password: confirmPassword
            }),
            success: function (data) {
                if (data.error || !data.success) {
                    stopButtonLoading(".forgot-reset-password-bnt", "Reset password");
                    $(".credential-error").fadeIn(0, function () {
                        $(this).html(`<img src="../../Assets/Icons/alert.svg" alt="heyDaniel error"><p>` + (data.error || data.message) + `</p>`)
                    })
                } else {
                    $(".credential-forgot").fadeOut(200, function () {
                        $(".forgot-step-reset").addClass("hidden").hide();
                        $(".forgot-step-request").removeClass("hidden").show();
                        $(".forgot-email, .forgot-code, .forgot-new-password, .forgot-confirm-password").val("");
                        stopButtonLoading(".forgot-reset-password-bnt", "Reset password");
                        $(".credential-login").fadeIn(200);
                    });
                }
            },
            error: function (xhr) {
                stopButtonLoading(".forgot-reset-password-bnt", "Reset password");
                let message = "Something went wrong. Please try again.";
                try {
                    const res = JSON.parse(xhr.responseText);
                    message = res.error || res.message || message;
                } catch (e) {
                    // response wasn't JSON — fall back to the default message above
                }
                console.log("resetPassword failed:", message);
                $(".credential-error").fadeIn(0, function () {
                    $(this).html(`<img src="../../Assets/Icons/alert.svg" alt="heyDaniel error"><p>` + message + `</p>`)
                })
            }
        });
    }, ".forgot-reset-password-bnt");
}

function googleLogin(idToken) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "google_login",
            device_type: "Web",
            id_token: idToken
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                location.reload();
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("googleLogin failed:", res.error);
        }
    });
}

function logout() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "logout",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                // These pages either require login to mean anything (account
                // settings/orders/addresses) or may be showing session-bound
                // content (Cart doubles as the active-order/Process view,
                // Checkout is mid-purchase) - reloading them logged-out would
                // either break or silently swap what's on screen out from
                // under the user. Anywhere else (Store, Item, HelpSupport,
                // etc.) already renders fine logged-out, so a reload is enough.
                const accountOnlyPaths = [
                    "/Interface/Sheets/Profile",
                    "/Interface/Sheets/Orders",
                    "/Interface/Sheets/Saved",
                    "/Interface/Sheets/Addresses",
                    "/Interface/Sheets/PaymentMethods",
                    "/Interface/Sheets/Notifications",
                    "/Interface/Sheets/Settings",
                    "/Interface/Sheets/Cart",
                    "/Interface/Sheets/Checkout"
                ];
                const onAccountOnlyPage = accountOnlyPaths.some(function (path) {
                    return window.location.pathname.indexOf(path) !== -1;
                });

                if (onAccountOnlyPage) {
                    window.location.href = "/HeyDaniel/";
                } else {
                    location.reload();
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("logout failed:", res.error);
        }
    });
}

// cart
function decrementProduct(product_id, onSuccess) {
    if (!requireLogin()) {
        return;
    }

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "cart_decrement",
            device_type: "Web",
            product_id: product_id
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                summary();
                cartIcon();

                if (typeof onSuccess === "function") {
                    onSuccess(data);
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("decrementProduct failed:", res.error);
        }
    });
}

function clearCart() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "cart_clear",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                summary();
                cartIcon();

                if ($("#cart-items-container").length) {
                    cartItem();
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("clearCart failed:", res.error);
        }
    });
}

// saved
function savedCount() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "saved_count",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                $(".nav-wishlist .Nav_Icon_Badge").text(data.saved_count).toggle(data.saved_count > 0);
                $("#stat-wishlist-count").text(data.saved_count || 0);
                $("#account-nav-wishlist-badge").text(data.saved_count).toggle(data.saved_count > 0);
                $("#bottomnav-wishlist-badge").text(data.saved_count).toggle(data.saved_count > 0);
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("savedCount failed:", res.error);
        }
    });
}

const SAVED_PAGE_SIZE = 6;
let savedPageAllItems = [];
let savedPageCurrentPage = 1;

function savedItems(resetPage) {
    if (resetPage === undefined) {
        resetPage = true;
    }

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "saved_items",
            device_type: "Web"
        }),
        success: function (data) {
            savedPageAllItems = data.saved_items || [];
            if (resetPage) {
                savedPageCurrentPage = 1;
            }

            if (data.message || !savedPageAllItems.length) {
                $('#saved-items-container').empty();
                $('#saved-message').text(data.message || "You haven't saved any items yet.").show();
                $('#saved-move-all-btn').hide();
                $('#saved-pagination').hide();
                $('#saved-item-count').text('');
                return;
            }

            $('#saved-message').hide();
            $('#saved-item-count').text(savedPageAllItems.length + (savedPageAllItems.length === 1 ? " item" : " items"));
            $('#saved-move-all-btn').show();

            renderSavedPage();
            savedCount();
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("savedItems failed:", res.error);
        }
    });
}

function setSavedPage(page) {
    savedPageCurrentPage = page;
    renderSavedPage();
}

function renderSavedPage() {
    const $container = $('#saved-items-container').empty();

    const totalPages = Math.max(1, Math.ceil(savedPageAllItems.length / SAVED_PAGE_SIZE));
    if (savedPageCurrentPage > totalPages) {
        savedPageCurrentPage = totalPages;
    }

    const startIndex = (savedPageCurrentPage - 1) * SAVED_PAGE_SIZE;
    const pageItems = savedPageAllItems.slice(startIndex, startIndex + SAVED_PAGE_SIZE);

    pageItems.forEach(function (item) {
        const product = {
            product_id: item.product_id,
            brand: item.brand,
            name: item.name,
            oz: item.oz,
            price: item.price,
            picture: item.picture,
            is_on_sale: item.is_on_sale,
            sale_price: item.sale_price,
            is_bogo: item.is_bogo,
            is_saved: true,
            in_cart: item.quantity > 0,
            quantity: item.quantity,
            rating: item.ratings,
            review_count: item.review_count
        };

        const $card = buildProductCard(product, false, {
            showWishlistBtn: true,
            onWishlistToggle: function (toggleData) {
                if (!toggleData.is_saved) {
                    savedItems(false);
                }
            }
        });

        $container.append($card);
    });

    $('#saved-pagination-label').text(
        "Showing " + (startIndex + 1) + " to " + Math.min(startIndex + SAVED_PAGE_SIZE, savedPageAllItems.length) + " of " + savedPageAllItems.length + " items"
    );

    const $pages = $('#saved-pagination-numbers').empty();
    for (let i = 1; i <= totalPages; i++) {
        $('<button type="button" class="List_Page_Btn"></button>')
            .toggleClass("active", i === savedPageCurrentPage)
            .attr("data-page", i)
            .text(i)
            .appendTo($pages);
    }

    $('#saved-page-prev').prop("disabled", savedPageCurrentPage <= 1);
    $('#saved-page-next').prop("disabled", savedPageCurrentPage >= totalPages);

    $('#saved-pagination').show();
}

// Loops the single-item add-to-cart endpoint since there is no bulk-add
// endpoint on the backend.
function moveAllSavedToCart() {
    if (!requireLogin()) {
        return;
    }

    const $btn = $('#saved-move-all-btn');
    const itemsToAdd = savedPageAllItems.filter(function (item) {
        return !(item.quantity > 0);
    });

    if (!itemsToAdd.length) {
        return;
    }

    $btn.prop('disabled', true).text('Adding...');

    let remaining = itemsToAdd.length;

    itemsToAdd.forEach(function (item) {
        addProduct(item.product_id, function () {
            remaining -= 1;
            if (remaining <= 0) {
                $btn.prop('disabled', false).text('Move all to cart');
                savedItems();
                cartIcon();
            }
        });
    });
}

function savedAdd(product_id, onSuccess) {
    if (!requireLogin()) {
        return;
    }

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "saved_add",
            device_type: "Web",
            product_id: product_id
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                savedCount();

                if (typeof onSuccess === "function") {
                    onSuccess(data);
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("savedAdd failed:", res.error);
        }
    });
}

// header
function summary() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "summary",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                const subtotal = Number(data.subtotal);
                const deliveryFee = 0;
                const total = subtotal + deliveryFee;

                $(".summary-order .order-info p").text('$' + subtotal.toFixed(2));
                $("#mini-cart-bar-price").text('$' + subtotal.toFixed(2));
                $(".cart-summary-subtotal").text('$' + subtotal.toFixed(2));
                $(".cart-summary-delivery").text('$' + deliveryFee.toFixed(2));
                $(".cart-summary-total").text('$' + total.toFixed(2));
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("summary failed:", res.error);
        }
    });
}

// store
function renderStoreResults(data) {
    const $grid = $("#store-grid");
    const $similarSection = $("#store-similar-section");
    const $similar = $("#store-similar-products");

    // Client-side sort only — the backend has no sort param, so this just
    // reorders whatever page of results already came back.
    let products = (data.products || []).slice();
    const sortValue = $("#store-sort").val();
    if (sortValue === "price_asc") {
        products.sort(function (a, b) { return a.price - b.price; });
    } else if (sortValue === "price_desc") {
        products.sort(function (a, b) { return b.price - a.price; });
    } else if (sortValue === "name_asc") {
        products.sort(function (a, b) { return a.name.localeCompare(b.name); });
    }

    $grid.empty();

    if (!products.length) {
        // Dedicated empty state (Store.php) handles messaging when present;
        // otherwise fall back to an inline message for other consumers.
        if (!$("#store-empty").length) {
            $grid.append('<p class="Store_Empty">No products found.</p>');
        }
    } else {
        products.forEach(function (product) {
            $grid.append(buildProductCard(product, false));
        });
    }

    if (typeof window.updateStoreResultsCount === "function") {
        window.updateStoreResultsCount(products.length);
    }

    // Brand options are seeded once from the first (unfiltered) load and
    // left alone after that — the backend only supports narrowing available
    // brands per result set, not recomputing them live as filters change.
    if (data.available_filters && data.available_filters.brands && $("#store-filter-brand option").length <= 1) {
        const $brandSelect = $("#store-filter-brand");
        data.available_filters.brands.forEach(function (brand) {
            $brandSelect.append($("<option></option>").val(brand).text(brand));
        });
    }

    if ($similar.length) {
        $similar.empty();

        if (data.similar_products && data.similar_products.length) {
            data.similar_products.forEach(function (product) {
                $similar.append(buildProductCard(product, false));
            });
            $similarSection.show();
        } else {
            $similarSection.hide();
        }
    }
}

function store(filter, limit) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "store",
            device_type: "Web",
            filter: filter || {},
            limit: limit || 16
        }),
        success: function (data) {
            renderStoreResults(data);
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);

            if (res.error === "No products found.") {
                renderStoreResults({ products: [], similar_products: [], available_filters: {} });
            } else {
                console.log("store failed:", res.error);
            }
        }
    });
}

// Note: the backend "filter" action has no pagination support (filterStore()
// is a straight alias of store()), so `page` is accepted for call-signature
// compatibility but isn't sent.
function filter(mainCategory, subCategory, thirdCategory, page, limit) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "filter",
            device_type: "Web",
            filter: {
                MainCategory: mainCategory || "",
                SubCategory: subCategory || "",
                ThirdCategory: thirdCategory || ""
            },
            limit: limit || 16
        }),
        success: function (data) {
            renderStoreResults({
                products: data.products || [],
                similar_products: data.similar_products || [],
                available_filters: data.available_filters || {}
            });
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);

            if (res.error === "No products found.") {
                renderStoreResults({ products: [], similar_products: [], available_filters: {} });
            } else {
                console.log("filter failed:", res.error);
            }
        }
    });
}

function mainCategories(onLoaded) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "main_categories",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                const $select = $("#store-filter-main-category");
                data.categories.forEach(function (category) {
                    $select.append($("<option></option>").val(category.name).text(category.name).attr("data-icon", category.icon));
                });
            }
            if (typeof onLoaded === "function") {
                onLoaded();
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("mainCategories failed:", res.error);
        }
    });
}

function subCategories(mainCategory, onLoaded) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "sub_categories",
            device_type: "Web",
            main_category: mainCategory
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                const $select = $("#store-filter-sub-category");
                $select.prop("disabled", false).find("option:not(:first)").remove();
                data.sub_categories.forEach(function (category) {
                    $select.append($("<option></option>").val(category.name).text(category.name).attr("data-icon", category.icon));
                });
            }
            if (typeof onLoaded === "function") {
                onLoaded();
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("subCategories failed:", res.error);
        }
    });
}

function thirdCategories(subCategory, onLoaded) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "third_categories",
            device_type: "Web",
            sub_category: subCategory
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                const $select = $("#store-filter-third-category");
                $select.prop("disabled", false).find("option:not(:first)").remove();
                data.third_categories.forEach(function (category) {
                    $select.append($("<option></option>").val(category.name).text(category.name).attr("data-icon", category.icon));
                });
            }
            if (typeof onLoaded === "function") {
                onLoaded();
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("thirdCategories failed:", res.error);
        }
    });
}

// search
function search(searchTerm) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "search",
            device_type: "Web",
            search_term: searchTerm
        }),
        success: function (data) {
            const $results = $("#search-results");

            // #search-results lives outside .header-main (which clips
            // overflow), so it's positioned relative to <header> via JS
            // instead of plain CSS nesting under .search-wrap.
            const wrapRect = document.querySelector(".search-wrap").getBoundingClientRect();
            const headerRect = document.querySelector("header").getBoundingClientRect();

            $results.css({
                left: (wrapRect.left - headerRect.left) + "px",
                top: (wrapRect.bottom - headerRect.top + 8) + "px",
                width: wrapRect.width + "px"
            });

            $results.empty();

            if (data.message || !data.products || !data.products.length) {
                const $empty = $('<div class="Search_Result_Empty"></div>').append(
                    $('<h3 class="Search_Result_Empty_Title"></h3>').text('No results found'),
                    $('<p class="Search_Result_Empty_Subtitle"></p>').text('We couldn\'t find any products matching "' + searchTerm + '". Try searching for something else.'),
                    $('<div class="Search_Result_Empty_Divider"></div>').append($('<span></span>').text('Try these tips')),
                    $('<div class="Search_Result_Empty_Tips"></div>').append(
                        $('<div class="Search_Result_Empty_Tip"></div>').append(
                            $('<span class="Search_Result_Empty_Tip_Icon Icon_Circle"></span>').append($('<img src="/HeyDaniel/Assets/Icons/search.svg" alt="">')),
                            $('<p></p>').text('Check your spelling')
                        ),
                        $('<div class="Search_Result_Empty_Tip"></div>').append(
                            $('<span class="Search_Result_Empty_Tip_Icon Icon_Circle"></span>').append($('<img src="/HeyDaniel/Assets/Icons/cart.svg" alt="">')),
                            $('<p></p>').text('Try more general terms')
                        ),
                        $('<div class="Search_Result_Empty_Tip"></div>').append(
                            $('<span class="Search_Result_Empty_Tip_Icon Icon_Circle"></span>').append($('<img src="/HeyDaniel/Assets/Icons/tag.svg" alt="">')),
                            $('<p></p>').text('Browse our categories')
                        )
                    ),
                    $('<a class="Primary_Btn Primary_Btn--auto Search_Result_Empty_Btn"></a>').attr('href', '/HeyDaniel/Interface/Sheets/Store').text('Browse All Categories')
                );

                $results.append($empty);
                $results.addClass("active");
                return;
            }

            data.products.forEach(function (product) {
                const productId = product.product_id;
                const brand = product.brand;
                const name = product.name;
                const size = product.oz;
                const price = product.price;
                const picture = product.picture;
                const isOnSale = product.is_on_sale;
                const salePrice = product.sale_price;
                const isBogo = product.is_bogo;

                const nowPrice = isOnSale ? salePrice : price;
                const wasPrice = isOnSale ? price : null;

                const badgeHTML = isBogo
                    ? '<span class="Product_Card_Badge Bogo">BOGO</span>'
                    : (isOnSale ? '<span class="Product_Card_Badge">Sale</span>' : '');

                let $tag = null;
                if (isOnSale && wasPrice) {
                    const savings = wasPrice - nowPrice;
                    const percent = Math.round((savings / wasPrice) * 100);
                    $tag = $('<span class="Search_Result_Discount"></span>').append(
                        $('<img src="/HeyDaniel/Assets/Icons/tag.svg" alt="">'),
                        document.createTextNode('Save $' + savings.toFixed(2) + ' (' + percent + '%)')
                    );
                } else if (isBogo) {
                    $tag = $('<span class="Search_Result_Bogo_Tag"></span>').text('Buy One, Get One 50% Off');
                } else if (size) {
                    $tag = $('<span class="Search_Result_Size"></span>').text(size);
                }

                // Clicking a search result goes to Store filtered to similar
                // items (same category) rather than that one product's detail
                // page - falls back to an unfiltered Store if the product has
                // no category on file.
                const similarItemsUrl = product.category
                    ? "/HeyDaniel/Interface/Sheets/Store?category=" + encodeURIComponent(product.category)
                    : "/HeyDaniel/Interface/Sheets/Store";

                const $item = $('<div class="Search_Result_Item"></div>').append(
                    $('<div class="Search_Result_Image"></div>')
                        .css("background-image", "url(" + picture + ")")
                        .append(badgeHTML),
                    $('<div class="Search_Result_Info"></div>').append(
                        $('<p class="Search_Result_Brand"></p>').text(brand),
                        $('<p class="Search_Result_Name"></p>').text(name),
                        $('<p class="Search_Result_Price"></p>').append(
                            $('<span></span>').text('$' + nowPrice.toFixed(2)),
                            wasPrice ? $('<span class="Search_Result_Price_Was"></span>').text('$' + wasPrice.toFixed(2)) : null
                        ),
                        $tag
                    ),
                    buildSearchResultCartControl(productId, product.in_cart || product.in_process, product.quantity)
                ).on('click', function () {
                    window.location.href = similarItemsUrl;
                });

                $results.append($item);
            });

            $results.addClass("active");
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("search failed:", res.error);
        }
    });
}

// checkout
function checkout(address, paymentMethodId, tipAmount, deliveryMethod, saveCard, onSuccess, onError) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "checkout",
            device_type: "Web",
            address: address,
            payment_method_id: paymentMethodId,
            tip_amount: tipAmount || 0,
            delivery_method: deliveryMethod,
            save_card: saveCard !== false
        }),
        success: function (data) {
            if (data.success) {
                if (typeof onSuccess === "function") {
                    onSuccess(data.order_id);
                }
            } else if (typeof onError === "function") {
                onError(data.message || data.error || "Checkout failed. Please try again.");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            if (typeof onError === "function") {
                onError(res.message || res.error || "Checkout failed. Please try again.");
            }
        }
    });
}

function subscribeMembership(paymentMethodId, saveCard, onSuccess, onError) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "subscribe_membership",
            device_type: "Web",
            payment_method_id: paymentMethodId,
            save_card: saveCard !== false
        }),
        success: function (data) {
            if (data.success) {
                if (typeof onSuccess === "function") {
                    onSuccess();
                }
            } else if (typeof onError === "function") {
                onError(data.message || data.error || "Subscription failed. Please try again.");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            if (typeof onError === "function") {
                onError(res.message || res.error || "Subscription failed. Please try again.");
            }
        }
    });
}

function newsletterSubscribe(email, onSuccess, onError) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "newsletter_subscribe",
            device_type: "Web",
            email: email
        }),
        success: function (data) {
            if (data.success) {
                if (typeof onSuccess === "function") {
                    onSuccess(data.message || "You're subscribed!");
                }
            } else if (typeof onError === "function") {
                onError(data.message || data.error || "Subscription failed. Please try again.");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            if (typeof onError === "function") {
                onError(res.message || res.error || "Subscription failed. Please try again.");
            }
        }
    });
}

function cancelMembership(onSuccess, onError) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "cancel_membership",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.success) {
                if (typeof onSuccess === "function") {
                    onSuccess();
                }
            } else if (typeof onError === "function") {
                onError(data.message || data.error || "Unable to cancel membership. Please try again.");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            if (typeof onError === "function") {
                onError(res.message || res.error || "Unable to cancel membership. Please try again.");
            }
        }
    });
}

// product detail

function productDetail(product_id) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "product_detail",
            device_type: "Web",
            product_id: product_id
        }),
        success: function (data) {
            $("#item-loading").hide();

            if (data.message || !data.product) {
                $("#item-not-found").show();
                return;
            }

            const product = data.product;
            const hasRating = typeof product.rating === "number";
            const nowPrice = product.is_on_sale ? product.sale_price : product.price;
            const wasPrice = product.is_on_sale ? product.price : null;

            document.title = product.name + " - HeyDaniel";

            const badgeHTML = product.is_bogo
                ? '<span class="Product_Card_Badge Bogo">BOGO</span>'
                : (product.is_on_sale ? '<span class="Product_Card_Badge">Sale</span>' : '');

            $("#item-image-wrap").css("background-image", "url(" + product.picture + ")").html(badgeHTML);

            const breadcrumb = [product.main_category, product.sub_category, product.third_category]
                .filter(Boolean)
                .join(" / ");
            $("#item-breadcrumb").text(breadcrumb);

            $("#item-brand").text(product.brand);
            $("#item-name").text(product.name);

            $("#item-rating").html(
                renderProductStars(hasRating ? product.rating : 0) +
                (hasRating
                    ? '<span class="Product_Card_Rating_Value">' + product.rating + '</span><span class="Product_Card_Rating_Count">(' + product.review_count + ' reviews)</span>'
                    : '<span class="Product_Card_Rating_Count">' + product.rating + '</span>')
            );

            $("#item-price").html(
                '<span class="Product_Card_Price_Now">$' + nowPrice + '</span>' +
                (wasPrice ? '<span class="Product_Card_Price_Was">$' + wasPrice + '</span>' : '')
            );

            $("#item-meta").html(
                (!product.in_stock ? '<span class="Item_Out_Of_Stock">Out of stock</span>' : '')
            );

            if (product.oz) {
                $("#item-size-wrap").show();
                $("#item-size").empty().append($('<option></option>').val(product.oz).text(product.oz));
            } else {
                $("#item-size-wrap").hide();
            }

            $("#item-description").text(product.description || "");

            function wishlistLabel(isSaved) {
                return isSaved ? "Remove from wishlist" : "Add to wishlist";
            }

            const $wishlistBtn = $('<button class="Item_Wishlist_Btn Text_Btn" type="button"></button>')
                .toggleClass('Active', !!product.is_saved)
                .append(
                    $('<img src="/HeyDaniel/Assets/Icons/heart.svg" alt="">'),
                    $('<span></span>').text(wishlistLabel(product.is_saved))
                )
                .on('click', function () {
                    const $btn = $(this);
                    savedAdd(product.product_id, function (data) {
                        $btn.toggleClass('Active', !!data.is_saved);
                        $btn.find('span').text(wishlistLabel(data.is_saved));
                    });
                });

            const $cartControl = product.in_stock
                ? buildCartControl(product.product_id, product.in_cart, product.quantity)
                : $('<p class="Item_Out_Of_Stock">Currently unavailable</p>');

            $("#item-cart-control").empty().append($cartControl);
            $("#item-wishlist-link").empty().append($wishlistBtn);

            $("#item-content").show();

            itemPush(product.product_id, "RecentlyViewed");
            getReviews(product.product_id, 1, 5);
            loadRelatedProducts(product.product_id, product.main_category);
        },
        error: function () {
            $("#item-loading").hide();
            $("#item-not-found").show();
        }
    });
}

// order history

function buildOrderThumbs(order) {
    const pictures = order.pictures || [];

    if (!pictures.length) {
        return $('<div class="Order_Row_Icon Icon_Circle"></div>').append($('<img src="/HeyDaniel/Assets/Icons/shopping-cart.svg" alt="">'));
    }

    const $stack = $('<div class="Order_Row_Thumbs"></div>');
    pictures.forEach(function (picture) {
        $stack.append($('<div class="Order_Row_Thumb"></div>').css('background-image', 'url(' + picture + ')'));
    });

    if (order.more_count > 0) {
        $stack.append($('<div class="Order_Row_Thumb Order_Row_Thumb_More"></div>').text('+' + order.more_count));
    }

    return $stack;
}

function getOrderCode(order) {
    const orderDate = new Date(order.date_added);
    const hasValidDate = !isNaN(orderDate);
    return '#HD-' + (hasValidDate ? orderDate.toISOString().slice(0, 10).replace(/-/g, '') : '0') +
        '-' + String(order.order_id).padStart(4, '0');
}

function orderStatusSuffix(status) {
    const normalized = status.toLowerCase();
    if (normalized === 'delivered') return 'Delivered';
    if (normalized === 'pending') return 'Pending';
    if (normalized === 'processing') return 'Processing';
    if (normalized === 'shipped') return 'Shipped';
    if (normalized === 'cancelled') return 'Cancelled';
    return '';
}

const ORDER_STATUS_ICONS = {
    Delivered: 'fa-check-circle',
    Shipped: 'fa-truck',
    Processing: 'fa-clock',
    Pending: 'fa-clock',
    Cancelled: 'fa-times-circle'
};

function orderStatusIcon(statusSuffix) {
    return ORDER_STATUS_ICONS[statusSuffix] || 'fa-circle';
}

function orderSubstatusText(order, statusSuffix) {
    if (statusSuffix === 'Delivered') {
        const deliveredDate = new Date(order.time_delivered);
        if (!isNaN(deliveredDate)) {
            return 'Delivered on ' + deliveredDate.toLocaleDateString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
        }
        return 'Delivered';
    }
    if (statusSuffix === 'Shipped') return 'On its way to you';
    if (statusSuffix === 'Processing' || statusSuffix === 'Pending') return 'Preparing your order';
    if (statusSuffix === 'Cancelled') return 'Order cancelled';
    return '';
}

const ORDER_ITEM_CLAIM_LABELS = { 1: 'Missing', 2: 'Expired', 3: 'Bad Quality' };

// Before delivery the badge just mirrors the order's own status. Once
// delivered, Process's own per-item columns take over: isStocked flags an
// out-of-stock substitution, and isMissing becomes a claim code (1 missing /
// 2 expired / 3 bad quality) if the customer reports an issue.
function orderItemStatusBadge(order, item, statusSuffix) {
    if (statusSuffix !== 'Delivered') {
        return { text: statusSuffix || order.status, className: statusSuffix ? 'Order_Row_Status_' + statusSuffix : '' };
    }

    if (!item.in_stock) {
        return { text: 'Out of Stock', className: 'Order_Row_Status_Issue' };
    }

    const claimLabel = ORDER_ITEM_CLAIM_LABELS[item.is_missing];
    if (claimLabel) {
        return { text: claimLabel, className: 'Order_Row_Status_Issue' };
    }

    return { text: 'Delivered', className: 'Order_Row_Status_Delivered' };
}

function saveProfileEdit(name, phone) {
    return $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "update_profile",
            device_type: "Web",
            name: name,
            phone: phone
        })
    });
}

function saveAddress(payload) {
    return $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify(Object.assign({ action: "save_address", device_type: "Web" }, payload))
    });
}

function deleteAddress(addressId) {
    return $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "delete_address",
            device_type: "Web",
            address_id: addressId
        })
    });
}

function cancelOrder() {
    return $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "cancel_order",
            device_type: "Web"
        })
    });
}

function rescheduleDelivery(windowLabel) {
    return $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "reschedule_delivery",
            device_type: "Web",
            window: windowLabel
        })
    });
}

function getRescheduleWindows() {
    return $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "reschedule_windows",
            device_type: "Web"
        })
    });
}

function listAddressesRequest() {
    return $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "list_addresses",
            device_type: "Web"
        })
    });
}

function buildAddressCard(address) {
    const $card = $('<div class="Address_Card"></div>')
        .attr('data-address-id', address.address_id)
        .attr('data-label', address.label)
        .attr('data-address', address.address)
        .attr('data-apt', address.apt)
        .attr('data-city', address.city)
        .attr('data-state', address.state)
        .attr('data-zip', address.zip)
        .attr('data-phone', address.phone)
        .attr('data-gate-code', address.gate_code)
        .attr('data-note', address.note);

    const $label = $('<p class="Address_Card_Label"></p>').text(address.label + ' ');
    if (address.is_default) {
        $label.append($('<span class="Address_Default_Badge"></span>').text('Default'));
    }

    $card.append(
        $('<div class="Address_Card_Icon Icon_Circle"></div>').append($('<img alt="">').attr('src', '/HeyDaniel/Assets/Icons/home.svg')),
        $('<div class="Address_Card_Body"></div>').append(
            $label,
            $('<p class="Profile_Address"></p>').html(
                $('<span></span>').text(address.address + (address.apt ? ', ' + address.apt : '')).prop('outerHTML') + '<br>' +
                $('<span></span>').text(address.city + ', ' + address.state + ' ' + address.zip).prop('outerHTML')
            )
        ),
        $('<div class="Address_Card_Menu_Wrap"></div>').append(
            $('<button type="button" class="Address_Card_Menu Btn_Icon_Ghost Btn_Icon_Ghost--sm" aria-label="Address options"></button>')
                .append('<i class="fas fa-ellipsis-v" aria-hidden="true"></i>'),
            $('<div class="Address_Card_Menu_Dropdown"></div>').append(
                $('<button type="button" class="Address_Card_Edit_Btn Btn_Nav_Row"></button>').text('Edit'),
                $('<button type="button" class="Address_Card_Delete_Btn Btn_Nav_Row"></button>').text('Delete')
            )
        )
    );

    return $card;
}

// Shared by the Profile page's mini address book and the full Addresses
// page — both render the same .Address_Card markup, just one server-side
// (Profile, single card) and one via buildAddressCard() (Addresses, many).
function initAddressBook(containerSelector, addBtnSelector) {
    function openAddressModal() {
        $("#address-modal-overlay").show();
    }

    function closeAddressModal() {
        $("#address-modal-overlay").hide();
    }

    function resetAddressForm() {
        $("#address-form-id").val("");
        $("#address-form-label").val("");
        $("#address-form-street").val("");
        $("#address-form-apt").val("");
        $("#address-form-city").val("");
        $("#address-form-state").val("");
        $("#address-form-zip").val("");
        $("#address-form-phone").val("");
        $("#address-form-gate").val("");
        $("#address-form-note").val("");
    }

    $(addBtnSelector).on("click", function () {
        resetAddressForm();
        $("#address-modal-title").text("Add New Address");
        openAddressModal();
    });

    $(containerSelector).on("click", ".Address_Card_Menu", function (e) {
        e.stopPropagation();
        const $dropdown = $(this).siblings(".Address_Card_Menu_Dropdown");
        const wasOpen = $dropdown.hasClass("Open");
        $(".Address_Card_Menu_Dropdown.Open").removeClass("Open");
        if (!wasOpen) {
            $dropdown.addClass("Open");
        }
    });

    $(document).on("click", function () {
        $(".Address_Card_Menu_Dropdown.Open").removeClass("Open");
    });

    $(containerSelector).on("click", ".Address_Card_Edit_Btn", function () {
        const $card = $(this).closest(".Address_Card");
        $("#address-form-id").val($card.data("address-id"));
        $("#address-form-label").val($card.data("label"));
        $("#address-form-street").val($card.data("address"));
        $("#address-form-apt").val($card.data("apt"));
        $("#address-form-city").val($card.data("city"));
        $("#address-form-state").val($card.data("state"));
        $("#address-form-zip").val($card.data("zip"));
        $("#address-form-phone").val($card.data("phone"));
        $("#address-form-gate").val($card.data("gate-code"));
        $("#address-form-note").val($card.data("note"));
        $("#address-modal-title").text("Edit Address");
        openAddressModal();
    });

    $(containerSelector).on("click", ".Address_Card_Delete_Btn", function () {
        const addressId = $(this).closest(".Address_Card").data("address-id");
        showAppConfirm("Delete this address?", function () {
            deleteAddress(addressId)
                .done(function () {
                    location.reload();
                })
                .fail(function (xhr) {
                    const res = JSON.parse(xhr.responseText);
                    showAppAlert(res.error || "Failed to delete address.");
                });
        }, { title: "Delete address", confirmLabel: "Delete" });
    });

    $("#address-modal-close, #address-modal-cancel").on("click", function () {
        closeAddressModal();
    });

    $("#address-modal-overlay").on("click", function (e) {
        if (e.target === this) {
            closeAddressModal();
        }
    });

    $("#address-modal-save").on("click", function () {
        const street = $("#address-form-street").val().trim();
        const city = $("#address-form-city").val().trim();
        const state = $("#address-form-state").val().trim();
        const zip = $("#address-form-zip").val().trim();

        if (!street || !city || !state || !zip) {
            showAppAlert("Please fill in address, city, state, and zip code.");
            return;
        }

        saveAddress({
            address_id: $("#address-form-id").val(),
            label: $("#address-form-label").val().trim(),
            address: street,
            apt: $("#address-form-apt").val().trim(),
            city: city,
            state: state,
            zip: zip,
            phone: $("#address-form-phone").val().trim(),
            gate_code: $("#address-form-gate").val().trim(),
            note: $("#address-form-note").val().trim()
        })
            .done(function () {
                location.reload();
            })
            .fail(function (xhr) {
                const res = JSON.parse(xhr.responseText);
                showAppAlert(res.error || "Failed to save address.");
            });
    });
}

function buildOrderRow(order, options) {
    options = options || {};

    const orderDate = new Date(order.date_added);
    const hasValidDate = !isNaN(orderDate);
    const formattedDate = hasValidDate
        ? orderDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
        : order.date_added;
    const orderCode = getOrderCode(order);
    const statusSuffix = orderStatusSuffix(order.status);

    const $row = $('<div class="Order_Row"></div>')
        .addClass(statusSuffix ? 'Order_Row_' + statusSuffix : '')
        .attr('data-order-id', order.order_id)
        .attr('data-order-code', orderCode)
        .append(
            buildOrderThumbs(order),
            $('<div class="Order_Row_Info"></div>').append(
                $('<p class="Order_Row_Number"></p>').text(orderCode),
                $('<p class="Order_Row_Meta"></p>').text(formattedDate + ' · ' + order.item_count + ' item(s)')
            ),
            $('<span class="Order_Row_Status"></span>').addClass(statusSuffix ? 'Order_Row_Status_' + statusSuffix : '').text(statusSuffix || order.status),
            $('<p class="Order_Row_Total"></p>').text('$' + order.total.toFixed(2)),
            options.showViewDetailsBtn
                ? $('<button type="button" class="Secondary_Light_Btn Order_Row_View_Details"></button>').text('View Details')
                : $('<i class="fas fa-chevron-down Order_Row_Chevron" aria-hidden="true"></i>')
        );

    return $('<div class="Order_Row_Wrap"></div>').append(
        $row,
        $('<div class="Order_Row_Detail"></div>').hide()
    );
}

function buildOrdersPageRow(order) {
    const orderDate = new Date(order.date_added);
    const hasValidDate = !isNaN(orderDate);
    const formattedDate = hasValidDate
        ? orderDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' })
        : order.date_added;
    const orderCode = getOrderCode(order);
    const statusSuffix = orderStatusSuffix(order.status);
    const pictures = order.pictures || [];

    const $thumb = pictures.length
        ? $('<div class="Orders_Row_Thumb"></div>').css('background-image', 'url(' + pictures[0] + ')')
        : $('<div class="Orders_Row_Thumb Orders_Row_Thumb_Empty"></div>').append('<img src="/HeyDaniel/Assets/Icons/shopping-cart.svg" alt="">');

    const $row = $('<div class="Order_Row"></div>')
        .attr('data-order-id', order.order_id)
        .attr('data-order-code', orderCode)
        .append(
            $thumb,
            $('<div class="Order_Row_Info"></div>').append(
                $('<p class="Order_Row_Number"></p>').text(orderCode),
                $('<p class="Order_Row_Meta"></p>').text(formattedDate + ' · ' + order.item_count + ' item(s)'),
                $('<span class="Order_Row_Status"></span>')
                    .addClass(statusSuffix ? 'Order_Row_Status_' + statusSuffix : '')
                    .append($('<i aria-hidden="true"></i>').addClass('fas ' + orderStatusIcon(statusSuffix)))
                    .append(' ' + (statusSuffix || order.status)),
                $('<p class="Order_Row_Substatus"></p>').text(orderSubstatusText(order, statusSuffix))
            ),
            $('<div class="Order_Row_Right"></div>').append(
                $('<p class="Order_Row_Total"></p>').text('$' + order.total.toFixed(2)),
                $('<button type="button" class="Order_Row_View_Details"></button>')
                    .append('View Details ')
                    .append('<i class="fas fa-chevron-right" aria-hidden="true"></i>')
            )
        );

    return $('<div class="Order_Row_Wrap"></div>').append(
        $row,
        $('<div class="Order_Row_Detail"></div>').hide()
    );
}

function orderHistory() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "order_history",
            device_type: "Web"
        }),
        success: function (data) {
            const $container = $("#order-history-container");
            $container.empty();

            const orders = data.orders || [];
            const totalSpent = orders.reduce(function (sum, order) { return sum + order.total; }, 0);

            $("#stat-orders-count").text(orders.length);
            $("#stat-total-spent").text('$' + totalSpent.toFixed(2));

            if (!orders.length) {
                $("#order-history-empty").show();
                $("#order-history-view-all-btn").hide();
                return;
            }

            $("#order-history-empty").hide();
            $("#order-history-view-all-btn").show();

            orders.forEach(function (order) {
                $container.append(buildOrderRow(order));
            });
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("orderHistory failed:", res.error);
        }
    });
}

const ORDERS_PAGE_SIZE = 5;
let ordersPageAllOrders = [];
let ordersPageStatusFilter = 'all';
let ordersPageCurrentPage = 1;
let ordersPageSearchQuery = '';
let ordersPageSortBy = 'newest';

function initOrdersPage() {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "order_history",
            device_type: "Web"
        }),
        success: function (data) {
            ordersPageAllOrders = data.orders || [];
            renderOrdersPage();
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("initOrdersPage failed:", res.error);
        }
    });
}

function setOrdersStatusFilter(status) {
    ordersPageStatusFilter = status;
    ordersPageCurrentPage = 1;
    renderOrdersPage();
}

function setOrdersPage(page) {
    ordersPageCurrentPage = page;
    renderOrdersPage();
}

function setOrdersSearchQuery(query) {
    ordersPageSearchQuery = query.trim().toLowerCase();
    ordersPageCurrentPage = 1;
    renderOrdersPage();
}

function setOrdersSort(sortBy) {
    ordersPageSortBy = sortBy;
    ordersPageCurrentPage = 1;
    renderOrdersPage();
}

function orderMatchesSearch(order, query) {
    if (!query) return true;
    return getOrderCode(order).toLowerCase().includes(query);
}

function sortOrders(orders, sortBy) {
    const sorted = orders.slice();
    if (sortBy === 'oldest') {
        sorted.sort(function (a, b) { return new Date(a.date_added) - new Date(b.date_added); });
    } else if (sortBy === 'highest') {
        sorted.sort(function (a, b) { return b.total - a.total; });
    } else if (sortBy === 'lowest') {
        sorted.sort(function (a, b) { return a.total - b.total; });
    } else {
        sorted.sort(function (a, b) { return new Date(b.date_added) - new Date(a.date_added); });
    }
    return sorted;
}

function renderOrdersTabCounts() {
    const searchScoped = ordersPageAllOrders.filter(function (order) {
        return orderMatchesSearch(order, ordersPageSearchQuery);
    });

    $("#tab-count-all").text(searchScoped.length).toggle(searchScoped.length > 0);
    ['pending', 'processing', 'shipped', 'delivered', 'cancelled'].forEach(function (status) {
        const count = searchScoped.filter(function (order) {
            return order.status.toLowerCase() === status;
        }).length;
        $("#tab-count-" + status).text(count).toggle(count > 0);
    });
}

function renderOrdersPage() {
    const $container = $("#orders-list-container");
    $container.empty();

    renderOrdersTabCounts();

    const searchScoped = ordersPageAllOrders.filter(function (order) {
        return orderMatchesSearch(order, ordersPageSearchQuery);
    });

    const statusScoped = ordersPageStatusFilter === 'all'
        ? searchScoped
        : searchScoped.filter(function (order) {
            return order.status.toLowerCase() === ordersPageStatusFilter;
        });

    const filtered = sortOrders(statusScoped, ordersPageSortBy);

    if (!filtered.length) {
        $("#orders-empty").show();
        $("#orders-pagination").hide();
        return;
    }
    $("#orders-empty").hide();

    const totalPages = Math.ceil(filtered.length / ORDERS_PAGE_SIZE);
    if (ordersPageCurrentPage > totalPages) {
        ordersPageCurrentPage = totalPages;
    }

    const startIndex = (ordersPageCurrentPage - 1) * ORDERS_PAGE_SIZE;
    const pageItems = filtered.slice(startIndex, startIndex + ORDERS_PAGE_SIZE);

    pageItems.forEach(function (order) {
        $container.append(buildOrdersPageRow(order));
    });

    $("#orders-pagination-label").text(
        "Showing " + (startIndex + 1) + " to " + Math.min(startIndex + ORDERS_PAGE_SIZE, filtered.length) + " of " + filtered.length + " orders"
    );

    const $pages = $("#orders-pagination-numbers").empty();
    if (totalPages > 1) {
        for (let i = 1; i <= totalPages; i++) {
            $('<button type="button" class="Orders_Page_Btn"></button>')
                .toggleClass("active", i === ordersPageCurrentPage)
                .attr("data-page", i)
                .text(i)
                .appendTo($pages);
        }
    }

    $("#orders-page-prev").prop("disabled", ordersPageCurrentPage <= 1);
    $("#orders-page-next").prop("disabled", ordersPageCurrentPage >= totalPages);

    $("#orders-pagination").show();
}

function initOrderRowInteractions(containerSelector) {
    $(containerSelector).on("click", ".Order_Row", function () {
        toggleOrderRow($(this));
    });

    $(containerSelector).on("click", ".Order_Detail_Item_Menu_Btn", function (e) {
        e.stopPropagation();
        const $menu = $(this).closest(".Order_Detail_Item_Menu");
        const wasOpen = $menu.hasClass("Open");
        $(".Order_Detail_Item_Menu.Open").removeClass("Open");
        if (!wasOpen) {
            $menu.addClass("Open");
        }
    });

    $(document).on("click", function () {
        $(".Order_Detail_Item_Menu.Open").removeClass("Open");
    });
}

function toggleOrderRow($row) {
    const $wrap = $row.closest(".Order_Row_Wrap");
    const $detail = $wrap.find(".Order_Row_Detail");

    if ($wrap.hasClass("expanded")) {
        $wrap.removeClass("expanded");
        $detail.slideUp(200);
        return;
    }

    $wrap.siblings(".Order_Row_Wrap.expanded").removeClass("expanded")
        .find(".Order_Row_Detail").slideUp(200);

    $wrap.addClass("expanded");

    if ($detail.data("loaded")) {
        $detail.slideDown(200);
        return;
    }

    $detail.html('<p class="Order_Row_Detail_Loading">Loading order details…</p>').slideDown(200);
    fetchOrderDetail($row.data("order-id"), $detail);
}

function fetchOrderDetail(orderId, $detail) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "order_details",
            order_id: orderId,
            device_type: "Web"
        }),
        success: function (data) {
            if (!data.order) {
                $detail.html('<p class="Order_Row_Detail_Loading">Couldn\'t load order details.</p>');
                console.log("orderDetails failed:", data.error);
                return;
            }

            renderOrderDetail($detail, data.order);
            $detail.data("loaded", true);
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            $detail.html('<p class="Order_Row_Detail_Loading">Couldn\'t load order details.</p>');
            console.log("orderDetails failed:", res.error);
        }
    });
}

function renderOrderDetail($detail, order) {
    $detail.empty();

    const statusSuffix = orderStatusSuffix(order.status);
    const $items = $('<div class="Order_Detail_Item_List"></div>');
    order.items.forEach(function (item) {
        const $item = $('<div class="Order_Detail_Item"></div>').append(
            $('<div class="Order_Detail_Item_Image"></div>').css('background-image', 'url(' + item.picture + ')'),
            $('<div class="Order_Detail_Item_Info"></div>').append(
                $('<p class="Order_Detail_Item_Name"></p>').text(item.brand + ' ' + item.name),
                $('<p class="Order_Detail_Item_Qty"></p>').text('Qty: ' + item.quantity)
            )
        );

        if (statusSuffix === 'Delivered') {
            $item.append(
                $('<div class="Order_Detail_Item_Menu"></div>').append(
                    $('<button type="button" class="Order_Detail_Item_Menu_Btn Btn_Icon_Ghost Btn_Icon_Ghost--sm" aria-label="Report an issue with this item"></button>')
                        .append('<i class="fas fa-ellipsis-v" aria-hidden="true"></i>'),
                    $('<div class="Order_Detail_Item_Menu_Dropdown"></div>').append(
                        $('<button type="button" class="Btn_Nav_Row" data-coming-soon="Reporting a missing item"></button>').text('Missing'),
                        $('<button type="button" class="Btn_Nav_Row" data-coming-soon="Reporting an expired item"></button>').text('Expired'),
                        $('<button type="button" class="Btn_Nav_Row" data-coming-soon="Reporting a bad/damaged item"></button>').text('Bad Quality')
                    )
                )
            );
        }

        const badge = orderItemStatusBadge(order, item, statusSuffix);
        $item.append(
            $('<div class="Order_Detail_Item_Price_Wrap"></div>').append(
                $('<p class="Order_Detail_Item_Price"></p>').text('$' + (item.price * item.quantity).toFixed(2)),
                $('<span class="Order_Row_Status"></span>').addClass(badge.className).text(badge.text)
            )
        );

        $items.append($item);
    });
    $detail.append($items);

    const $totals = $('<div class="Order_Detail_Totals"></div>');
    $totals.append(
        $('<div class="Order_Detail_Total_Row"></div>').append($('<span></span>').text('Subtotal'), $('<span></span>').text('$' + order.subtotal.toFixed(2))),
        $('<div class="Order_Detail_Total_Row"></div>').append($('<span></span>').text('Tax'), $('<span></span>').text('$' + order.tax.toFixed(2)))
    );

    if (order.tip > 0) {
        $totals.append(
            $('<div class="Order_Detail_Total_Row"></div>').append($('<span></span>').text('Tip'), $('<span></span>').text('$' + order.tip.toFixed(2)))
        );
    }

    $totals.append(
        $('<div class="Order_Detail_Total_Row Order_Detail_Total_Row_Grand"></div>').append($('<span></span>').text('Total'), $('<span></span>').text('$' + order.total.toFixed(2)))
    );

    $detail.append($totals);
}

// reviews

function getReviews(product_id, page, limit) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "reviews_get",
            product_id: product_id,
            page: page,
            limit: limit
        }),
        success: function (data) {
            const $list = $("#item-reviews-list");
            const $summary = $("#item-reviews-summary");
            const $more = $("#item-reviews-more");

            if (page === 1) {
                $list.empty();
                $summary.html(
                    data.total_count > 0
                        ? renderProductStars(data.avg_rating) +
                          '<span class="Product_Card_Rating_Value">' + data.avg_rating + '</span>' +
                          '<span class="Product_Card_Rating_Count">(' + data.total_count + ' reviews)</span>'
                        : '<p class="Item_No_Reviews">No reviews yet. Be the first to review this product.</p>'
                );
            }

            if (data.message || !data.reviews || !data.reviews.length) {
                $more.hide();
                return;
            }

            data.reviews.forEach(function (review) {
                const reviewDate = new Date(review.date_added);
                const formattedDate = isNaN(reviewDate)
                    ? review.date_added
                    : reviewDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

                $list.append(
                    $('<div class="Item_Review"></div>').append(
                        $('<div class="Item_Review_Header"></div>').append(
                            $('<p class="Item_Review_Stars"></p>').html(renderProductStars(review.stars)),
                            $('<span class="Item_Review_Author"></span>').text(review.user_name),
                            $('<span class="Item_Review_Date"></span>').text(formattedDate)
                        ),
                        $('<h4 class="Item_Review_Title"></h4>').text(review.title),
                        $('<p class="Item_Review_Body"></p>').text(review.review)
                    )
                );
            });

            const loadedCount = $list.find(".Item_Review").length;
            $more.toggle(loadedCount < data.total_count);
            $more.data("nextPage", page + 1);
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("getReviews failed:", res.error);
        }
    });
}

function addReview(product_id, stars, expectation, title, review) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "reviews_add",
            device_type: "Web",
            product_id: product_id,
            stars: stars,
            expectation: expectation,
            title: title,
            review: review
        }),
        success: function (data) {
            const $error = $("#item-review-error");

            if (data.message) {
                $error.text(data.message).show();
            } else {
                $error.hide();
                $("#item-review-form")[0].reset();
                $("#item-review-form .Star_Btn").removeClass("selected");
                getReviews(product_id, 1, 5);
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            $("#item-review-error").text(res.error || "Something went wrong. Please try again.").show();
        }
    });
}

// related products (item detail page)

function loadRelatedProducts(productId, mainCategory) {
    const $section = $("#item-related-section");
    const $slider = $("#item-related-products");

    if (!mainCategory) {
        $section.hide();
        return;
    }

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "store",
            device_type: "Web",
            filter: { MainCategory: mainCategory },
            limit: 12
        }),
        success: function (data) {
            const products = (data.products || []).filter(function (product) {
                return product.product_id !== productId;
            });

            if (!products.length) {
                $section.hide();
                return;
            }

            $slider.empty();
            products.forEach(function (product) {
                $slider.append(buildProductCard(product, false));
            });
            $section.show();
        },
        error: function () {
            $section.hide();
        }
    });
}

// recently viewed

function renderProductStars(rating) {
    const filled = Math.round(parseFloat(rating) || 0);
    let stars = "";
    for (let i = 1; i <= 5; i++) {
        stars += i <= filled
            ? '<span class="Star_Filled">&#9733;</span>'
            : '<span class="Star_Empty">&#9733;</span>';
    }
    return stars;
}

function buildCartControl(productId, inCart, quantity, onEmpty) {
    const $control = $('<div class="Product_Card_Cart_Control"></div>');

    function renderAddButton() {
        $control.empty().append(
            $('<button class="Add_To_Cart_Btn" type="button"></button>')
                .text(getHasActiveOrder() ? 'Add to order' : 'Add to cart')
                .on('click', function () {
                    addProduct(productId, function (data) {
                        renderQty(data.quantity);
                    });
                })
        );
    }

    function renderQty(qty) {
        if (!qty) {
            if (typeof onEmpty === "function") {
                onEmpty();
            } else {
                renderAddButton();
            }
            return;
        }

        const decrementIcon = qty === 1 ? 'trash.svg' : 'minus.svg';
        const decrementLabel = qty === 1 ? 'Remove item' : 'Decrease quantity';

        $control.empty().append(
            $('<div class="Product_Card_Qty"></div>').append(
                $('<button class="Qty_Decrement_Btn" type="button"></button>')
                    .attr('aria-label', decrementLabel)
                    .append($('<img alt="">').attr('src', '/HeyDaniel/Assets/Icons/' + decrementIcon))
                    .on('click', function () {
                        decrementProduct(productId, function (data) {
                            renderQty(data.quantity);
                        });
                    }),
                $('<span class="Product_Card_Qty_Value"></span>').text(qty),
                $('<button class="Qty_Btn" type="button" aria-label="Increase quantity"></button>')
                    .append($('<img src="/HeyDaniel/Assets/Icons/plus.svg" alt="">'))
                    .on('click', function () {
                        addProduct(productId, function (data) {
                            renderQty(data.quantity);
                        });
                    })
            )
        );
    }

    if (inCart && quantity > 0) {
        renderQty(quantity);
    } else {
        renderAddButton();
    }

    return $control;
}

// Same add/decrement behavior as buildCartControl(), but sized to match the
// compact circular "+" button used in the search results dropdown.
function buildSearchResultCartControl(productId, inCart, quantity) {
    const $control = $('<div class="Search_Result_Cart_Control"></div>');

    function renderAddButton() {
        $control.empty().append(
            $('<button class="Search_Result_Add_Btn Qty_Btn Qty_Btn--sm" type="button"></button>')
                .attr('aria-label', getHasActiveOrder() ? 'Add to order' : 'Add to cart')
                .append($('<img src="/HeyDaniel/Assets/Icons/plus.svg" alt="">'))
                .on('click', function (e) {
                    e.stopPropagation();
                    addProduct(productId, function (data) {
                        renderQty(data.quantity);
                    });
                })
        );
    }

    function renderQty(qty) {
        if (!qty) {
            renderAddButton();
            return;
        }

        const decrementIcon = qty === 1 ? 'trash.svg' : 'minus.svg';
        const decrementLabel = qty === 1 ? 'Remove item' : 'Decrease quantity';

        $control.empty().append(
            $('<button class="Search_Result_Qty_Btn Qty_Btn Qty_Btn--sm" type="button"></button>')
                .attr('aria-label', decrementLabel)
                .append($('<img alt="">').attr('src', '/HeyDaniel/Assets/Icons/' + decrementIcon))
                .on('click', function (e) {
                    e.stopPropagation();
                    decrementProduct(productId, function (data) {
                        renderQty(data.quantity);
                    });
                }),
            $('<span class="Search_Result_Qty_Value"></span>').text(qty),
            $('<button class="Search_Result_Qty_Btn Qty_Btn Qty_Btn--sm" type="button" aria-label="Increase quantity"></button>')
                .append($('<img src="/HeyDaniel/Assets/Icons/plus.svg" alt="">'))
                .on('click', function (e) {
                    e.stopPropagation();
                    addProduct(productId, function (data) {
                        renderQty(data.quantity);
                    });
                })
        );
    }

    if (inCart && quantity > 0) {
        renderQty(quantity);
    } else {
        renderAddButton();
    }

    return $control;
}

function buildProductCard(product, sameDayEligible, options) {
    options = options || {};

    const productId = product.product_id;
    const brand = product.brand;
    const name = product.name;
    const oz = product.oz;
    const price = product.price;
    const picture = product.picture;
    const isOnSale = product.is_on_sale;
    const salePrice = product.sale_price;
    const isBogo = product.is_bogo;
    const rating = product.rating;
    const reviewCount = product.review_count;
    const isSaved = product.is_saved;
    // A product in an active order lives in Process, not Cart, so in_cart
    // alone is always false for it — in_process has to count too, or the
    // card falls back to the Add button instead of the +/- stepper.
    const inCart = product.in_cart || product.in_process;
    const quantity = product.quantity;

    const nowPrice = isOnSale ? salePrice : price;
    const wasPrice = isOnSale ? price : null;
    const hasRating = typeof rating === "number";

    const badgeHTML = isBogo
        ? '<span class="Product_Card_Badge Bogo">BOGO</span>'
        : (isOnSale ? '<span class="Product_Card_Badge">Sale</span>' : '');

    const ratingHTML = hasRating
        ? renderProductStars(rating) +
          '<span class="Product_Card_Rating_Value">' + rating + '</span>' +
          '<span class="Product_Card_Rating_Count">(' + reviewCount + ')</span>'
        : renderProductStars(0) +
          '<span class="Product_Card_Rating_Count">' + rating + '</span>';

    let $card;

    const $wishlistBtn = $('<button class="Product_Card_Wishlist" type="button"></button>')
        .toggleClass('Active', !!isSaved)
        .attr("aria-label", (isSaved ? "Remove " : "Save ") + name + (isSaved ? " from wishlist" : " to wishlist"))
        .append($('<img src="/HeyDaniel/Assets/Icons/heart.svg" alt="">'))
        .on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);
            savedAdd(productId, function (data) {
                $btn.toggleClass('Active', !!data.is_saved);
                $btn.attr("aria-label", (data.is_saved ? "Remove " : "Save ") + name + (data.is_saved ? " from wishlist" : " to wishlist"));

                if (typeof options.onWishlistToggle === "function") {
                    options.onWishlistToggle(data, $card);
                }
            });
        });

    const lineTotal = typeof product.total_price === "number" ? product.total_price : (nowPrice * (quantity || 1));

    const itemUrl = "/HeyDaniel/Interface/Sheets/Item?id=" + productId;

    if (options.layout === "row") {
        $card = $('<div class="Row_Card"></div>').attr('data-product-id', productId).append(
            $('<a class="Row_Card_Image_Wrap"></a>')
                .attr("href", itemUrl)
                .css("background-image", "url(" + picture + ")")
                .append(
                    badgeHTML,
                    options.showWishlistBtn ? $wishlistBtn : null
                ),
            $('<div class="Row_Card_Info"></div>').append(
                $('<p class="Product_Card_Brand"></p>').text(brand),
                $('<h3 class="Row_Card_Title"></h3>').append($('<a></a>').attr("href", itemUrl).text(name)),
                $('<p class="Product_Card_Rating"></p>').html(ratingHTML),
                $('<div class="Row_Card_Meta"></div>').append(
                    oz ? $('<span class="Product_Card_Size"></span>').text(oz) : null,
                    product.same_day_eligible
                        ? $('<span class="Product_Card_Delivery"></span>').text('Same-day eligible')
                        : $('<span class="Product_Card_Delivery Standard"></span>').text('Fast delivery')
                )
            ),
            $('<div class="Row_Card_Price"></div>').append(
                $('<span class="Product_Card_Price_Now"></span>').text('$' + nowPrice),
                wasPrice ? $('<span class="Product_Card_Price_Was"></span>').text('$' + wasPrice) : null,
                options.showLineTotal ? $('<p class="Product_Card_Line_Total"></p>').text('Total: $' + lineTotal.toFixed(2)) : null
            ),
            $('<div class="Row_Card_Actions"></div>').append(
                buildCartControl(productId, inCart, quantity, options.onQuantityEmpty ? function () {
                    options.onQuantityEmpty($card);
                } : null)
            )
        );

        return $card;
    }

    $card = $('<div class="Col Col_3"></div>').attr('data-product-id', productId).append(
        $('<div class="Product_Card"></div>').append(
            $('<a class="Product_Card_Image_Wrap"></a>')
                .attr("href", itemUrl)
                .css("background-image", "url(" + picture + ")")
                .append(
                    badgeHTML,
                    $wishlistBtn
                ),
            $('<div class="Product_Card_Content"></div>').append(
                $('<p class="Product_Card_Brand"></p>').text(brand),
                $('<h3 class="Product_Card_Title"></h3>').append($('<a></a>').attr("href", itemUrl).text(name)),
                $('<p class="Product_Card_Rating"></p>').html(ratingHTML),
                $('<p class="Product_Card_Price"></p>').append(
                    $('<span class="Product_Card_Price_Now"></span>').text('$' + nowPrice),
                    wasPrice ? $('<span class="Product_Card_Price_Was"></span>').text('$' + wasPrice) : null
                ),
                $('<div class="Product_Card_Meta"></div>').append(
                    oz ? $('<span class="Product_Card_Size"></span>').text(oz) : null,
                    sameDayEligible ? $('<span class="Product_Card_Delivery"></span>').text('Same-day eligible') : null
                ),
                options.showLineTotal ? $('<p class="Product_Card_Line_Total"></p>').text('Total: $' + lineTotal.toFixed(2)) : null,
                buildCartControl(productId, inCart, quantity, options.onQuantityEmpty ? function () {
                    options.onQuantityEmpty($card);
                } : null)
            )
        )
    );

    return $card;
}

// Box categories (On Sale/BOGO/What's New/Featured) are eligibility-aware
// server-side (homepageCollections() excludes Grocery/Frozen/Produce/Dairy
// when not same-day eligible), but were only ever rendered once at page
// load - a zip change to a non-eligible zip left stale, now-ineligible
// items showing until a full reload. This re-fetches and re-renders them,
// called from DeviceLog()'s success handler alongside the other
// zip-dependent refreshes (refreshProductSliders()/summary()/cartIcon()).
function refreshHomepageCollections() {
    if (!$(".main-sale-cat-container").length) {
        return;
    }

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "homepage_collections",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message);
            } else {
                renderHomepageCollections(data.collections || []);
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("refreshHomepageCollections failed:", res.error);
        }
    });
}

// Mirrors renderSaleCollections() in Interface/Sections/Main.php exactly
// (same classes/markup shape), so this re-render looks identical to what
// PHP produced at page load - just with fresh data.
function renderHomepageCollections(collections) {
    const $container = $(".main-sale-cat-container");
    if (!$container.length) {
        return;
    }

    $container.empty();

    collections.forEach(function (collection) {
        const shopMoreUrl = collection.filter
            ? "/HeyDaniel/Interface/Sheets/Store?" + collection.filter
            : "/HeyDaniel/Interface/Sheets/Store";

        const $grid = $('<div class="main-sale-cat-grid"></div>').attr("data-count", collection.items.length);

        collection.items.forEach(function (item) {
            const tileUrl = item.category
                ? "/HeyDaniel/Interface/Sheets/Store?category=" + encodeURIComponent(item.category)
                : "/HeyDaniel/Interface/Sheets/Store";

            $('<a class="main-sale-cat-tile"></a>')
                .attr("href", tileUrl)
                .css("background-color", collection.bg)
                .append($('<img loading="lazy">').attr("src", item.image).attr("alt", item.alt))
                .appendTo($grid);
        });

        $('<div class="main-sale-cat"></div>')
            .append(
                $('<h2 class="main-sale-cat-title"></h2>').text(collection.title),
                $grid,
                $('<a class="main-sale-cat-link"></a>').attr("href", shopMoreUrl).text("Shop more")
            )
            .appendTo($container);
    });
}

function refreshProductSliders() {
    $(".Product_Slider_Section[data-table]").each(function () {
        const $section = $(this);
        const table = $section.data("table");
        const $slider = $section.find(".product-slider");

        if (table && $slider.length) {
            pullingProducts(table, $section.attr("id"), $slider.attr("id"));
        }
    });
}

function pullingProducts(table, sectionId, sliderId) {
    const $section = $("#" + sectionId);
    const $slider = $("#" + sliderId);

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            table: table,
            action: "pulling_products",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message || !data.products || !data.products.length) {
                $section.hide();
                return;
            }

            $slider.empty();

            const sameDayEligible = data.same_day_eligible;

            data.products.forEach(function (product) {
                $slider.append(buildProductCard(product, sameDayEligible));
            });

            $section.show();
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("pullingProducts failed:", res.error);
        }
    });
}

function recentlyViewed() {
    const $section = $("#Recently_Viewed_Section");
    const $slider = $("#Recently_Viewed_Slider");

    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "recently_viewed",
            device_type: "Web"
        }),
        success: function (data) {
            if (data.message || !data.products || !data.products.length) {
                $section.hide();
                return;
            }

            $slider.empty();
            data.products.forEach(function (product) {
                $slider.append(buildProductCard(product, false));
            });
            $section.show();
        },
        error: function () {
            $section.hide();
        }
    });
}

function itemPush(product_id, table) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "item_push",
            device_type: "Web",
            product_id: product_id,
            table: table
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("itemPush failed:", res.error);
        }
    });
}

// App-wide replacement for native confirm()/alert() (Footer.php has the
// modal markup, loaded on every page). Native confirm() is synchronous and
// returns a boolean immediately; a styled modal can't block like that, so
// callers pass the "what happens if confirmed" code as a callback instead
// of wrapping it in `if (confirm(...))`.
function showAppConfirm(message, onConfirm, options) {
    options = options || {};
    $("#app-confirm-title").text(options.title || "Please confirm");
    $("#app-confirm-message").text(message);
    $("#app-confirm-ok-label").text(options.confirmLabel || "Yes, continue");
    $("#app-confirm-cancel-label").text(options.cancelLabel || "No, go back");
    $("#app-confirm-icon").attr("class", options.icon || "fas fa-question");

    if (options.badge) {
        $("#app-confirm-badge").text(options.badge).show();
    } else {
        $("#app-confirm-badge").hide();
    }

    if (options.note === false) {
        $("#app-confirm-note").hide();
    } else {
        $("#app-confirm-note-text").text(typeof options.note === "string" ? options.note : "This action cannot be undone");
        $("#app-confirm-note").show();
    }

    $("#app-confirm-ok").off("click").on("click", function () {
        hideAppConfirm();
        onConfirm();
    });
    $("#app-confirm-modal").show();
}

function hideAppConfirm() {
    $("#app-confirm-modal").hide();
}

function showAppAlert(message, options) {
    options = options || {};
    $("#app-alert-title").text(options.title || "Notice");
    $("#app-alert-message").text(message);
    $("#app-alert-modal").show();
}

function hideAppAlert() {
    $("#app-alert-modal").hide();
}

$(document).ready(function () {
    $("#app-confirm-close-x, #app-confirm-cancel").on("click", hideAppConfirm);
    $("#app-alert-close-x, #app-alert-ok").on("click", hideAppAlert);

    // click on the dark backdrop (not the content box) dismisses either modal
    $("#app-confirm-modal, #app-alert-modal").on("click", function (e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
});
