<?php
/*
Template Name: Order Tracking
*/
get_header();
?>
<section class="section">
    <div class="container">
        <p class="eyebrow">Customer Service</p>
        <h1>Order Tracking</h1>
        <p>Enter your order details to view shipping and fulfillment status.</p>
        <div class="card prose">
            <?php
            if (shortcode_exists('woocommerce_order_tracking')) {
                echo do_shortcode('[woocommerce_order_tracking]');
            } else {
                echo '<p>WooCommerce order tracking is unavailable. Please activate WooCommerce.</p>';
            }
            ?>
        </div>
    </div>
</section>
<?php
get_footer();
?>
