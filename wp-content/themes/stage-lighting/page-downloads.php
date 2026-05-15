<?php
/*
Template Name: Downloads Library
*/
get_header();

$type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
$tax_query = array();
if (!empty($type)) {
    $tax_query[] = array(
        'taxonomy' => 'download_type',
        'field'    => 'slug',
        'terms'    => $type,
    );
}

$downloads = new WP_Query(
    array(
        'post_type'      => 'download',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
        'tax_query'      => $tax_query,
    )
);

$download_types = get_terms(
    array(
        'taxonomy'   => 'download_type',
        'hide_empty' => false,
    )
);
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Resources</p>
        <h1>Downloads</h1>
        <p>Access product manuals, certifications, and photometric files for project submission.</p>
        <div class="download-filters">
            <a class="btn btn-outline <?php echo empty($type) ? 'is-active' : ''; ?>" href="<?php echo esc_url(get_permalink()); ?>">All</a>
            <?php foreach ($download_types as $term) : ?>
                <a class="btn btn-outline <?php echo $type === $term->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('type', $term->slug, get_permalink())); ?>">
                    <?php echo esc_html($term->name); ?>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="grid grid-3">
            <?php if ($downloads->have_posts()) : ?>
                <?php while ($downloads->have_posts()) : $downloads->the_post(); ?>
                    <?php
                    $file_url = (string) get_post_meta(get_the_ID(), 'download_file_url', true);
                    $type_terms = get_the_terms(get_the_ID(), 'download_type');
                    ?>
                    <article class="card download-card">
                        <div>
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 20)); ?></p>
                            <p>
                                <small>
                                    <?php
                                    echo esc_html($type_terms ? implode(', ', wp_list_pluck($type_terms, 'name')) : 'Uncategorized');
                                    ?>
                                </small>
                            </p>
                        </div>
                        <?php if (!empty($file_url)) : ?>
                            <a class="btn btn-gradient" href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener">Download File</a>
                        <?php else : ?>
                            <a class="btn btn-outline" href="<?php the_permalink(); ?>">View Details</a>
                        <?php endif; ?>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="card">
                    <p>No downloads available yet. Add entries in the Downloads post type.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
get_footer();
?>
