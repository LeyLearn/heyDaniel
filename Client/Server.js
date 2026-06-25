$(document).ready(function () {
    $(document).on("click", "button", function () {
        const buttonName = $(this).text().trim();
        if (buttonName === "devicelog") {
            DeviceLog("32256")
        }
        else if (buttonName === "add to cart") {
            addProduct(1)
        }
        else if (buttonName === "take from cart") {
            decrementProduct(1)
        }
        else if (buttonName === "clear cart") {
            clearCart()
        }
        else if (buttonName === "add to wishlist") {
            savedAdd(1)
        }
        else if (buttonName === "register") {
            register("Leni Devaldi", "Leydave02@gmail.com", "Leni1112@")
        }
        else if (buttonName === "log in") {
            login("Leydave02@gmail.com", "Leni1112@")
        }
        else if (buttonName === "log out") {
            logout()
        }
    })
});