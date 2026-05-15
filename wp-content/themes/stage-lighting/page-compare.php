<?php
/*
Template Name: Product Compare
*/
get_header();

if (!function_exists('wc_get_products')) {
    ?>
    <section class="section">
        <div class="container"><p>WooCommerce is required for compare feature.</p></div>
    </section>
    <?php
    get_footer();
    return;
}

$ids_raw = isset($_COOKIE['stage_compare_ids']) ? sanitize_text_field(wp_unslash($_COOKIE['stage_compare_ids'])) : '';
if (empty($ids_raw) && isset($_GET['ids'])) {
    $ids_raw = sanitize_text_field(wp_unslash($_GET['ids']));
}
$ids = array_values(
    array_filter(
        array_map(
            'intval',
            array_map('trim', explode(',', (string) $ids_raw))
        )
    )
);
$ids = array_slice(array_unique($ids), 0, 4);

$products = array();
if (!empty($ids)) {
    $products = wc_get_products(
        array(
            'include' => $ids,
            'limit'   => 4,
            'status'  => 'publish',
        )
    );
}

$attribute_keys = array();
foreach ($products as $p) {
    foreach ($p->get_attributes() as $attr) {
        if ($attr->get_visible()) {
            $attribute_keys[$attr->get_name()] = wc_attribute_label($attr->get_name());
        }
    }
}
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">P1 Feature</p>
        <h1>Product Compare</h1>
        <?php if (empty($products)) : ?>
            <div class="card">
                <p>No products selected for compare. Go to Products and click "Add Compare".</p>
            </div>
        <?php else : ?>
            <div class="compare-actions">
                <a class="btn btn-outline" href="<?php echo esc_url(stage_lighting_get_products_page_url()); ?>">Back to Products</a>
                <button class="btn btn-outline" type="button" id="stage-clear-compare">Clear Compare</button>
            </div>
            <div class="table-wrap">
                <table class="compare-table">
                    <tbody>
                    <tr>
                        <th>Item</th>
                        <?php foreach ($products as $p) : ?>
                            <td>
                                <strong><a href="<?php echo esc_url(get_permalink($p->get_id())); ?>"><?php echo esc_html($p->get_name()); ?></a></strong>
                                <div style="margin-top:10px;">
                                    <a class="btn btn-outline" href="#" data-compare-toggle="1" data-product-id="<?php echo esc_attr((string) $p->get_id()); ?>">Remove</a>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>Price</th>
                        <?php foreach ($products as $p) : ?>
                            <td><?php echo wp_kses_post($p->get_price_html()); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>SKU</th>
                        <?php foreach ($products as $p) : ?>
                            <td><?php echo esc_html($p->get_sku()); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <?php foreach ($products as $p) : ?>
                            <td><?php echo esc_html($p->is_in_stock() ? 'In Stock' : 'Out of Stock'); ?></td>
                        <?php endforeach; ?>
                    </tr>
                    <?php foreach ($attribute_keys as $attr_key => $label) : ?>
                        <tr>
                            <th><?php echo esc_html($label); ?></th>
                            <?php foreach ($products as $p) : ?>
                                <?php
                                $attrs = $p->get_attributes();
                                $value = '';
                                if (isset($attrs[$attr_key])) {
                                    $attr = $attrs[$attr_key];
                                    if ($attr->is_taxonomy()) {
                                        $terms = wc_get_product_terms($p->get_id(), $attr->get_name(), array('fields' => 'names'));
                                        $value = implode(', ', $terms);
                                    } else {
                                        $value = implode(', ', $attr->get_options());
                                    }
                                }
                                ?>
                                <td><?php echo esc_html($value ? $value : '-'); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <script>
            document.addEventListener("DOMContentLoaded", function () {
                var clearBtn = document.getElementById("stage-clear-compare");
                if (!clearBtn) {
                    return;
                }
                clearBtn.addEventListener("click", function () {
                    document.cookie = "stage_compare_ids=; path=/; max-age=0";
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
