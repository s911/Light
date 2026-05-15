<?php
/*
Template Name: Wishlist
*/
get_header();

if (!function_exists('wc_get_products')) {
    ?>
    <section class="section">
        <div class="container"><p>WooCommerce is required for wishlist feature.</p></div>
    </section>
    <?php
    get_footer();
    return;
}

$ids = array();
if (is_user_logged_in() && function_exists('stage_lighting_get_user_wishlist_ids')) {
    $ids = stage_lighting_get_user_wishlist_ids();
}
if (empty($ids) && function_exists('stage_lighting_get_wishlist_cookie_ids')) {
    $ids = stage_lighting_get_wishlist_cookie_ids();
}
$ids = array_slice(array_unique(array_map('intval', $ids)), 0, 100);

$products = array();
if (!empty($ids)) {
    $products = wc_get_products(
        array(
            'include' => $ids,
            'limit'   => 100,
            'status'  => 'publish',
        )
    );
}
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">User Feature</p>
        <h1>Wishlist</h1>
        <?php if (empty($products)) : ?>
            <div class="card">
                <p>No wishlist products yet. Add products from listing or product details.</p>
            </div>
        <?php else : ?>
            <div class="compare-actions">
                <a class="btn btn-outline" href="<?php echo esc_url(stage_lighting_get_products_page_url()); ?>">Back to Products</a>
                <button class="btn btn-outline" type="button" id="stage-clear-wishlist">Clear Wishlist</button>
            </div>
            <div class="grid grid-3">
                <?php foreach ($products as $p) : ?>
                    <article class="card product-card">
                        <div>
                            <h3><a href="<?php echo esc_url(get_permalink($p->get_id())); ?>"><?php echo esc_html($p->get_name()); ?></a></h3>
                            <p><?php echo esc_html(wp_trim_words(get_post_field('post_excerpt', $p->get_id()), 18)); ?></p>
                            <p class="product-price"><?php echo wp_kses_post($p->get_price_html()); ?></p>
                        </div>
                        <div class="cta-row">
                            <a class="btn btn-outline" href="<?php echo esc_url(get_permalink($p->get_id())); ?>">View Product</a>
                            <a class="btn btn-outline" href="#" data-wishlist-toggle="1" data-product-id="<?php echo esc_attr((string) $p->get_id()); ?>">Remove Wishlist</a>
                            <a class="btn btn-gradient" href="<?php echo esc_url(home_url('/for-business/?product=' . rawurlencode($p->get_name()))); ?>">Request Quote</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
            <script>
            document.addEventListener("DOMContentLoaded", function () {
                var clearBtn = document.getElementById("stage-clear-wishlist");
                if (!clearBtn) {
                    return;
                }
                clearBtn.addEventListener("click", function () {
                    document.cookie = "stage_wishlist_ids=; path=/; max-age=0";
                    window.location.reload();
                });
            });
            </script>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
?>
