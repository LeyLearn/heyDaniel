$(document).ready(function(){

DeviceCheck();

const searchInput = $('.search');
const searchBtn = $('#search-btn');
const searchIcon = $('.search-icon');
const closeIcon = $('.close-icon');

searchInput.keyup(function () {
    if ($(this).val().length > 0) {
        searchIcon.css('opacity', '0');
        closeIcon.css('opacity', '1');
        closeIcon.css('pointer-events', 'auto');
    } else {
        searchIcon.css('opacity', '1');
        closeIcon.css('opacity', '0');
        closeIcon.css('pointer-events', 'none');
    }
})

searchBtn.click(function () {
    searchInput.val("");
    searchIcon.css('opacity', '1');
    closeIcon.css('opacity', '0');
    closeIcon.css('pointer-events', 'none');
    searchInput.focus();

})

// Navigation buttons
$('.nav-account').click(function() {
    window.location.href = '/HeyDaniel/Interface/Sheets/Profile.php';
});

$('.nav-wishlist').click(function() {
    savedItems();
    // window.location.href = '/HeyDaniel/Interface/Sheets/Saved.php';
});

$('.nav-cart').click(function() {
    window.location.href = '/HeyDaniel/Interface/Sheets/Cart.php';
});

})