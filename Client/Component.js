
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
function requireLogin() {
    if (typeof isLoggedIn !== "undefined" && !isLoggedIn) {
        window.location.href = "/HeyDaniel/Interface/Sheets/Credential.php";
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

                        const bannerHTML = data.same_day_eligible
                            ? `<h1>Premium same-day delivery on everything</h1><p>The best way to order. The fastest way to deliver.</p>`
                            : `<h1>Everything you need, all in one place</h1><p>The simplest way to order. Built for speed.</p>`;

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

function DeviceLog(zipcode, clicked, confirmRemovePerishables) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "device_log",
            device_type: "Web",
            zipcode: zipcode,
            confirm_remove_perishables: !!confirmRemovePerishables
        }),
        success: function (data) {
            if (data.message) {
                console.log(data.message)
            } else if (data.requires_confirmation) {
                showZipConflictModal(zipcode, clicked, data.perishable_items);
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
                summary();
                cartIcon();

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
                $(".nav-cart span").text(totalCount > 0 ? totalCount : "Cart");
                $(".nav-cart").toggleClass("Has_Items", totalCount > 0);
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("cartIcon failed:", res.error);
        }
    });
}

function cartItem() {
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
            const $container = $("#cart-items-container");
            $container.empty();

            if (data.message || !data.cart_items || !data.cart_items.length) {
                $("#cart-empty-message").show();
                $("#cart-summary").hide();
                $("#cart-item-count").text("0 items");
                return;
            }

            $("#cart-empty-message").hide();
            $("#cart-summary").show();

            data.cart_items.forEach(function (item) {
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
                    total_price: item.total_price
                };

                const $card = buildProductCard(product, false, {
                    layout: "row",
                    showLineTotal: true,
                    onQuantityEmpty: function ($itemCard) {
                        $itemCard.fadeOut(200, function () {
                            $itemCard.remove();
                            if (!$container.find(".Row_Card").length) {
                                $("#cart-empty-message").show();
                                $("#cart-summary").hide();
                            }
                        });
                    }
                });

                $container.append($card);
            });

            $("#cart-item-count").text(data.cart_items.length + (data.cart_items.length === 1 ? " item" : " items"));
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("cartItem failed:", res.error);
        }
    });
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
                location.reload();
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
                $(".nav-wishlist span").text(data.saved_count > 0 ? data.saved_count : "Favorites");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("savedCount failed:", res.error);
        }
    });
}

function savedItems() {
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
            const $container = $('#saved-items-container');
            $container.empty();

            if (data.message || !data.saved_items || !data.saved_items.length) {
                $('#saved-message').text(data.message || "You haven't saved any items yet.").show();
                return;
            }

            $('#saved-message').hide();

            data.saved_items.forEach(function (item) {
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
                    layout: "row",
                    showWishlistBtn: true,
                    onWishlistToggle: function (toggleData, $itemCard) {
                        if (!toggleData.is_saved) {
                            $itemCard.fadeOut(200, function () {
                                $itemCard.remove();
                                if (!$container.find(".Row_Card").length) {
                                    $('#saved-message').text("You haven't saved any items yet.").show();
                                    $('#saved-move-all-btn').hide();
                                }
                            });
                        }
                    }
                });

                $container.append($card);
            });

            $('#saved-item-count').text(data.saved_items.length + (data.saved_items.length === 1 ? " item" : " items"));
            $('#saved-move-all-btn').show();

            savedCount();
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("savedItems failed:", res.error);
        }
    });
}

// Loops the single-item add-to-cart endpoint since there is no bulk-add
// endpoint on the backend.
function moveAllSavedToCart() {
    if (!requireLogin()) {
        return;
    }

    const $btn = $('#saved-move-all-btn');
    const $rows = $('#saved-items-container .Row_Card').filter(function () {
        return $(this).find('.Add_To_Cart_Btn').length > 0;
    });

    if (!$rows.length) {
        return;
    }

    $btn.prop('disabled', true).text('Adding...');

    let remaining = $rows.length;

    $rows.each(function () {
        const productId = $(this).data('productId');
        addProduct(productId, function () {
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
                const subtotal = data.subtotal;
                $(".summary-order .order-info p").text('$' + Number(subtotal).toFixed(2));
                $(".cart-summary-subtotal").text('$' + Number(subtotal).toFixed(2));
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

    $grid.empty();

    if (!data.products || !data.products.length) {
        $grid.append('<p class="Store_Empty">No products found.</p>');
    } else {
        data.products.forEach(function (product) {
            $grid.append(buildProductCard(product, false));
        });
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

function filter(mainCategory, subCategory, thirdCategory, page, limit) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "filter",
            device_type: "Web",
            main_category: mainCategory,
            sub_category: subCategory,
            third_category: thirdCategory,
            page: page,
            limit: limit
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                data.products.forEach(function (item) {
                    const productId = item.product_id;
                    const brand = item.brand;
                    const name = item.name;
                    const oz = item.oz;
                    const price = item.price;
                    const picture = item.picture;
                    const isOnSale = item.is_on_sale;
                    const isBogo = item.is_bogo;
                    const ratings = item.ratings;
                    const reviewCount = item.review_count;
                    // mount html here
                });
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("filter failed:", res.error);
        }
    });
}

function mainCategories() {
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
                    $select.append($("<option></option>").val(category.name).text(category.name));
                });
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("mainCategories failed:", res.error);
        }
    });
}

