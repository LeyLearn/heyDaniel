
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
                $("p").text(data.message);
            }
            else {
                if (data.is_device_known === false) {
                    // prompt zipcode sheet
                }
                else {
                    console.log("Device is known :" + data.is_device_known)
                }
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("DeviceCheck failed:", res.error);
        }
    });
}

function DeviceLog(zipcode) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "device_log",
            device_type: "Web",
            zipcode: zipcode
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                console.log(data.same_day_eligible)
                console.log(data.tax_rate)
                console.log(data.city)
                console.log(data.state)
                console.log(data)
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("DeviceLog failed:", res.error);
        }
    });
}

// cart

function addProduct(product_id) {
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
                $("p").text(data.message);
            } else {
                const quantity = data.quantity;
                const totalCount = data.total_count;
                const subTotal = data.subtotal;

                if (data.table_source === "Cart") {
                    //   icon is going to change to cart
                    // every button is changing to add to cart
                    // button on product: - quantity +
                }
                else if (data.table_source === "Process") {
                    // icon is going to change to process
                    // every button is changing to add to order
                    // button on product: - quantity +
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
                $("p").text(data.message);
            } else {
                const totalCount = data.total_count;
                const icon = data.icon;
                const hasActiveOrder = data.has_active_order;

                console.log(totalCount)
                console.log(icon)
                console.log(hasActiveOrder)

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
            if (data.message) {
                $("p").text(data.message);
            } else {
                data.cart_items.forEach(function (item) {
                    const productId = item.product_id;
                    const brand = item.brand;
                    const name = item.name;
                    const oz = item.oz;
                    const price = item.price;
                    const picture = item.picture;
                    const isOnSale = item.is_on_sale;
                    const isBogo = item.is_bogo;
                    const isSaved = item.is_saved;
                    const quantity = item.quantity;
                    const ratings = item.ratings;
                    const reviewCount = item.review_count;
                    const totalPrice = item.total_price;

                    // mount html here
                });
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("cartItem failed:", res.error);
        }
    });
}

// users
function register(userName, userEmail, userPassword) {
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
                $("p").text(data.message);
            } else {
                console.log(data.success)
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("register failed:", res.error);
        }
    });
}

function login(userEmail, userPass) {
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
                $("p").text(data.message);
            } else {
                location.reload();
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("login failed:", res.error);
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
function decrementProduct(product_id) {
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
                $("p").text(data.message);
            } else {
                const quantity = data.quantity;
                const totalCount = data.total_count;
                const subTotal = data.subtotal;
                // update ui
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
                $("p").text(data.message);
            } else {
                // cart cleared, update ui
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
                $("p").text(data.message);
            } else {
                const count = data.count;
                // update ui
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

            if (data.message) {
                $('#saved-message').text(data.message);
                return;
            }

            const container = $('#saved-items-container');
            container.empty();

            data.saved_items.forEach(function (item) {
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

                const card = $('<div class="saved-item"></div>').append(
                    $('<img>').attr('src', picture).attr('alt', name),
                    $('<p class="saved-brand"></p>').text(brand),
                    $('<p class="saved-name"></p>').text(name),
                    oz ? $('<p class="saved-oz"></p>').text(oz + ' oz') : null,
                    $('<p class="saved-price"></p>').text('$' + price),
                    isOnSale ? $('<span class="badge-sale">Sale</span>') : null,
                    isBogo ? $('<span class="badge-bogo">BOGO</span>') : null,
                    $('<p class="saved-ratings"></p>').text('★ ' + ratings + ' (' + reviewCount + ')'),
                    $('<button class="btn-add-to-cart">Add to Cart</button>').on('click', function () {
                        addProduct(productId);
                    })
                );

                container.append(card);
            });
        },
        error: function () {}
    });
}

function savedAdd(product_id) {
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
                $("p").text(data.message);
            } else {
                const count = data.count;
                // update saved icon
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
                $("p").text(data.message);
            } else {
                const cartCount = data.cart_count;
                const savedCount = data.saved_count;
                const hasActiveOrder = data.has_active_order;
                // update header
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("summary failed:", res.error);
        }
    });
}

