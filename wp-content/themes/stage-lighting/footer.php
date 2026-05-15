<?php
if (!defined('ABSPATH')) {
    exit;
}

$stage_sales_email = function_exists('stage_b2b_get_sales_email') ? stage_b2b_get_sales_email() : 'sales@example.com';
$stage_whatsapp_display = function_exists('stage_b2b_get_whatsapp_display') ? stage_b2b_get_whatsapp_display() : '+1 000 000 0000';
$stage_whatsapp_number = function_exists('stage_b2b_get_whatsapp_number') ? stage_b2b_get_whatsapp_number() : '10000000000';
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <h4>Stage Lighting</h4>
            <p>Dark tech lighting solutions for touring, theater, and event production.</p>
        </div>
        <div>
            <h4>Contact</h4>
            <p>Email: <?php echo esc_html($stage_sales_email); ?></p>
            <p>WhatsApp: <?php echo esc_html($stage_whatsapp_display); ?></p>
        </div>
        <div>
            <h4>Quick Links</h4>
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'footer',
                    'container'      => false,
                    'fallback_cb'    => false,
                )
            );
            ?>
            <?php
            if (function_exists('stage_marketing_social_links')) {
                stage_marketing_social_links(true);
            }
            ?>
            <div class="payment-icons">
                <span>PayPal</span>
                <span>Visa</span>
                <span>Mastercard</span>
                <span>Stripe</span>
            </div>
        </div>
    </div>
</footer>
<a class="stage-whatsapp-float" href="<?php echo esc_url('https://wa.me/' . $stage_whatsapp_number); ?>" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">
    WhatsApp
</a>
<?php wp_footer(); ?>
</body>
</html>
