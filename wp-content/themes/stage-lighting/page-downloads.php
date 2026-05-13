<?php
/*
Template Name: Downloads Library
*/
get_header();

$downloads = new WP_Query(
    array(
        'post_type'      => 'download',
        'posts_per_page' => 12,
        'post_status'    => 'publish',
    )
);
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Resources</p>
        <h1>Downloads</h1>
        <p>Access product manuals, certifications, and photometric files for project submission.</p>
        <div class="grid grid-3">
            <?php if ($downloads->have_posts()) : ?>
                <?php while ($downloads->have_posts()) : $downloads->the_post(); ?>
                    <article class="card download-card">
                        <div>
                            <h3><?php the_title(); ?></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt() ?: get_the_content(), 20)); ?></p>
                        </div>
                        <a class="btn btn-outline" href="<?php the_permalink(); ?>">View Details</a>
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