// store
function store(page, limit) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "store",
            device_type: "Web",
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
            console.log("store failed:", res.error);
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
                $("p").text(data.message);
            } else {
                data.categories.forEach(function (category) {
                    // mount html here
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
                $("p").text(data.message);
            } else {
                data.categories.forEach(function (category) {
                    // mount html here
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
                $("p").text(data.message);
            } else {
                data.categories.forEach(function (category) {
                    // mount html here
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
function search(query) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "search",
            device_type: "Web",
            query: query
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                data.results.forEach(function (item) {
                    const productId = item.product_id;
                    const brand = item.brand;
                    const name = item.name;
                    const oz = item.oz;
                    const price = item.price;
                    const picture = item.picture;
                    // mount html here
                });
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("search failed:", res.error);
        }
    });
}

// checkout
function checkout(firstName, lastName, streetAddress, city, state, zipcode, aptUnit) {
    $.ajax({
        method: "POST",
        url: "/HeyDaniel/Server/index.php",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "checkout",
            device_type: "Web",
            first_name: firstName,
            last_name: lastName,
            street_address: streetAddress,
            city: city,
            state: state,
            zipcode: zipcode,
            apt_unit: aptUnit
        }),
        success: function (data) {
            if (data.message) {
                $("p").text(data.message);
            } else {
                const orderId = data.order_id;
                const total = data.total;
                const tax = data.tax;
                const deliveryFee = data.delivery_fee;
                // proceed to payment
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("checkout failed:", res.error);
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
            if (data.message) {
                $("p").text(data.message);
            }
            else {
                const product = data.product;
                const productId = product.product_id;
                const brand = product.brand;
                const name = product.name;
                const oz = product.oz;
                const price = product.price;
                const picture = product.picture;
                const description = product.description;
                const isOnSale = product.is_on_sale;
                const salePrice = product.sale_price;
                const isBogo = product.is_bogo;
                const inStock = product.in_stock;
                const isSaved = product.is_saved;
                const inCart = product.in_cart;
                const quantity = product.quantity;
                const mainCategory = product.main_category;
                const subCategory = product.sub_category;
                const thirdCategory = product.third_category;
                const rating = product.rating;
                const reviewCount = product.review_count;

                // mount html here
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("productDetail failed:", res.error);
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
            if (data.message) {
                $("p").text(data.message);
            }
            else {
                data.orders.forEach(function (order) {
                    const historyId = order.history_id;
                    const productId = order.product_id;
                    const brand = order.brand;
                    const name = order.name;
                    const oz = order.oz;
                    const picture = order.picture;
                    const unitPrice = order.unit_price;
                    const quantity = order.quantity;
                    const totalPrice = order.total_price;
                    const isOnSale = order.is_on_sale;
                    const dateAdded = order.date_added;

                    // mount html here
                });
            }
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
            if (data.message) {
                $("p").text(data.message);
            }
            else {
                const totalCount = data.total_count;
                const avgRating = data.avg_rating;

                data.reviews.forEach(function (review) {
                    const reviewId = review.review_id;
                    const userName = review.user_name;
                    const stars = review.stars;
                    const expectation = review.expectation;
                    const title = review.title;
                    const reviewText = review.review;
                    const dateAdded = review.date_added;

                    // mount html here
                });
            }
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
            if (data.message) {
                $("p").text(data.message);
            } else {
                console.log("Review submitted successfully");
                // show success message
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("addReview failed:", res.error);
        }
    });
}

// recently viewed

function recentlyViewed() {
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
            if (data.message) {
                $("p").text(data.message);
            }
            else {
                data.products.forEach(function (product) {
                    const productId = product.product_id;
                    const brand = product.brand;
                    const name = product.name;
                    const oz = product.oz;
                    const price = product.price;
                    const picture = product.picture;
                    const isOnSale = product.is_on_sale;
                    const salePrice = product.sale_price;
                    const isBogo = product.is_bogo;
                    const isSaved = product.is_saved;
                    const rating = product.rating;
                    const reviewCount = product.review_count;
                    const dateViewed = product.date_viewed;

                    // mount html here
                });
            }
        },
        error: function (xhr) {
            const res = JSON.parse(xhr.responseText);
            console.log("recentlyViewed failed:", res.error);
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
