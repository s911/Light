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
    $video_url = (string) get_post_meta(get_the_ID(), 'stage_video_url', true);
    $attributes = $product->get_attributes();
    $gallery_ids = $product->get_gallery_image_ids();
    $featured_id = $product->get_image_id();
    if (!empty($featured_id)) {
        array_unshift($gallery_ids, (int) $featured_id);
    }
    $gallery_ids = array_values(array_unique(array_filter(array_map('intval', $gallery_ids))));
    ?>
    <section class="section">
        <div class="container product-detail-layout">
            <div class="product-detail-main">
                <?php
                if (function_exists('stage_lighting_render_breadcrumb')) {
                    stage_lighting_render_breadcrumb(
                        array(
                            array('label' => 'Home', 'url' => home_url('/')),
                            array('label' => 'Products', 'url' => stage_lighting_get_products_page_url()),
                            array('label' => $product->get_name(), 'url' => get_permalink()),
                        )
                    );
                }
                ?>
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

                <?php if (!empty($gallery_ids)) : ?>
                    <h2>Product Gallery</h2>
                    <div class="product-gallery">
                        <?php $main_url = wp_get_attachment_image_url($gallery_ids[0], 'large'); ?>
                        <a class="product-gallery-main" href="<?php echo esc_url($main_url); ?>" data-lightbox-open="1">
                            <?php echo wp_get_attachment_image($gallery_ids[0], 'large'); ?>
                        </a>
                        <?php if (count($gallery_ids) > 1) : ?>
                            <div class="product-gallery-thumbs">
                                <?php foreach ($gallery_ids as $gid) : ?>
                                    <a href="<?php echo esc_url(wp_get_attachment_image_url($gid, 'full')); ?>" data-lightbox-open="1">
                                        <?php echo wp_get_attachment_image($gid, 'thumbnail'); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($video_url)) : ?>
                    <h2>Product Video</h2>
                    <div class="product-video-wrap">
                        <?php echo wp_oembed_get(esc_url($video_url)) ?: '<a href="' . esc_url($video_url) . '" target="_blank" rel="noopener">Watch Video</a>'; ?>
                    </div>
                <?php endif; ?>

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
                    <?php foreach ($attributes as $attr) : ?>
                        <?php
                        if (!$attr->get_visible()) {
                            continue;
                        }
                        $label = wc_attribute_label($attr->get_name());
                        if ($attr->is_taxonomy()) {
                            $terms = wc_get_product_terms($product->get_id(), $attr->get_name(), array('fields' => 'names'));
                            $value = implode(', ', $terms);
                        } else {
                            $value = implode(', ', $attr->get_options());
                        }
                        if ('' === trim($value)) {
                            continue;
                        }
                        ?>
                        <tr>
                            <th><?php echo esc_html($label); ?></th>
                            <td><?php echo esc_html($value); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>Downloads</h2>
                <?php if (!empty($download_links)) : ?>
                    <?php
                    $groups = array();
                    $lines = array_filter(array_map('trim', explode("\n", (string) $download_links)));
                    foreach ($lines as $line) {
                        $group = 'General';
                        $url = $line;
                        if (false !== strpos($line, '::')) {
                            list($maybe_group, $maybe_url) = array_map('trim', explode('::', $line, 2));
                            if (!empty($maybe_group) && !empty($maybe_url)) {
                                $group = $maybe_group;
                                $url = $maybe_url;
                            }
                        }
                        $groups[$group][] = $url;
                    }
                    ?>
                    <?php foreach ($groups as $group_name => $links) : ?>
                        <h3 class="download-group-title"><?php echo esc_html($group_name); ?></h3>
                        <ul class="download-list">
                            <?php foreach ($links as $link) : ?>
                                <li><a href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener">Download Resource</a></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endforeach; ?>
                <?php else : ?>
                    <p>No downloads added yet. Add links in custom field <code>stage_download_links</code> (one URL per line).</p>
                <?php endif; ?>

                <h2>Share This Product</h2>
                <div class="share-links">
                    <a class="btn btn-outline" target="_blank" rel="noopener" href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode(get_permalink())); ?>">Facebook</a>
                    <a class="btn btn-outline" target="_blank" rel="noopener" href="<?php echo esc_url('https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode(get_permalink())); ?>">LinkedIn</a>
                    <a class="btn btn-outline" target="_blank" rel="noopener" href="<?php echo esc_url('https://twitter.com/intent/tweet?url=' . rawurlencode(get_permalink()) . '&text=' . rawurlencode($product->get_name())); ?>">X</a>
                    <a class="btn btn-outline" target="_blank" rel="noopener" href="<?php echo esc_url('https://wa.me/?text=' . rawurlencode($product->get_name() . ' ' . get_permalink())); ?>">WhatsApp</a>
                </div>
            </div>
            <aside class="product-detail-sidebar">
                <a class="btn btn-gradient" href="<?php echo esc_url($quote_url); ?>">Request Bulk Quote</a>
                <?php
                $GLOBALS['product'] = $product;
                woocommerce_template_single_add_to_cart();
                ?>
                <a class="btn btn-outline" href="#" data-compare-toggle="1" data-product-id="<?php echo esc_attr((string) get_the_ID()); ?>">Add Compare</a>
                <a class="btn btn-outline" href="#" data-wishlist-toggle="1" data-product-id="<?php echo esc_attr((string) get_the_ID()); ?>">Add Wishlist</a>
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
<div class="quick-view-modal" id="stage-lightbox-modal">
    <div class="quick-view-card">
        <button class="btn btn-outline quick-view-close" type="button" id="stage-lightbox-close">Close</button>
        <img id="stage-lightbox-image" src="" alt="Product image">
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var modal = document.getElementById("stage-lightbox-modal");
    var closeBtn = document.getElementById("stage-lightbox-close");
    var image = document.getElementById("stage-lightbox-image");
    if (!modal || !closeBtn || !image) {
        return;
    }
    document.addEventListener("click", function (evt) {
        var anchor = evt.target.closest("[data-lightbox-open]");
        if (!anchor) {
            return;
        }
        evt.preventDefault();
        image.src = anchor.getAttribute("href") || "";
        modal.classList.add("is-open");
    });
    closeBtn.addEventListener("click", function () {
        modal.classList.remove("is-open");
    });
    modal.addEventListener("click", function (evt) {
        if (evt.target === modal) {
            modal.classList.remove("is-open");
        }
    });
});
</script>

<?php
get_footer();
?>
