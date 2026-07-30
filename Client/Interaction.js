
$(function () {

    // ============================
    // Cached Elements
    // ============================
    const searchInput = $(".search");
    const searchBtn = $("#search-btn");
    const searchIcon = $(".search-icon");
    const closeIcon = $(".close-icon");

    const sideBar = $("#side-bar");
    const zipInput = $(".zip-code-input");

    const googleButton = $(".google-button");

    // ============================
    // Helper Functions
    // ============================

    function showSearchIcon() {
        searchIcon.css({ opacity: 1 });

        closeIcon.css({
            opacity: 0,
            "pointer-events": "none"
        });
    }

    function showCloseIcon() {
        searchIcon.css({ opacity: 0 });

        closeIcon.css({
            opacity: 1,
            "pointer-events": "auto"
        });
    }

    function openSidebar() {
        sideBar.get(0).showModal();
        // showModal() traps focus and blocks interaction with the rest of
        // the page, but it doesn't stop the page from scrolling underneath
        // the dialog — that's still on us, so it's tied to the same
        // open/close lifecycle as everything else here.
        $("html, body").addClass("No_Scroll");
        requestAnimationFrame(function () {
            sideBar.addClass("Sidebar_Open");
        });
    }

    function closeSidebar() {
        sideBar.removeClass("Sidebar_Open");
    }

    // The dialog only leaves the top layer once its slide-out transition
    // finishes; native dismissal (Escape) skips straight to the "close"
    // event, so both paths are handled here to keep state in sync.
    sideBar.on("transitionend", function (e) {
        if (e.originalEvent.propertyName === "right" && !sideBar.hasClass("Sidebar_Open")) {
            this.close();
        }
    });

    // Clicking the backdrop is a click on the dialog element itself, outside
    // its own visible box, since ::backdrop isn't separately hit-testable.
    sideBar.on("click", function (e) {
        const rect = this.getBoundingClientRect();
        const clickedInsidePanel = e.clientX >= rect.left && e.clientX <= rect.right
            && e.clientY >= rect.top && e.clientY <= rect.bottom;

        if (!clickedInsidePanel) {
            closeSidebar();
        }
    });

    sideBar.on("close", function () {
        sideBar.removeClass("Sidebar_Open");
        $("html, body").removeClass("No_Scroll");
    });

    function validateZip(zip) {
        return /^\d{5}(-\d{4})?$/.test(zip);
    }

    function submitZip() {
        const zip = zipInput.val().trim();

        if (!validateZip(zip)) {
            alert("Please enter a valid ZIP code.");
            return;
        }

        DeviceLog(zip, true);
    }

    function detectLocation() {
        $.getJSON("https://ipapi.co/json/", function (data) {
            if (data.postal) {
                zipInput.val(data.postal);
                DeviceLog(data.postal, true);
            } else {
                alert("Unable to determine your ZIP code.");
            }
        });
    }

    function goToHeroSlide($track, direction) {
        const total = $track.find(".hero-slide").length;
        const current = $track.data("index") || 0;
        const next = direction === -1
            ? (current - 1 + total) % total
            : (current + 1) % total;

        $track.data("index", next);
        $track.css("transform", `translateX(-${next * 100}%)`);

        const accent = $track.find(".hero-slide").eq(next).data("accent");
        $(".main-container-bg #slide-color").attr("stop-color", accent);
    }

    function startHeroAutoSlide($track) {
        clearInterval($track.data("autoSlideTimer"));
        $track.data("autoSlideTimer", setInterval(function () {
            goToHeroSlide($track, 1);
        }, 30000));
    }

    // ============================
    // Initialize
    // ============================

    DeviceCheck();
    if ($("#Recently_Viewed_Slider").length) {
        recentlyViewed();
    }
    refreshProductSliders();
    summary();
    cartIcon();
    savedCount();
    showSearchIcon();
    if ($("#account-nav-notifications-badge").length) {
        notificationsBadge();
    }

    // ============================
    // Search Input
    // ============================

    const $searchResults = $("#search-results");
    let searchDebounceTimer = null;

    function closeSearchResults() {
        $searchResults.removeClass("active").empty();
    }

    searchInput.on("input", function () {
        const query = $(this).val().trim();

        $(this).val().length
            ? showCloseIcon()
            : showSearchIcon();

        clearTimeout(searchDebounceTimer);

        if (!query) {
            closeSearchResults();
            return;
        }

        searchDebounceTimer = setTimeout(function () {
            search(query);
        }, 300);
    });

    searchBtn.on("click", function () {
        searchInput.val("").focus();
        showSearchIcon();
        closeSearchResults();
    });

    $(document).on("click", function (e) {
        if (!$(e.target).closest(".search-wrap").length) {
            closeSearchResults();
        }
    });

    searchInput.on("keydown", function (e) {
        if (e.key === "Escape") {
            closeSearchResults();
        }
    });

    // ============================
    // Sidebar
    // ============================

    $(".summary-location").on("click", openSidebar);

    $(".close-icon-location").on("click", closeSidebar);

    $(".enable-location-btn").on("click", submitZip);

    $(".history").on("click", function () {
        zipInput.val($(this).find("p").text());
    });

    // ============================
    // Zip Conflict Modal
    // ============================

    $("#zip-conflict-cancel, #zip-conflict-close").on("click", hideZipConflictModal);

    $("#zip-conflict-continue").on("click", confirmZipChange);

    $("#zip-conflict-modal").on("click", function (e) {
        if (e.target === this) {
            hideZipConflictModal();
        }
    });

    // ============================
    // Navigation
    // ============================

    $(".nav-account").on("click", function () {
        window.location.href = "/HeyDaniel/Interface/Sheets/Profile";
    });

    $(".nav-wishlist").on("click", function () {
        window.location.href = "/HeyDaniel/Interface/Sheets/Saved";
    });

    $(".nav-cart").on("click", function () {
        window.location.href = "/HeyDaniel/Interface/Sheets/Cart";
    });

    // Cart.php already internally serves Process.php's content instead when
    // there's an active same-day order, so linking here to Cart is enough to
    // cover both cases - no client-side branching needed.
    $(".summary-order").on("click", function () {
        window.location.href = "/HeyDaniel/Interface/Sheets/Cart";
    });

    // ============================
    // Dark Mode Toggle
    // ============================
    // The actual theme is already applied before first paint by the inline
    // script in Head.php (avoids a flash of the wrong theme) - this just
    // syncs the button's icon/state to whatever that script decided, and
    // handles switching it afterward.

    function syncThemeToggleIcon() {
        const isDark = document.documentElement.getAttribute("data-theme") === "dark";
        $("#theme-toggle-icon").toggleClass("fa-moon", !isDark).toggleClass("fa-sun", isDark);
        $("#theme-toggle-btn").attr("aria-pressed", isDark ? "true" : "false");
    }

    syncThemeToggleIcon();

    $("#theme-toggle-btn").on("click", function () {
        const isDark = document.documentElement.getAttribute("data-theme") === "dark";
        if (isDark) {
            document.documentElement.removeAttribute("data-theme");
            localStorage.setItem("hd-theme", "light");
        } else {
            document.documentElement.setAttribute("data-theme", "dark");
            localStorage.setItem("hd-theme", "dark");
        }
        syncThemeToggleIcon();
    });

    // ============================
    // Detect User Location
    // ============================

    $(".search-btn").on("click", detectLocation);

    // ============================
    // Google Sign-In
    // ============================

    const googleOverlay = $("#google-signin-overlay");

    if (googleOverlay.length && window.google && window.google.accounts) {
        google.accounts.id.initialize({
            client_id: "763632369023-f94fto7ea5elt1pooikdfp6d11nb7tgc.apps.googleusercontent.com",
            callback: function (response) {
                googleLogin(response.credential);
            }
        });

        google.accounts.id.renderButton(googleOverlay[0], {
            type: "standard",
            theme: "outline",
            size: "large",
            width: googleButton.outerWidth()
        });
    }

    // ============================
    // regular Sign-In
    // ============================
    $(".login-bnt").click(function () {
        $(".credential-error").hide();
        const email = $(".login-email").val().trim();
        const password = $(".login-password").val().trim();
        login(email, password);
    })

    // ============================
    // regular register
    // ============================
    $(".register-bnt").click(function(){
        $(".credential-error").hide();
        const userName = $(".register-name").val().trim();
        const userEmail = $(".register-email").val().trim();
        const userPassword = $(".register-password").val().trim();
        register(userName, userEmail, userPassword);
    })

    // ============================
    // forgot password
    // ============================
    $(".forgot-send-code-bnt").click(function () {
        $(".credential-error").hide();
        const email = $(".forgot-email").val().trim();
        sendResetCode(email);
    })

    $(".forgot-reset-password-bnt").click(function () {
        $(".credential-error").hide();
        const email = $(".forgot-email").val().trim();
        const code = $(".forgot-code").val().trim();
        const newPassword = $(".forgot-new-password").val().trim();
        const confirmPassword = $(".forgot-confirm-password").val().trim();
        resetPassword(email, code, newPassword, confirmPassword);
    })



    // showing register
    $(document).on("click", "p .show-login", function (e) {
        e.preventDefault();
        $(".credential-register, .credential-forgot").fadeOut(200, function () {
            $(".switch-auth").html(`New to heyDaniel ? <a href="#" id="show-register" class="show-register">Create an account</a>`);
            $(".credential-login").fadeIn(200);
        });
    })
    // showing login
    $(document).on("click", "p .show-register", function (e) {
        e.preventDefault();
        $(".credential-login").fadeOut(200, function () {
            $(".switch-auth").html(`Already have an account ? <a href="#" id="show-login" class="show-login">Login</a>`);
            $(".credential-register").fadeIn(200);
        });
    })
    // showing forgot password
    $(document).on("click", "p .show-forgot", function (e) {
        e.preventDefault();
        $(".credential-login").fadeOut(200, function () {
            $(".credential-forgot").fadeIn(200);
        });
    })
    // back to login from forgot password
    $(document).on("click", "p .show-login-from-forgot", function (e) {
        e.preventDefault();
        $(".credential-forgot").fadeOut(200, function () {
            $(".forgot-step-reset").addClass("hidden").hide();
            $(".forgot-step-request").removeClass("hidden").show();
            $(".forgot-email, .forgot-code, .forgot-new-password, .forgot-confirm-password").val("");
            $(".credential-login").fadeIn(200);
        });
    })

    // ============================
    // Product Slider Arrows
    // ============================
    $(document).on("click", ".Slider_Arrow", function () {
        const $slider = $(this).closest(".product-slider-wrap").find(".product-slider");
        const $card = $slider.find(".Col").first();
        const scrollAmount = $card.outerWidth(true) * 2;
        const direction = $(this).hasClass("Slider_Arrow_Prev") ? -1 : 1;

        $slider.animate({
            scrollLeft: $slider.scrollLeft() + (direction * scrollAmount)
        }, 300);
    })

    // ============================
    // Hero Slider Arrows
    // ============================
    $(document).on("click", ".Hero_Arrow", function () {
        const $track = $(this).closest(".hero-slider").find(".hero-slider-track");
        const direction = $(this).hasClass("Hero_Arrow_Prev") ? -1 : 1;

        goToHeroSlide($track, direction);
        startHeroAutoSlide($track);
    })

    $(".hero-slider-track").each(function () {
        startHeroAutoSlide($(this));
    });

    // ============================
    // Coming Soon Stubs
    // ============================
    $(document).on("click", "[data-coming-soon]", function () {
        alert($(this).data("coming-soon") + " is coming soon.");
    });

    // ============================
    // HeyDaniel+ Membership Modal
    // (mounted once in Header.php, so any page can trigger it)
    // ============================
    if (typeof Stripe !== "undefined" && $("#membership-modal-overlay").length) {
        const membershipStripe = Stripe(stripePublishableKey);
        const membershipElements = membershipStripe.elements();
        const membershipCardStyle = {
            base: {
                fontSize: "14px",
                fontFamily: "inherit",
                color: "#333",
                "::placeholder": { color: "#9aa0ab" }
            }
        };
        const membershipCardNumberElement = membershipElements.create("cardNumber", { style: membershipCardStyle });
        const membershipCardExpiryElement = membershipElements.create("cardExpiry", { style: membershipCardStyle });
        const membershipCardCvcElement = membershipElements.create("cardCvc", { style: membershipCardStyle });
        let membershipCardMounted = false;
        let membershipCardFieldsVisible = false;
        let membershipSavedCardId = null;

        function revealMembershipCardFields() {
            if (!membershipCardMounted) {
                membershipCardNumberElement.mount("#membership-card-number");
                membershipCardExpiryElement.mount("#membership-card-expiry");
                membershipCardCvcElement.mount("#membership-card-cvc");
                membershipCardMounted = true;
            }
            $("#membership-card-fields").show();
            membershipCardFieldsVisible = true;
        }

        window.openMembershipModal = function () {
            $("#membership-modal-error").hide();
            $("#membership-modal-overlay").css("display", "flex");

            membershipCardFieldsVisible = false;
            $("#membership-card-fields").hide();

            $.ajax({
                method: "POST",
                url: "/HeyDaniel/Server/index.php",
                contentType: "application/json",
                dataType: "json",
                data: JSON.stringify({ action: "payment_methods", device_type: "Web" }),
                success: function (data) {
                    const cards = data.payment_methods || [];
                    if (!cards.length) {
                        revealMembershipCardFields();
                        return;
                    }

                    const card = cards[0];
                    membershipSavedCardId = card.id;

                    $("#membership-saved-card-icon").html(cardBrandIconHTML(card.brand));
                    $("#membership-saved-card-detail").text(
                        card.brand.charAt(0).toUpperCase() + card.brand.slice(1) +
                        " •••• " + card.last4 + " · Expires " + card.exp_month + "/" + card.exp_year
                    );

                    $("#membership-saved-card").show();
                    $("#membership-card-fields").hide();
                },
                error: function () {
                    // couldn't check for a saved card — fall back to letting them enter one
                    revealMembershipCardFields();
                }
            });
        };

        function closeMembershipModal() {
            $("#membership-modal-overlay").hide();
        }

        $(document).on("click", "[data-open-membership-modal]", function () {
            window.openMembershipModal();
        });

        $("#membership-modal-close, #membership-modal-cancel").on("click", closeMembershipModal);

        $("#membership-card-toggle").on("click", function () {
            const $suggestion = $("#membership-saved-card");
            const $badge = $suggestion.find(".Checkout_Selected_Badge");
            const usingSaved = $suggestion.hasClass("Selected");

            if (usingSaved) {
                $suggestion.removeClass("Selected");
                revealMembershipCardFields();
                $badge.hide();
                $("#membership-card-toggle-label").text("Select");
            } else {
                $suggestion.addClass("Selected");
                $("#membership-card-fields").hide();
                membershipCardFieldsVisible = false;
                $badge.show();
                $("#membership-card-toggle-label").text("Edit");
            }
        });

        $("#membership-modal-subscribe").on("click", function () {
            const usingSavedCard = membershipSavedCardId && $("#membership-saved-card").hasClass("Selected");

            if (!usingSavedCard && !membershipCardFieldsVisible) {
                revealMembershipCardFields();
                return;
            }

            const $btn = $(this);
            const $label = $("#membership-modal-subscribe-label");
            const $error = $("#membership-modal-error");
            $error.hide();
            $btn.prop("disabled", true);
            $label.text("Subscribing...");

            function finishSubscribe(paymentMethodId) {
                subscribeMembership(paymentMethodId, function () {
                    closeMembershipModal();
                    window.location.reload();
                }, function (message) {
                    $error.text(message).show();
                    $btn.prop("disabled", false);
                    $label.text("Subscribe for $9.99/mo");
                });
            }

            if (usingSavedCard) {
                finishSubscribe(membershipSavedCardId);
                return;
            }

            membershipStripe.createPaymentMethod({
                type: "card",
                card: membershipCardNumberElement,
                billing_details: {
                    address: { postal_code: $("#membership-card-zip").val() }
                }
            }).then(function (result) {
                if (result.error) {
                    $error.text(result.error.message).show();
                    $btn.prop("disabled", false);
                    $label.text("Subscribe for $9.99/mo");
                    return;
                }

                finishSubscribe(result.paymentMethod.id);
            });
        });
    }

    // ============================
    // Footer Newsletter Signup
    // ============================

    function submitNewsletterSignup() {
        const $email = $("#footer-newsletter-email");
        const $message = $("#footer-newsletter-message");
        const email = $email.val().trim();

        $message.hide().removeClass("Newsletter_Message_Success Newsletter_Message_Error");

        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            $message.addClass("Newsletter_Message_Error").text("Enter a valid email address.").show();
            return;
        }

        newsletterSubscribe(email, function (message) {
            $message.addClass("Newsletter_Message_Success").text(message).show();
            $email.val("");
        }, function (message) {
            $message.addClass("Newsletter_Message_Error").text(message).show();
        });
    }

    $("#footer-newsletter-submit").on("click", submitNewsletterSignup);

    $("#footer-newsletter-email").on("keydown", function (e) {
        if (e.key === "Enter") {
            submitNewsletterSignup();
        }
    });
});
