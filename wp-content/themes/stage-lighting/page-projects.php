<?php
/*
Template Name: Projects Showcase
*/
get_header();

$projects = new WP_Query(
    array(
        'post_type'      => 'project',
        'posts_per_page' => 9,
        'post_status'    => 'publish',
    )
);
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Case Studies</p>
        <h1>Projects</h1>
        <p>Production-ready examples from concerts, theaters, clubs and event installations.</p>
        <div class="grid grid-3">
            <?php if ($projects->have_posts()) : ?>
                <?php while ($projects->have_posts()) : $projects->the_post(); ?>
                    <article class="card project-card">
                        <div>
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 22)); ?></p>
                        </div>
                        <a class="btn btn-outline" href="<?php the_permalink(); ?>">Read Case</a>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="card">
                    <p>No project case studies yet. Add entries in the Projects post type.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
get_footer();
?>
