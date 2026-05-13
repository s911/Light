<?php
/*
Template Name: For Business
*/
get_header();
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">B2B Lead Generation</p>
        <h1>Request Bulk Quote</h1>
        <p>Tell us your project details and quantity requirements. Our sales team replies within 24 hours.</p>
        <?php echo do_shortcode('[stage_bulk_quote_form]'); ?>
    </div>
</section>
<?php
get_footer();
?>
