<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<header class="site-header">
    <div class="container nav-wrap">
        <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
            <?php if (has_custom_logo()) : ?>
                <?php the_custom_logo(); ?>
            <?php else : ?>
                <span><?php bloginfo('name'); ?></span>
            <?php endif; ?>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="stage-main-nav" data-nav-toggle>
            <span></span><span></span><span></span>
        </button>
        <nav class="main-nav">
            <?php
            wp_nav_menu(
                array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'menu_id'        => 'stage-main-nav',
                    'fallback_cb'    => false,
                )
            );
            ?>
        </nav>
        <form class="header-search" role="search" method="get" action="<?php echo esc_url(stage_lighting_get_products_page_url()); ?>">
            <input type="search" name="s" placeholder="Search products">
        </form>
        <?php if (function_exists('WC') && WC()->cart && function_exists('wc_get_cart_url')) : ?>
            <a class="btn btn-outline" href="<?php echo esc_url(wc_get_cart_url()); ?>">
                Cart (<span><?php echo esc_html((string) WC()->cart->get_cart_contents_count()); ?></span>)
            </a>
        <?php endif; ?>
        <a class="btn btn-outline" href="<?php echo esc_url(stage_lighting_get_compare_page_url()); ?>">
            Compare (<span data-compare-count>0</span>)
        </a>
        <a class="btn btn-outline" href="<?php echo esc_url(stage_lighting_get_wishlist_page_url()); ?>">
            Wishlist (<span data-wishlist-count>0</span>)
        </a>
        <a class="btn btn-gradient" href="<?php echo esc_url(home_url('/for-business')); ?>">For Business</a>
    </div>
</header>
<main class="site-main">
