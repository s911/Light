<?php
get_header();

if (!function_exists('wc_get_product')) {
    ?>
    <section class="section">
        <div class="container">
            <h1>Product</h1>
            <p>WooCommerce is required to view product details.</p>
        </div>
    </section>
    <?php
    get_footer();
    return;
}

while (have_posts()) :
    the_post();
    $product = wc_get_product(get_the_ID());
    if (!$product) {
        continue;
    }

    $quote_url = home_url('/for-business/?product=' . rawurlencode($product->get_name()));
    $price_html = $product->get_price_html();
    $sku = $product->get_sku();
    $categories = get_the_terms(get_the_ID(), 'product_cat');
    $applications = get_the_terms(get_the_ID(), 'product_tag');
    $power_terms = taxonomy_exists('pa_power') ? get_the_terms(get_the_ID(), 'pa_power') : array();
    $download_links = get_post_meta(get_the_ID(), 'stage_download_links', true);
    ?>
    <section class="section">
        <div class="container product-detail-layout">
            <div class="product-detail-main">
                <p class="eyebrow">Product Detail</p>
                <h1><?php echo esc_html($product->get_name()); ?></h1>
                <div class="product-detail-meta">
                    <?php if (!empty($price_html)) : ?>
                        <span class="product-price"><?php echo wp_kses_post($price_html); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($sku)) : ?>
                        <span>SKU: <?php echo esc_html($sku); ?></span>
                    <?php endif; ?>
                    <span>Status: <?php echo esc_html($product->is_in_stock() ? 'In Stock' : 'Out of Stock'); ?></span>
                </div>
                <div class="prose">
                    <?php the_content(); ?>
                </div>

                <h2>Specifications</h2>
                <table class="spec-table">
                    <tbody>
                    <tr>
                        <th>Category</th>
                        <td><?php echo esc_html($categories ? implode(', ', wp_list_pluck($categories, 'name')) : 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Application</th>
                        <td><?php echo esc_html($applications ? implode(', ', wp_list_pluck($applications, 'name')) : 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Power</th>
                        <td><?php echo esc_html($power_terms ? implode(', ', wp_list_pluck($power_terms, 'name')) : 'N/A'); ?></td>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <td><?php echo wp_kses_post($price_html ? $price_html : 'Contact Sales'); ?></td>
                    </tr>
                    </tbody>
                </table>

                <h2>Downloads</h2>
                <?php if (!empty($download_links)) : ?>
                    <ul class="download-list">
                        <?php
                        $links = array_filter(array_map('trim', explode("\n", (string) $download_links)));
                        foreach ($links as $link) :
                            ?>
                            <li><a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener">Download Resource</a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else : ?>
                    <p>No downloads added yet. Add links in custom field <code>stage_download_links</code> (one URL per line).</p>
                <?php endif; ?>
            </div>
            <aside class="product-detail-sidebar">
                <a class="btn btn-gradient" href="<?php echo esc_url($quote_url); ?>">Request Bulk Quote</a>
                <div class="sidebar-card">
                    <h3>Need consulting?</h3>
                    <p>Send your venue size, fixture list, and target effect. We respond within 24 hours.</p>
                    <p>Email: sales@example.com</p>
                </div>
                <div class="sidebar-card">
                    <h3>Trust Signals</h3>
                    <ul>
                        <li>CE / FCC / RoHS</li>
                        <li>Factory OEM / ODM</li>
                        <li>Global shipping support</li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>
    <?php
    $related_products = wc_get_products(
        array(
            'status'  => 'publish',
            'exclude' => array(get_the_ID()),
            'limit'   => 3,
        )
    );
    ?>
    <section class="section">
        <div class="container">
            <h2>Recommended Products</h2>
            <div class="grid grid-3">
                <?php if (!empty($related_products)) : ?>
                    <?php foreach ($related_products as $related_product) : ?>
                        <article class="card product-card">
                            <div>
                                <h3><a href="<?php echo esc_url(get_permalink($related_product->get_id())); ?>"><?php echo esc_html($related_product->get_name()); ?></a></h3>
                                <p><?php echo esc_html(wp_trim_words(get_post_field('post_excerpt', $related_product->get_id()), 16)); ?></p>
                            </div>
                            <a class="btn btn-outline" href="<?php echo esc_url(get_permalink($related_product->get_id())); ?>">View Product</a>
                        </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="card"><p>Add more products to show recommendations.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endwhile; ?>

<?php
get_footer();
?>
