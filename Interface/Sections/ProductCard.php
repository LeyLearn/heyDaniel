<?php

function renderProductStars($rating)
{
    $filled = round((float) $rating);
    $stars = '';
    for ($i = 1; $i <= 5; $i++) {
        $stars .= $i <= $filled
            ? '<span class="Star_Filled">&#9733;</span>'
            : '<span class="Star_Empty">&#9733;</span>';
    }
    return $stars;
}

function renderProductCard($product, $path)
{
    $hasSale = !empty($product['was_price']);
?>
    <div class="Product_Card">
        <div class="Product_Card_Image_Wrap" style="background-image: url(<?php echo $path ?>Assets/Build/<?php echo $product['image'] ?>)">
            <?php if (!empty($product['badge'])) { ?>
                <span class="Product_Card_Badge<?php echo $product['badge'] === 'BOGO' ? ' Bogo' : ''; ?>"><?php echo htmlspecialchars($product['badge']) ?></span>
            <?php } ?>
            <button class="Product_Card_Wishlist" type="button" aria-label="Save <?php echo htmlspecialchars($product['name']) ?> to wishlist">
                <img src="<?php echo $path ?>Assets/Icons/heart.svg" alt="" />
            </button>
        </div>
        <div class="Product_Card_Content">
            <p class="Product_Card_Brand"><?php echo htmlspecialchars($product['brand']) ?></p>
            <h3 class="Product_Card_Title"><?php echo htmlspecialchars($product['name']) ?></h3>
            <p class="Product_Card_Rating">
                <?php echo renderProductStars($product['rating']) ?>
                <span class="Product_Card_Rating_Value"><?php echo $product['rating'] ?></span>
                <span class="Product_Card_Rating_Count">(<?php echo $product['reviews'] ?>)</span>
            </p>
            <p class="Product_Card_Price">
                <span class="Product_Card_Price_Now">$<?php echo $product['price'] ?></span>
                <?php if ($hasSale) { ?>
                    <span class="Product_Card_Price_Was">$<?php echo $product['was_price'] ?></span>
                <?php } ?>
            </p>
            <div class="Product_Card_Meta">
                <?php if (!empty($product['size'])) { ?>
                    <span class="Product_Card_Size"><?php echo htmlspecialchars($product['size']) ?></span>
                <?php } ?>
                <?php if (!empty($product['same_day'])) { ?>
                    <span class="Product_Card_Delivery">Same-day eligible</span>
                <?php } ?>
            </div>
            <button class="Add_To_Cart_Btn" type="button">Add to cart</button>
        </div>
    </div>
<?php
}
