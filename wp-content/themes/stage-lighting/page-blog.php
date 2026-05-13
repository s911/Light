<?php
/*
Template Name: Blog Listing
*/
get_header();

$posts_query = new WP_Query(
    array(
        'post_type'      => 'post',
        'posts_per_page' => 10,
        'post_status'    => 'publish',
        'paged'          => max(1, get_query_var('paged')),
    )
);
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Insights</p>
        <h1>Blog / News</h1>
        <div class="grid grid-3">
            <?php if ($posts_query->have_posts()) : ?>
                <?php while ($posts_query->have_posts()) : $posts_query->the_post(); ?>
                    <article class="card blog-card">
                        <div>
                            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                            <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 24)); ?></p>
                        </div>
                        <a class="btn btn-outline" href="<?php the_permalink(); ?>">Read More</a>
                    </article>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            <?php else : ?>
                <div class="card">
                    <p>No blog posts yet. Publish your first article to start SEO content marketing.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
get_footer();
?>
