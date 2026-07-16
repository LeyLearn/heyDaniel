<?php
$path = "../../";
$pageTitle = "Product Card Sample - HeyDaniel";
$metaDescription = "Internal preview of the product card component.";
?>
<!DOCTYPE html>
<html lang="en">
<?php
include_once $path . "Interface/Sections/Head.php";
include_once $path . "Interface/Sections/ProductCard.php";

$sampleProducts = [
    [
        'image'     => 'Frozen.png',
        'brand'     => "Birds Eye",
        'name'      => 'Steamfresh Cheesy Broccoli',
        'size'      => '10.8 oz',
        'rating'    => '4.6',
        'reviews'   => '890',
        'price'     => '2.79',
        'was_price' => '3.49',
        'badge'     => 'Sale',
        'same_day'  => true,
    ],
    [
        'image'    => 'iphone.png',
        'brand'    => 'Apple',
        'name'     => 'iPhone 15 Pro, 128GB',
        'size'     => '128GB',
        'rating'   => '4.9',
        'reviews'  => '1.2k',
        'price'    => '999.00',
        'badge'    => null,
        'same_day' => false,
    ],
    [
        'image'    => 'Windex.png',
        'brand'    => 'Windex',
        'name'     => 'Glass Cleaner, 23oz',
        'size'     => '23 oz',
        'rating'   => '4.7',
        'reviews'  => '412',
        'price'    => '4.29',
        'badge'    => 'BOGO',
        'same_day' => true,
    ],
    [
        'image'    => 'razor.png',
        'brand'    => "Gillette Venus",
        'name'     => 'Comfortglide Razor, 2ct',
        'size'     => '2 ct',
        'rating'   => '4.5',
        'reviews'  => '203',
        'price'    => '9.99',
        'badge'    => null,
        'same_day' => true,
    ],
    [
        'image'    => 'Microwave.png',
        'brand'    => 'Insignia',
        'name'     => 'Countertop Microwave, 0.9 cu ft',
        'size'     => '0.9 cu ft',
        'rating'   => '4.4',
        'reviews'  => '156',
        'price'    => '69.99',
        'badge'    => null,
        'same_day' => false,
    ],
    [
        'image'    => 'parsley.png',
        'brand'    => 'Fresh Farms',
        'name'     => 'Organic Parsley Bunch',
        'size'     => '1 bunch',
        'rating'   => '4.8',
        'reviews'  => '64',
        'price'    => '1.99',
        'badge'    => null,
        'same_day' => true,
    ],
];
?>
<body>
    <?php include_once $path . "Interface/Sections/Header.php"; ?>

    <section class="Section Container">
        <div class="Section_Header">
            <h2>Product card sample</h2>
        </div>
        <div class="Row">
            <?php foreach ($sampleProducts as $product) { ?>
                <div class="Col Col_3">
                    <?php renderProductCard($product, $path); ?>
                </div>
            <?php } ?>
        </div>
    </section>

    <?php include_once $path . "Interface/Sections/Footer.php"; ?>
</body>
</html>
