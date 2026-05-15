<?php
/*
Template Name: Blog Listing
*/
get_header();

$category = isset($_GET['cat']) ? sanitize_text_field(wp_unslash($_GET['cat'])) : '';
$tag = isset($_GET['tag']) ? sanitize_text_field(wp_unslash($_GET['tag'])) : '';

$query_args = array(
    'post_type'      => 'post',
    'posts_per_page' => 10,
    'post_status'    => 'publish',
    'paged'          => max(1, get_query_var('paged')),
);
if (!empty($category)) {
    $query_args['category_name'] = $category;
}
if (!empty($tag)) {
    $query_args['tag'] = $tag;
}

$posts_query = new WP_Query(
    $query_args
);
$categories = get_categories(array('hide_empty' => true));
$tags = get_tags(array('hide_empty' => true, 'number' => 15));
$seo_links = array(
    array('label' => 'Moving Head Lighting Guide', 'url' => home_url('/blog/?cat=buying-guide')),
    array('label' => 'DMX Technical Basics', 'url' => home_url('/blog/?cat=tech')),
    array('label' => 'Concert Project Cases', 'url' => home_url('/projects')),
    array('label' => 'Bulk Order Consultation', 'url' => home_url('/for-business')),
);
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Insights</p>
        <h1>Blog / News</h1>
        <div class="blog-filters">
            <a class="btn btn-outline <?php echo empty($category) ? 'is-active' : ''; ?>" href="<?php echo esc_url(get_permalink()); ?>">All Categories</a>
            <?php foreach ($categories as $cat_obj) : ?>
                <a class="btn btn-outline <?php echo $category === $cat_obj->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('cat', $cat_obj->slug, get_permalink())); ?>"><?php echo esc_html($cat_obj->name); ?></a>
            <?php endforeach; ?>
        </div>
        <div class="blog-tags">
            <strong>Tags:</strong>
            <?php foreach ($tags as $tag_obj) : ?>
                <a class="btn btn-outline blog-tag <?php echo $tag === $tag_obj->slug ? 'is-active' : ''; ?>" href="<?php echo esc_url(add_query_arg('tag', $tag_obj->slug, get_permalink())); ?>"><?php echo esc_html($tag_obj->name); ?></a>
            <?php endforeach; ?>
        </div>
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
        <section class="seo-links-block">
            <h2>Related Reading</h2>
            <div class="grid grid-2">
                <?php foreach ($seo_links as $link) : ?>
                    <a class="card" href="<?php echo esc_url($link['url']); ?>"><?php echo esc_html($link['label']); ?></a>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</section>
<?php
get_footer();
?>
