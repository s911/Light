<?php
get_header();
$solutions = stage_lighting_solution_labels();
$products_url = stage_lighting_get_products_page_url();
$home_modules = function_exists('stage_setup_get_homepage_modules')
    ? stage_setup_get_homepage_modules()
    : array(
        'hero' => '1',
        'hot_products' => '1',
        'categories' => '1',
        'solutions' => '1',
        'projects' => '1',
        'why_choose_us' => '1',
        'partner_logos' => '1',
        'blog' => '1',
        'newsletter' => '1',
    );
$hot_products = new WP_Query(
    array(
        'post_type'      => 'product',
        'posts_per_page' => 6,
        'post_status'    => 'publish',
    )
);
$featured_projects = new WP_Query(
    array(
        'post_type'      => 'project',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
    )
);
$latest_posts = new WP_Query(
    array(
        'post_type'      => 'post',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
    )
);
?>
<?php if ('1' === (string) ($home_modules['hero'] ?? '1')) : ?>
    <section class="hero">
        <div class="hero-glow" aria-hidden="true"></div>
        <div class="container">
            <p class="eyebrow">Professional Stage Lighting</p>
            <h1>High-Performance Lighting for Touring, Venue and Live Events</h1>
            <p>Build your full rig from beam lights to controllers, structure systems and effects machines.</p>
            <div class="cta-row">
                <a class="btn btn-gradient" href="<?php echo esc_url(home_url('/products')); ?>">Shop Now</a>
                <a class="btn btn-outline" href="<?php echo esc_url(home_url('/for-business')); ?>">Get Bulk Quote</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['hot_products'] ?? '1')) : ?>
    <section class="section">
        <div class="container">
            <h2>Hot Products</h2>
            <div class="grid grid-3">
                <?php if ($hot_products->have_posts()) : ?>
                    <?php while ($hot_products->have_posts()) : $hot_products->the_post(); ?>
                        <?php $hot_rating = get_post_meta(get_the_ID(), '_wc_average_rating', true); ?>
                        <article class="card product-card">
                            <div>
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
                                <p>Rating: <?php echo esc_html($hot_rating !== '' ? (string) $hot_rating : '0'); ?></p>
                            </div>
                            <div class="cta-row">
                                <a class="btn btn-outline" href="<?php the_permalink(); ?>">View Product</a>
                                <a class="btn btn-outline" href="#" data-compare-toggle="1" data-product-id="<?php echo esc_attr((string) get_the_ID()); ?>">Add Compare</a>
                                <a class="btn btn-outline" href="#" data-wishlist-toggle="1" data-product-id="<?php echo esc_attr((string) get_the_ID()); ?>">Add Wishlist</a>
                                <a class="btn btn-gradient" href="<?php echo esc_url(home_url('/for-business/?product=' . rawurlencode(get_the_title()))); ?>">Bulk Quote</a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <div class="card"><p>Add products in WooCommerce to show hot products here.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['categories'] ?? '1')) : ?>
    <section class="section">
        <div class="container">
            <h2>Product Categories</h2>
            <div class="grid grid-3">
                <a class="card" href="<?php echo esc_url($products_url); ?>">All Products</a>
                <a class="card" href="<?php echo esc_url(add_query_arg('category', 'stage-lighting', $products_url)); ?>">Stage Lighting</a>
                <a class="card" href="<?php echo esc_url(add_query_arg('category', 'lighting-control', $products_url)); ?>">Lighting Control</a>
                <a class="card" href="<?php echo esc_url(add_query_arg('category', 'stage-structure', $products_url)); ?>">Stage Structure</a>
                <a class="card" href="<?php echo esc_url(add_query_arg('category', 'special-effects', $products_url)); ?>">Special Effects</a>
                <a class="card" href="<?php echo esc_url(add_query_arg('category', 'cables-accessories', $products_url)); ?>">Cables & Accessories</a>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['solutions'] ?? '1')) : ?>
    <section class="section">
        <div class="container">
            <h2>Solutions</h2>
            <div class="grid grid-3">
                <?php foreach ($solutions as $slug => $label) : ?>
                    <a class="card" href="<?php echo esc_url(add_query_arg('solution', $slug, $products_url)); ?>"><?php echo esc_html($label); ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['projects'] ?? '1')) : ?>
    <section class="section">
        <div class="container">
            <h2>Projects</h2>
            <div class="grid grid-3">
                <?php if ($featured_projects->have_posts()) : ?>
                    <?php while ($featured_projects->have_posts()) : $featured_projects->the_post(); ?>
                        <article class="card project-card">
                            <div>
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 20)); ?></p>
                            </div>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <div class="card"><p>Add project posts to highlight successful installations.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['why_choose_us'] ?? '1')) : ?>
    <section class="section">
        <div class="container">
            <h2>Why Choose Us</h2>
            <div class="grid grid-4">
                <div class="card">CE / FCC / RoHS Certificates</div>
                <div class="card">24h Sales Response</div>
                <div class="card">OEM / ODM Manufacturing</div>
                <div class="card">Global Logistics & After-sales</div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['partner_logos'] ?? '1')) : ?>
    <section class="section">
        <div class="container">
            <h2>Partner Logos</h2>
            <div class="grid grid-5">
                <div class="card logo-card">Partner A</div>
                <div class="card logo-card">Partner B</div>
                <div class="card logo-card">Partner C</div>
                <div class="card logo-card">Partner D</div>
                <div class="card logo-card">Partner E</div>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['blog'] ?? '1')) : ?>
    <section class="section">
        <div class="container">
            <h2>From Blog</h2>
            <div class="grid grid-3">
                <?php if ($latest_posts->have_posts()) : ?>
                    <?php while ($latest_posts->have_posts()) : $latest_posts->the_post(); ?>
                        <article class="card blog-card">
                            <div>
                                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
                            </div>
                            <a class="btn btn-outline" href="<?php the_permalink(); ?>">Read More</a>
                        </article>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <div class="card"><p>Create blog posts to power SEO and content marketing.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ('1' === (string) ($home_modules['newsletter'] ?? '1')) : ?>
    <section class="section">
        <div class="container newsletter">
            <h2>Get Product Updates</h2>
            <p>Join our newsletter for launch announcements and lighting setup guides.</p>
        </div>
    </section>
<?php endif; ?>
<?php
get_footer();
?>
