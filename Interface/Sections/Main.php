<?php
$mainCategories = [
    'Baby'        => 'Baby.png',
    'Beauty'      => 'Beauty.png',
    'Care'        => 'Care.png',
    'Electronics' => 'Electronics.png',
    'Groceries'   => 'Groceries.png',
    'Households'  => 'Households.png',
    'Kitchen'     => 'Kitchen.png',
    'Pets'        => 'Pets.png',
];

$saleCollections = [
    [
        'title' => 'Fresh picks',
        'bg'    => '#F3F3F3',
        'items' => [
            ['image' => 'parsley.png', 'alt' => 'Parsley'],
            ['image' => 'orange.png',  'alt' => 'Orange'],
            ['image' => 'Frozen.png',  'alt' => 'Frozen entree'],
            ['image' => 'scott.png',   'alt' => 'Scott paper towels'],
        ],
    ],
    [
        'title' => 'Everyday essentials',
        'bg'    => '#f2f3f2',
        'items' => [
            ['image' => 'toothpaste.JPG', 'alt' => 'Toothpaste'],
            ['image' => 'selfcare.JPG',   'alt' => 'Self care'],
            ['image' => 'razor.png',      'alt' => 'Razor'],
            ['image' => 'Windex.png',     'alt' => 'Windex'],
        ],
    ],
    [
        'title' => 'Tech deals',
        'bg'    => '#f0e517',
        'items' => [
            ['image' => 'iphone.png',  'alt' => 'iPhone'],
            ['image' => 'laptop.png',  'alt' => 'Laptop'],
            ['image' => 'ps52.png',    'alt' => 'PS5'],
            ['image' => 'Nitendo.png', 'alt' => 'Nintendo Switch'],
        ],
    ],
    [
        'title' => 'Home and office',
        'bg'    => '#0bb1f3',
        'items' => [
            ['image' => 'keyboard.png',  'alt' => 'Keyboard'],
            ['image' => 'sheet.png',     'alt' => 'Bed sheet'],
            ['image' => 'Microwave.png', 'alt' => 'Microwave'],
        ],
    ],
];

// Second row reuses the same photo pool under different groupings until more
// product images are added to Assets/Build/.
$saleCollectionsRow2 = [
    [
        'title' => 'Weekly grocery run',
        'bg'    => '#FBEAE3',
        'items' => [
            ['image' => 'orange.png',  'alt' => 'Orange'],
            ['image' => 'Frozen.png',  'alt' => 'Frozen entree'],
            ['image' => 'parsley.png', 'alt' => 'Parsley'],
            ['image' => 'Windex.png',  'alt' => 'Windex'],
        ],
    ],
    [
        'title' => 'Gadgets we love',
        'bg'    => '#EAF3FB',
        'items' => [
            ['image' => 'iphone.png',   'alt' => 'iPhone'],
            ['image' => 'laptop.png',   'alt' => 'Laptop'],
            ['image' => 'ps52.png',     'alt' => 'PS5'],
            ['image' => 'keyboard.png', 'alt' => 'Keyboard'],
        ],
    ],
    [
        'title' => 'Self care Sunday',
        'bg'    => '#FBEAF0',
        'items' => [
            ['image' => 'toothpaste.JPG', 'alt' => 'Toothpaste'],
            ['image' => 'selfcare.JPG',   'alt' => 'Self care'],
            ['image' => 'razor.png',      'alt' => 'Razor'],
            ['image' => 'sheet.png',      'alt' => 'Bed sheet'],
        ],
    ],
    [
        'title' => 'Around the house',
        'bg'    => '#EAF3E6',
        'items' => [
            ['image' => 'scott.png',     'alt' => 'Scott paper towels'],
            ['image' => 'Microwave.png', 'alt' => 'Microwave'],
            ['image' => 'Nitendo.png',   'alt' => 'Nintendo Switch'],
        ],
    ],
];

include_once $path . "Interface/Sections/ProductCard.php";

