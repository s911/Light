<?php
/*
Template Name: For Business
*/
get_header();

$certificate_items = get_posts(
    array(
        'post_type'      => 'download',
        'posts_per_page' => 6,
        'post_status'    => 'publish',
        'tax_query'      => array(
            array(
                'taxonomy' => 'download_type',
                'field'    => 'slug',
                'terms'    => 'certificate',
            ),
        ),
    )
);
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">B2B Lead Generation</p>
        <h1>Request Bulk Quote</h1>
        <p>Tell us your project details and quantity requirements. Our sales team replies within 24 hours.</p>
        <?php echo do_shortcode('[stage_bulk_quote_form]'); ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>OEM / ODM Service</h2>
        <div class="grid grid-3">
            <article class="card">
                <h3>Custom Engineering</h3>
                <p>Optics, control protocol and mechanical structure tailored to your project.</p>
            </article>
            <article class="card">
                <h3>Private Label</h3>
                <p>Brand packaging, logo customization and product documentation support.</p>
            </article>
            <article class="card">
                <h3>Compliance Support</h3>
                <p>CE / FCC / RoHS documentation and testing guidance for target markets.</p>
            </article>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Business Workflow</h2>
        <div class="grid grid-4">
            <div class="card">1) Requirement Intake</div>
            <div class="card">2) Technical Proposal</div>
            <div class="card">3) Sample & Verification</div>
            <div class="card">4) Production & Delivery</div>
        </div>
    </div>
</section>
<section class="section">
    <div class="container">
        <h2>Certificates & Documents</h2>
        <div class="grid grid-3">
            <?php if (!empty($certificate_items)) : ?>
                <?php foreach ($certificate_items as $item) : ?>
                    <?php $file_url = (string) get_post_meta($item->ID, 'download_file_url', true); ?>
                    <article class="card download-card">
                        <div>
                            <h3><?php echo esc_html(get_the_title($item->ID)); ?></h3>
                            <p><?php echo esc_html(wp_trim_words(get_post_field('post_excerpt', $item->ID) ?: get_post_field('post_content', $item->ID), 18)); ?></p>
                        </div>
                        <?php if (!empty($file_url)) : ?>
                            <a class="btn btn-gradient" href="<?php echo esc_url($file_url); ?>" target="_blank" rel="noopener">Download</a>
                        <?php else : ?>
                            <a class="btn btn-outline" href="<?php echo esc_url(get_permalink($item->ID)); ?>">View Details</a>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php else : ?>
                <div class="card"><p>Add download posts and set type "Certificate" to display here.</p></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
get_footer();
?>
