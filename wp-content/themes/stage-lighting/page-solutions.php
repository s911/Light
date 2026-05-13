<?php
/*
Template Name: Solutions Overview
*/
get_header();
$solutions = stage_lighting_solution_labels();
$products_url = stage_lighting_get_products_page_url();
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Application Scenarios</p>
        <h1>Solutions</h1>
        <p>Select a scenario to view recommended products and start your project quickly.</p>
        <div class="grid grid-3">
            <?php foreach ($solutions as $slug => $label) : ?>
                <a class="card solution-card" href="<?php echo esc_url(add_query_arg('solution', $slug, $products_url)); ?>">
                    <div>
                        <h3><?php echo esc_html($label); ?></h3>
                        <p>Discover fixtures and control gear for <?php echo esc_html($label); ?>.</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
get_footer();
?>