$popularProducts = [
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

$heroSlides = [
    [
        'tag'     => 'New in beauty',
        'heading' => 'Glow up with your <br> favorite beauty picks',
        'cta'     => 'Shop beauty',
        'image'   => 'HeroBeauty.png',
        'accent'  => '#cc8c81',
    ],
    [
        'tag'     => 'New arrivals',
        'heading' => 'Refresh your space with <br> home & living favorites',
        'cta'     => 'Shop home & living',
        'image'   => 'HeroHomeLiving.png',
        'accent'  => '#a0885c',
    ],
    [
        'tag'     => 'Deal of the day',
        'heading' => 'Top tech, <br> delivered same day',
        'cta'     => 'Shop electronics',
        'image'   => 'HeroElectronics.png',
        'accent'  => '#373588',
    ],
];

$exploreCategories = [
    'Furniture'   => 'Furniture.png',
    'Gaming'      => 'Gaming.png',
    'Fitness'     => 'Fitness.png',
    'Outdoors'    => 'Outdoors.png',
    'Kitchen'     => 'Kitchen.png',
    'Pets'        => 'Pets.png',
    'Garden'      => 'Garden.png',
    'Tools'       => 'Tools.png',
    'Office'      => 'Office.png',
    'Fashion'     => 'Fashion.png',
    'Accessories' => 'Accessories.png',
    'Shipping'    => 'Shipping.png',
];

function darkenHexColor($hex, $factor = 0.5)
{
    $hex = ltrim($hex, '#');
    $r = (int) round(hexdec(substr($hex, 0, 2)) * $factor);
    $g = (int) round(hexdec(substr($hex, 2, 2)) * $factor);
    $b = (int) round(hexdec(substr($hex, 4, 2)) * $factor);
    return sprintf('#%02x%02x%02x', $r, $g, $b);
}

function renderHeroSlider($slides, $path)
{
?>
    <div class="hero-slider">
        <button class="Hero_Arrow Hero_Arrow_Prev" type="button" aria-label="Previous slide">
            <img src="<?php echo $path ?>Assets/Icons/chevron-right.svg" alt="" />
        </button>
        <div class="hero-slider-viewport">
            <div class="hero-slider-track">
                <?php foreach ($slides as $slide) { ?>
                    <div class="hero-slide" data-accent="<?php echo htmlspecialchars($slide['accent']) ?>">
                        <div class="main-container-ad">
                            <p><?php echo htmlspecialchars($slide['tag']) ?></p>
                            <h1><?php echo $slide['heading'] ?></h1>
                            <button class="Main_Cta_Btn"><?php echo htmlspecialchars($slide['cta']) ?></button>
                        </div>
                        <div class="hero-slide-picture-wrap">
                            <div class="hero-slide-circle" style="background-color: <?php echo htmlspecialchars(darkenHexColor($slide['accent'], 0.45)) ?>"></div>
                            <div class="hero-slide-picture" style="background-image: url('<?php echo $path ?>Assets/Build/<?php echo $slide['image'] ?>')"></div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
        <button class="Hero_Arrow Hero_Arrow_Next" type="button" aria-label="Next slide">
            <img src="<?php echo $path ?>Assets/Icons/chevron-left.svg" alt="" />
        </button>
    </div>
    <?php
}

function renderSaleCollections($collections, $path)
{
    foreach ($collections as $collection) { ?>
        <div class="main-sale-cat">
            <h2 class="main-sale-cat-title"><?php echo $collection['title'] ?></h2>
            <div class="main-sale-cat-grid" data-count="<?php echo count($collection['items']) ?>">
                <?php foreach ($collection['items'] as $item) { ?>
                    <div class="main-sale-cat-tile" style="background-color: <?php echo $collection['bg'] ?>">
                        <img src="<?php echo $path ?>Assets/Build/<?php echo $item['image'] ?>" alt="<?php echo $item['alt'] ?>" loading="lazy" />
                    </div>
                <?php } ?>
            </div>
            <a class="main-sale-cat-link" href="/HeyDaniel/Interface/Sheets/Store.php">Shop more</a>
        </div>
    <?php }
}

function renderExploreCategories($categories, $path)
{
    ?>
    <div class="main-explore-cat">
        <h2>Explore More Categories</h2>
        <div class="main-explore-cat-grid">
            <?php foreach ($categories as $categoryName => $categoryImage) { ?>
                <a class="main-explore-cat-tile" href="/HeyDaniel/Interface/Sheets/Store.php" style="background-image: url('<?php echo $path ?>Assets/Categories/Explore/<?php echo $categoryImage ?>')">
                    <span><?php echo $categoryName ?></span>
                </a>
            <?php } ?>
        </div>
    </div>
<?php
}

function renderProductSlider($title, $table, $path)
{
    $sliderId = 'Product_Slider_' . $table;
?>
    <section class="Container Product_Slider_Section" id="<?php echo $sliderId ?>_Section" data-table="<?php echo htmlspecialchars($table) ?>" style="display:none;">
        <div class="Section_Header">
            <h2><?php echo htmlspecialchars($title) ?></h2>
        </div>
        <div class="Row">
            <div class="product-slider-wrap">
                <button class="Slider_Arrow Slider_Arrow_Prev" type="button" aria-label="Scroll left">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-right.svg" alt="" />
                </button>
                <div class="product-slider" id="<?php echo $sliderId ?>"></div>
                <button class="Slider_Arrow Slider_Arrow_Next" type="button" aria-label="Scroll right">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-left.svg" alt="" />
                </button>
            </div>
        </div>
    </section>
    <?php
}

function renderAuthPromo($userId, $featuredProduct, $path)
{
    if ($userId === 0) { ?>
        <section class="Container">
            <div class="auth-promo auth-promo-guest">
                <div class="auth-promo-copy">
                    <h2>Sign in for faster checkout</h2>
                    <p>Save your address and payment info, track orders, and unlock member-only deals.</p>
                    <div class="auth-promo-actions">
                        <a href="/HeyDaniel/Interface/Sheets/Credential.php" class="auth-promo-btn Primary">Sign in</a>
                        <a href="/HeyDaniel/Interface/Sheets/Credential.php" class="auth-promo-btn Outline">Create account</a>
                    </div>
                </div>
                <div class="auth-promo-scene">
                    <img src="<?php echo $path ?>Assets/Build/signin.png" alt="Shopping bag and delivery boxes" />
                </div>
            </div>
        </section>
    <?php } else { ?>
        <section class="Container">
            <div class="auth-promo auth-promo-ad">
                <div class="auth-promo-image" style="background-image: url('<?php echo $path ?>Assets/Build/<?php echo $featuredProduct['image'] ?>')"></div>
                <div class="auth-promo-copy">
                    <span class="auth-promo-tag">Deal of the day</span>
                    <h2><?php echo htmlspecialchars($featuredProduct['name']) ?></h2>
                    <p>Now $<?php echo $featuredProduct['price'] ?> <span class="auth-promo-was">$<?php echo $featuredProduct['was_price'] ?></span> &mdash; same-day delivery available in your area.</p>
                    <a href="/HeyDaniel/Interface/Sheets/Store.php" class="auth-promo-btn Primary">Shop now</a>
                </div>
            </div>
        </section>
<?php }
}
?>
<div class="main-container">
    <?php
    $mainBackgroundSvg = file_get_contents($path . "Assets/Build/Main.svg");
    $mainBackgroundSvg = preg_replace(
        '/(id="slide-color" stop-color=")[^"]*(")/',
        '${1}' . htmlspecialchars($heroSlides[0]['accent']) . '${2}',
        $mainBackgroundSvg
    );
    $mainBackgroundSvg = str_replace('<svg ', '<svg class="main-container-bg" preserveAspectRatio="xMidYMid slice" ', $mainBackgroundSvg);
    echo $mainBackgroundSvg;
    ?>
    <?php renderHeroSlider($heroSlides, $path); ?>
    <!-- sale collections -->
    <div class="main-sale-cat-container">
        <?php renderSaleCollections($saleCollections, $path); ?>
    </div>
</div>
<!-- category circles -->
<div class="main-circle-cat">
    <h2>Shop in Categories</h2>
    <?php foreach ($mainCategories as $categoryName => $categoryImage) { ?>
        <a class="main-circle-cat-item" href="/HeyDaniel/Interface/Sheets/Store.php">
            <div class="main-circle-cat-img" style="background-image: url('<?php echo $path ?>Assets/Categories/<?php echo $categoryImage ?>')"></div>
            <span><?php echo $categoryName ?></span>
        </a>
    <?php } ?>
</div>
<!-- recently viewed -->
<section class="Container" id="Recently_Viewed_Section" style="display:none;">
    <div class="Section_Header">
        <h2>Recently Viewed</h2>
    </div>
    <div class="Row">
        <div class="product-slider-wrap">
            <button class="Slider_Arrow Slider_Arrow_Prev" type="button" aria-label="Scroll left">
                <img src="<?php echo $path ?>Assets/Icons/chevron-right.svg" alt="" />
            </button>
            <div class="product-slider" id="Recently_Viewed_Slider"></div>
            <button class="Slider_Arrow Slider_Arrow_Next" type="button" aria-label="Scroll right">
                <img src="<?php echo $path ?>Assets/Icons/chevron-left.svg" alt="" />
            </button>
        </div>
    </div>
</section>
<!-- popular products -->
<?php renderProductSlider('Popular Products', 'Popular', $path); ?>
<!-- sign-in pitch for guests, product ad for signed-in users -->
<?php renderAuthPromo($userId, $popularProducts[0], $path); ?>
<!-- discover great deals -->
<?php renderProductSlider('Discover Great Deals', 'Recommendations', $path); ?>
<!-- second row of sale collections -->
<div class="main-sale-cat-container">
    <?php renderSaleCollections($saleCollectionsRow2, $path); ?>
</div>