function subCategories(mainCategory) {
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
                    $select.append($("<option></option>").val(category.name).text(category.name));
                });
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("subCategories failed:", res.error);
        }
    });
}

function thirdCategories(subCategory) {
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
                    $select.append($("<option></option>").val(category.name).text(category.name));
                });
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
                $results.append('<p class="Search_Result_Empty">No products found.</p>');
                $results.addClass("active");
                return;
            }

            data.products.forEach(function (product) {
                const productId = product.product_id;
                const brand = product.brand;
                const name = product.name;
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

                const $item = $('<div class="Search_Result_Item"></div>').append(
                    $('<div class="Search_Result_Image"></div>')
                        .css("background-image", "url(" + picture + ")")
                        .append(badgeHTML),
                    $('<div class="Search_Result_Info"></div>').append(
                        $('<p class="Search_Result_Brand"></p>').text(brand),
                        $('<p class="Search_Result_Name"></p>').text(name),
                        $('<p class="Search_Result_Price"></p>').append(
                            $('<span></span>').text('$' + nowPrice),
                            wasPrice ? $('<span class="Search_Result_Price_Was"></span>').text('$' + wasPrice) : null
                        )
                    ),
                    $('<button class="Search_Result_Add_Btn" type="button" aria-label="Add to cart"></button>')
                        .append($('<img src="/HeyDaniel/Assets/Icons/plus.svg" alt="">'))
                        .on('click', function () {
                            addProduct(productId);
                        })
                );

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
function checkout(address, paymentMethodId, tipAmount, onSuccess, onError) {
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
            tip_amount: tipAmount || 0
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

function finalizeOrder(orderId, tipAmount, onSuccess, onError) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "finalize_order",
            device_type: "Web",
            order_id: orderId,
            tip_amount: tipAmount || 0
        }),
        success: function (data) {
            if (data.success) {
                if (typeof onSuccess === "function") {
                    onSuccess();
                }
            } else if (typeof onError === "function") {
                onError(data.message || data.error || "Could not finalize the order.");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            if (typeof onError === "function") {
                onError(res.message || res.error || "Could not finalize the order.");
            }
        }
    });
}

// password reset
function collectEmail(email) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "collect_email",
            user_email: email
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                // show verify code screen
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("collectEmail failed:", res.error);
        }
    });
}

function verifyCode(email, code) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "verify_code",
            user_email: email,
            code: code
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                // show change password screen
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("verifyCode failed:", res.error);
        }
    });
}

function changePassword(email, newPassword) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "change_password",
            user_email: email,
            new_password: newPassword
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                // redirect to login
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("changePassword failed:", res.error);
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

            const $wishlistBtn = $('<button class="Item_Wishlist_Btn" type="button"></button>')
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

            if (!data.orders || !data.orders.length) {
                $("#order-history-empty").show();
                return;
            }

            $("#order-history-empty").hide();

            data.orders.forEach(function (order) {
                const orderDate = new Date(order.date_added);
                const formattedDate = isNaN(orderDate)
                    ? order.date_added
                    : orderDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });

                const $row = $('<div class="Order_Row"></div>').append(
                    $('<div class="Order_Row_Icon"></div>').append($('<img src="/HeyDaniel/Assets/Icons/shopping-cart.svg" alt="">')),
                    $('<div class="Order_Row_Info"></div>').append(
                        $('<p class="Order_Row_Number"></p>').text('Order #' + order.order_id),
                        $('<p class="Order_Row_Meta"></p>').text(formattedDate + ' · ' + order.item_count + ' item(s)')
                    ),
                    $('<span class="Order_Row_Status"></span>').text(order.status),
                    $('<p class="Order_Row_Total"></p>').text('$' + order.total.toFixed(2))
                );

                $container.append($row);
            });
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("orderHistory failed:", res.error);
        }
    });
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
            $('<button class="Add_To_Cart_Btn" type="button">Add to cart</button>')
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
    const inCart = product.in_cart;
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

    const itemUrl = "/HeyDaniel/Interface/Sheets/Item.php?id=" + productId;

    if (options.layout === "row") {
        $card = $('<div class="Row_Card"></div>').attr('data-product-id', productId).append(
            $('<a class="Row_Card_Image_Wrap"></a>')
                .attr("href", itemUrl)
                .css("background-image", "url(" + picture + ")")
                .append(badgeHTML),
            $('<div class="Row_Card_Info"></div>').append(
                $('<p class="Product_Card_Brand"></p>').text(brand),
                $('<h3 class="Row_Card_Title"></h3>').append($('<a></a>').attr("href", itemUrl).text(name)),
                $('<div class="Row_Card_Meta"></div>').append(
                    oz ? $('<span class="Product_Card_Size"></span>').text(oz) : null,
                    sameDayEligible ? $('<span class="Product_Card_Delivery"></span>').text('Same-day eligible') : null
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
                } : null),
                options.showWishlistBtn ? $wishlistBtn : null
            )
        );

        return $card;
    }

    $card = $('<div class="Col Col_3"></div>').append(
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
            } else {
                console.log("Item logged successfully");
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("itemPush failed:", res.error);
        }
    });
}
