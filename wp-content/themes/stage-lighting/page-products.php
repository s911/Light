<?php
/*
Template Name: Products Catalog
*/
get_header();

$search     = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
$category   = isset($_GET['category']) ? sanitize_text_field(wp_unslash($_GET['category'])) : '';
$solution   = isset($_GET['solution']) ? sanitize_text_field(wp_unslash($_GET['solution'])) : '';
$power      = isset($_GET['power']) ? sanitize_text_field(wp_unslash($_GET['power'])) : '';
$min_price  = isset($_GET['min_price']) ? floatval(wp_unslash($_GET['min_price'])) : '';
$max_price  = isset($_GET['max_price']) ? floatval(wp_unslash($_GET['max_price'])) : '';
$sort       = isset($_GET['sort']) ? sanitize_text_field(wp_unslash($_GET['sort'])) : 'latest';
$paged      = max(1, (int) get_query_var('paged'));

$args = array(
    'post_type'      => 'product',
    'posts_per_page' => 12,
    'post_status'    => 'publish',
    's'              => $search,
    'paged'          => $paged,
);

$tax_query = array();
if (!empty($category)) {
    $tax_query[] = array(
        'taxonomy' => 'product_cat',
        'field'    => 'slug',
        'terms'    => $category,
    );
}
if (!empty($solution)) {
    $tax_query[] = array(
        'taxonomy' => 'product_tag',
        'field'    => 'slug',
        'terms'    => $solution,
    );
}
if (!empty($power) && taxonomy_exists('pa_power')) {
    $tax_query[] = array(
        'taxonomy' => 'pa_power',
        'field'    => 'slug',
        'terms'    => $power,
    );
}
if (!empty($tax_query)) {
    $args['tax_query'] = $tax_query;
}

$meta_query = array();
if ('' !== $min_price) {
    $meta_query[] = array(
        'key'     => '_price',
        'value'   => $min_price,
        'compare' => '>=',
        'type'    => 'NUMERIC',
    );
}
if ('' !== $max_price) {
    $meta_query[] = array(
        'key'     => '_price',
        'value'   => $max_price,
        'compare' => '<=',
        'type'    => 'NUMERIC',
    );
}
if (!empty($meta_query)) {
    $args['meta_query'] = $meta_query;
}

if ('price_asc' === $sort) {
    $args['meta_key'] = '_price';
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'ASC';
} elseif ('price_desc' === $sort) {
    $args['meta_key'] = '_price';
    $args['orderby']  = 'meta_value_num';
    $args['order']    = 'DESC';
} else {
    $args['orderby'] = 'date';
    $args['order']   = 'DESC';
}

$products = new WP_Query($args);
$categories = get_terms(
    array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
    )
);
$solutions = get_terms(
    array(
        'taxonomy'   => 'product_tag',
        'hide_empty' => false,
    )
);
$power_terms = array();
if (taxonomy_exists('pa_power')) {
    $power_terms = get_terms(
        array(
            'taxonomy'   => 'pa_power',
            'hide_empty' => false,
        )
    );
}
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Catalog</p>
        <h1>Products</h1>
        <p class="result-count">
            <?php
            echo esc_html(sprintf('Showing %1$d of %2$d products', (int) $products->post_count, (int) $products->found_posts));
            ?>
        </p>
        <form class="catalog-filters" method="get">
            <input type="search" name="s" placeholder="Search by product name" value="<?php echo esc_attr($search); ?>">
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($category, $term->slug); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="solution">
                <option value="">All Applications</option>
                <?php foreach ($solutions as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($solution, $term->slug); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <select name="power">
                <option value="">All Power</option>
                <?php foreach ($power_terms as $term) : ?>
                    <option value="<?php echo esc_attr($term->slug); ?>" <?php selected($power, $term->slug); ?>>
                        <?php echo esc_html($term->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="min_price" placeholder="Min Price" value="<?php echo esc_attr($min_price); ?>" min="0" step="1">
            <input type="number" name="max_price" placeholder="Max Price" value="<?php echo esc_attr($max_price); ?>" min="0" step="1">
            <select name="sort">
                <option value="latest" <?php selected($sort, 'latest'); ?>>Latest</option>
                <option value="price_asc" <?php selected($sort, 'price_asc'); ?>>Price: Low to High</option>
                <option value="price_desc" <?php selected($sort, 'price_desc'); ?>>Price: High to Low</option>
            </select>
            <button class="btn btn-gradient" type="submit">Apply Filters</button>
            <a class="btn btn-outline filter-reset" href="<?php echo esc_url(get_permalink()); ?>">Reset</a>
        </form>

        <div class="grid grid-3 product-grid">
            <?php if ($products->have_posts()) : ?>
                <?php while ($products->have_posts()) : $products->the_post(); ?>
                    <article class="card product-card">
                        <div>
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                            <p class="product-price"><?php echo esc_html(get_post_meta(get_the_ID(), '_price', true)); ?> USD</p>
                        </div>
                        <div class="cta-row">
                            <a class="btn btn-outline" href="<?php the_permalink(); ?>">View Product</a>
                            <a class="btn btn-gradient" href="<?php echo esc_url(home_url('/for-business/?product=' . rawurlencode(get_the_title()))); ?>">Request Quote</a>
                        </div>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="card">
                    <p>No products found. Add products in WooCommerce and recheck filters.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        $pagination = paginate_links(
            array(
                'total'     => $products->max_num_pages,
                'current'   => $paged,
                'type'      => 'array',
                'prev_text' => 'Prev',
                'next_text' => 'Next',
            )
        );
        if (!empty($pagination)) :
            ?>
            <nav class="catalog-pagination" aria-label="Catalog Pagination">
                <?php foreach ($pagination as $item) : ?>
                    <?php echo wp_kses_post($item); ?>
                <?php endforeach; ?>
            </nav>
        <?php endif; ?>
    </div>
</section>
<?php
get_footer();
?>
