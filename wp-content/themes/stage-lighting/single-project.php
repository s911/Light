<?php
get_header();
while (have_posts()) :
    the_post();
    $client = (string) get_post_meta(get_the_ID(), 'project_client', true);
    $venue = (string) get_post_meta(get_the_ID(), 'project_venue_type', true);
    $country = (string) get_post_meta(get_the_ID(), 'project_country', true);
    $used_products = (string) get_post_meta(get_the_ID(), 'project_used_products', true);
    $results = (string) get_post_meta(get_the_ID(), 'project_results', true);
    ?>
    <section class="section">
        <div class="container">
            <p class="eyebrow">Project Case</p>
            <h1><?php the_title(); ?></h1>
            <div class="case-meta">
                <?php if ($client) : ?><span>Client: <?php echo esc_html($client); ?></span><?php endif; ?>
                <?php if ($venue) : ?><span>Venue: <?php echo esc_html($venue); ?></span><?php endif; ?>
                <?php if ($country) : ?><span>Country: <?php echo esc_html($country); ?></span><?php endif; ?>
            </div>
            <div class="prose">
                <?php the_content(); ?>
            </div>
            <div class="grid grid-2">
                <article class="card">
                    <h3>Used Products</h3>
                    <p><?php echo esc_html($used_products ? $used_products : 'TBD'); ?></p>
                </article>
                <article class="card">
                    <h3>Project Results</h3>
                    <p><?php echo esc_html($results ? $results : 'TBD'); ?></p>
                </article>
            </div>
        </div>
    </section>
<?php
endwhile;
get_footer();
?>
