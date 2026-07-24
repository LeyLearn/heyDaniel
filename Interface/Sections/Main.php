<?php
include_once __DIR__ . "/../../Server/Function/Components.php";

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
                            <a href="/HeyDaniel/Interface/Sheets/Store.php" class="Primary_Btn Primary_Btn--auto Main_Cta_Btn"><?php echo htmlspecialchars($slide['cta']) ?></a>
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

// Matches the .Skeleton_Product_Card markup Store.php uses for its grid, so
// the loading state looks consistent everywhere a product list can load.
function renderSkeletonProductCards($count)
{
    for ($i = 0; $i < $count; $i++) { ?>
        <div class="Skeleton_Product_Card">
            <div class="Skeleton_Block Skeleton_Product_Card_Image"></div>
            <div class="Skeleton_Product_Card_Content">
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
                <div class="Skeleton_Block"></div>
            </div>
        </div>
    <?php }
}

function renderProductSlider($title, $table, $path)
{
    $sliderId = 'Product_Slider_' . $table;
?>
    <section class="Container Product_Slider_Section" id="<?php echo $sliderId ?>_Section" data-table="<?php echo htmlspecialchars($table) ?>">
        <div class="Section_Header">
            <h2><?php echo htmlspecialchars($title) ?></h2>
        </div>
        <div class="Row">
            <div class="product-slider-wrap">
                <button class="Slider_Arrow Slider_Arrow_Prev" type="button" aria-label="Scroll left">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-right.svg" alt="" />
                </button>
                <div class="product-slider" id="<?php echo $sliderId ?>"><?php renderSkeletonProductCards(5); ?></div>
                <button class="Slider_Arrow Slider_Arrow_Next" type="button" aria-label="Scroll right">
                    <img src="<?php echo $path ?>Assets/Icons/chevron-left.svg" alt="" />
                </button>
            </div>
        </div>
    </section>
    <?php
}

