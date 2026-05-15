<?php
/*
Template Name: Solutions Overview
*/
get_header();
$solutions = stage_lighting_solution_labels();
$products_url = stage_lighting_get_products_page_url();
$scene = isset($_GET['scene']) ? sanitize_text_field(wp_unslash($_GET['scene'])) : '';
$active_label = isset($solutions[$scene]) ? $solutions[$scene] : '';

$scene_descriptions = array(
    'concert-touring' => 'High-output beam and wash setups for touring productions with fast rigging and stable control.',
    'theater-auditorium' => 'Precision fixtures and quiet operation optimized for stage drama and auditorium performance.',
    'tv-studio' => 'Camera-friendly lighting systems with accurate dimming, color rendering and DMX networking.',
    'nightclub-bar' => 'Impact-driven effects packages for immersive nightlife environments.',
    'wedding-events' => 'Portable and easy-deploy lighting kits for wedding and event teams.',
    'architectural-lighting' => 'Long-runtime fixtures and controllers for facades and building illumination.',
    'dj-party' => 'Compact and affordable packs for mobile DJs and party rentals.',
);

$recommended_products = array();
$scene_projects = array();
if (!empty($active_label)) {
    $recommended_products = wc_get_products(
        array(
            'status' => 'publish',
            'limit'  => 6,
            'tag'    => array($scene),
        )
    );
    $scene_projects = get_posts(
        array(
            'post_type'      => 'project',
            'post_status'    => 'publish',
            'posts_per_page' => 3,
            'meta_query'     => array(
                array(
                    'key'     => 'project_venue_type',
                    'value'   => $active_label,
                    'compare' => 'LIKE',
                ),
            ),
        )
    );
}
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Application Scenarios</p>
        <h1>Solutions</h1>
        <p>Select a scenario to view recommended products and start your project quickly.</p>
        <div class="grid grid-3 scene-grid">
            <?php foreach ($solutions as $slug => $label) : ?>
                <a class="card solution-card <?php echo $scene === $slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('scene', $slug, get_permalink())); ?>">
                    <div>
                        <h3><?php echo esc_html($label); ?></h3>
                        <p>Discover fixtures and control gear for <?php echo esc_html($label); ?>.</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php if (!empty($active_label)) : ?>
    <section class="section">
        <div class="container">
            <p class="eyebrow">Scene Landing</p>
            <h2><?php echo esc_html($active_label); ?></h2>
            <p><?php echo esc_html($scene_descriptions[$scene] ?? 'Professional solution bundle for this venue scenario.'); ?></p>
            <div class="cta-row">
                <a class="btn btn-gradient" href="<?php echo esc_url(add_query_arg('solution', $scene, $products_url)); ?>">View Matching Products</a>
                <a class="btn btn-outline" href="<?php echo esc_url(home_url('/for-business/?product=' . rawurlencode($active_label . ' Package'))); ?>">Request Project Quote</a>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <h2>Recommended Products</h2>
            <div class="grid grid-3">
                <?php if (!empty($recommended_products)) : ?>
                    <?php foreach ($recommended_products as $rp) : ?>
                        <article class="card product-card">
                            <div>
                                <h3><a href="<?php echo esc_url(get_permalink($rp->get_id())); ?>"><?php echo esc_html($rp->get_name()); ?></a></h3>
                                <p><?php echo esc_html(wp_trim_words(get_post_field('post_excerpt', $rp->get_id()), 18)); ?></p>
                            </div>
                            <a class="btn btn-outline" href="<?php echo esc_url(get_permalink($rp->get_id())); ?>">View Product</a>
                        </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="card"><p>No products tagged for this scene yet. Use product tags to map content.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section class="section">
        <div class="container">
            <h2>Related Case Studies</h2>
            <div class="grid grid-3">
                <?php if (!empty($scene_projects)) : ?>
                    <?php foreach ($scene_projects as $cp) : ?>
                        <article class="card project-card">
                            <div>
                                <h3><a href="<?php echo esc_url(get_permalink($cp->ID)); ?>"><?php echo esc_html(get_the_title($cp->ID)); ?></a></h3>
                                <p><?php echo esc_html(wp_trim_words(get_post_field('post_excerpt', $cp->ID), 16)); ?></p>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else : ?>
                    <div class="card"><p>No case studies mapped yet for this scenario.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </section>
<?php endif; ?>
<?php
get_footer();
?>
