
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
        window.location.href = "/HeyDaniel/Interface/Sheets/Profile.php";
    });

    $(".nav-wishlist").on("click", function () {
        window.location.href = "/HeyDaniel/Interface/Sheets/Saved.php";
    });

    $(".nav-cart").on("click", function () {
        window.location.href = "/HeyDaniel/Interface/Sheets/Cart.php";
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



    // showing register
    $(document).on("click", "p .show-login", function (e) {
        e.preventDefault();
        $(".credential-register").fadeOut(200, function () {
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
});