function renderAuthPromo($userId, $isMember, $firstName, $monthlySavingsAmount, $path)
{
    if ($userId === 0) { ?>
        <section class="Container">
            <div class="auth-promo auth-promo-guest">
                <div class="auth-promo-copy">
                    <h2>Sign in for faster checkout</h2>
                    <p>Save your address and payment info, track orders, and unlock member-only deals.</p>
                    <div class="auth-promo-actions">
                        <a href="/HeyDaniel/Interface/Sheets/Credential.php" class="Primary_Btn Primary_Btn--auto auth-promo-btn Primary">Sign in</a>
                        <a href="/HeyDaniel/Interface/Sheets/Credential.php" class="Secondary_Light_Btn auth-promo-btn Outline">Create account</a>
                    </div>
                </div>
                <div class="auth-promo-scene">
                    <img src="<?php echo $path ?>Assets/Build/signin.png" alt="Shopping bag and delivery boxes" />
                </div>
            </div>
        </section>
    <?php } elseif ($isMember) { ?>
        <section class="Container">
            <div class="member-promo">
                <div class="member-promo-main">
                    <div class="member-promo-copy">
                        <span class="member-promo-badge">HeyDaniel+</span>
                        <h2 class="member-promo-heading">Welcome back, <span><?php echo htmlspecialchars($firstName) ?>!</span></h2>
                        <p class="member-promo-desc">Thanks for being a <strong>HeyDaniel+</strong> member.<br>You're saving more every time you shop.</p>
                        <div class="member-promo-rule"></div>
                        <div class="member-promo-features">
                            <div class="member-promo-feature">
                                <span class="member-promo-feature-icon Icon_Circle"><i class="fas fa-truck" aria-hidden="true"></i></span>
                                <p class="member-promo-feature-title">FREE DELIVERY</p>
                                <p class="member-promo-feature-sub">On all orders</p>
                            </div>
                            <div class="member-promo-divider"></div>
                            <div class="member-promo-feature">
                                <span class="member-promo-feature-icon Icon_Circle"><i class="fas fa-tags" aria-hidden="true"></i></span>
                                <p class="member-promo-feature-title">EXCLUSIVE PRICES</p>
                                <p class="member-promo-feature-sub">Members save more</p>
                            </div>
                            <div class="member-promo-divider"></div>
                            <div class="member-promo-feature">
                                <span class="member-promo-feature-icon Icon_Circle"><i class="fas fa-crown" aria-hidden="true"></i></span>
                                <p class="member-promo-feature-title">EARLY ACCESS</p>
                                <p class="member-promo-feature-sub">To new arrivals</p>
                            </div>
                            <div class="member-promo-divider"></div>
                            <div class="member-promo-feature">
                                <span class="member-promo-feature-icon Icon_Circle"><i class="fas fa-headset" aria-hidden="true"></i></span>
                                <p class="member-promo-feature-title">VIP SUPPORT</p>
                                <p class="member-promo-feature-sub">Priority assistance</p>
                            </div>
                        </div>
                        <div class="member-promo-cta-row">
                            <a href="/HeyDaniel/Interface/Sheets/Profile.php" class="Primary_Btn Primary_Btn--auto member-promo-cta">View Member Benefits <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                            <a href="/HeyDaniel/Interface/Sheets/Store.php" class="member-promo-shop-link">Shop Now <i class="fas fa-chevron-right" aria-hidden="true"></i></a>
                        </div>
                    </div>
                    <div class="member-promo-stats">
                        <p class="member-promo-saved-label">YOU'VE SAVED</p>
                        <p class="member-promo-saved-amount">$<?php echo number_format($monthlySavingsAmount, 2) ?></p>
                        <p class="member-promo-saved-sub">this month with HeyDaniel+</p>
                        <div class="member-promo-reward-card">
                            <span class="member-promo-reward-icon Icon_Circle"><i class="fas fa-gift" aria-hidden="true"></i></span>
                            <div class="member-promo-reward-text">
                                <p class="member-promo-reward-title">Your next reward is almost ready!</p>
                                <div class="member-promo-reward-bar"><div class="member-promo-reward-bar-fill"></div></div>
                                <p class="member-promo-reward-note">You're <strong>$18</strong> away from your next reward</p>
                            </div>
                        </div>
                    </div>
                    <div class="member-promo-image" style="background-image: url('<?php echo $path ?>Assets/Build/MembershipHero.PNG')"></div>
                </div>
                <div class="member-promo-footer">
                    <span class="member-promo-footer-icon"><i class="fas fa-shield-alt" aria-hidden="true"></i></span>
                    <div class="member-promo-footer-text">
                        <p class="member-promo-footer-title">Premium benefits. Exclusive savings. Just for you.</p>
                        <p class="member-promo-footer-sub">Thank you for being part of the HeyDaniel+ family.</p>
                    </div>
                    <p class="member-promo-tagline">You deserve the best.</p>
                </div>
            </div>
        </section>
    <?php } else { ?>
        <section class="Container">
            <div class="plus-promo">
                <div class="plus-promo-copy">
                    <span class="plus-promo-badge">HeyDaniel+</span>
                    <h2 class="plus-promo-heading">Subscribe &amp; <span>Save More</span></h2>
                    <p class="plus-promo-desc">Enjoy exclusive benefits, special offers, and early access &mdash; only for <strong>HeyDaniel+</strong> members.</p>
                    <div class="plus-promo-features">
                        <div class="plus-promo-feature">
                            <span class="plus-promo-feature-icon Icon_Circle"><i class="fas fa-truck" aria-hidden="true"></i></span>
                            <span>FREE &amp; FAST<br>DELIVERY</span>
                        </div>
                        <div class="plus-promo-divider"></div>
                        <div class="plus-promo-feature">
                            <span class="plus-promo-feature-icon Icon_Circle"><i class="fas fa-tags" aria-hidden="true"></i></span>
                            <span>EXCLUSIVE<br>DISCOUNTS</span>
                        </div>
                        <div class="plus-promo-divider"></div>
                        <div class="plus-promo-feature">
                            <span class="plus-promo-feature-icon Icon_Circle"><i class="fas fa-crown" aria-hidden="true"></i></span>
                            <span>MEMBER-ONLY<br>DEALS</span>
                        </div>
                        <div class="plus-promo-divider"></div>
                        <div class="plus-promo-feature">
                            <span class="plus-promo-feature-icon Icon_Circle"><i class="fas fa-gift" aria-hidden="true"></i></span>
                            <span>EARLY ACCESS<br>TO NEW ARRIVALS</span>
                        </div>
                    </div>
                    <div class="plus-promo-cta-row">
                        <button type="button" class="plus-promo-cta" data-open-membership-modal>Join HeyDaniel+ Today!</button>
                        <div class="plus-promo-divider plus-promo-divider--cta"></div>
                        <p class="plus-promo-tagline">More perks. More value.<br><strong>Just for you.</strong></p>
                    </div>
                </div>
                <div class="plus-promo-image" style="background-image: url('<?php echo $path ?>Assets/Build/MembershipHero.PNG')"></div>
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
<!-- sign-in pitch for guests, member welcome-back for members, join pitch for signed-in non-members -->
<?php
$memberMonthlySavings = ($userId !== 0 && $isMember) ? monthlySavings($pdo, $userId) : 0.00;
renderAuthPromo($userId, $isMember, $firstName, $memberMonthlySavings, $path);
?>
<!-- popular products -->
<?php renderProductSlider('Popular Products', 'Popular', $path); ?>
<!-- discover great deals -->
<?php renderProductSlider('Discover Great Deals', 'Recommendations', $path); ?>
<!-- second row of sale collections -->
<div class="main-sale-cat-container">
    <?php renderSaleCollections($saleCollectionsRow2, $path); ?>
</div>