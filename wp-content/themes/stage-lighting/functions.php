<?php
if (!defined('ABSPATH')) {
    exit;
}

function stage_lighting_theme_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('menus');
    add_theme_support('custom-logo');

    register_nav_menus(
        array(
            'primary' => __('Primary Menu', 'stage-lighting'),
            'footer'  => __('Footer Menu', 'stage-lighting'),
        )
    );
}
add_action('after_setup_theme', 'stage_lighting_theme_setup');

function stage_lighting_enqueue_assets() {
    wp_enqueue_style(
        'stage-lighting-main',
        get_template_directory_uri() . '/assets/css/theme.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'stage_lighting_enqueue_assets');

function stage_lighting_register_cpts() {
    register_post_type(
        'project',
        array(
            'labels' => array(
                'name'          => __('Projects', 'stage-lighting'),
                'singular_name' => __('Project', 'stage-lighting'),
            ),
            'public'      => true,
            'menu_icon'   => 'dashicons-portfolio',
            'supports'    => array('title', 'editor', 'thumbnail', 'excerpt'),
            'has_archive' => 'case-studies',
            'rewrite'     => array('slug' => 'case-studies'),
        )
    );

    register_post_type(
        'download',
        array(
            'labels' => array(
                'name'          => __('Downloads', 'stage-lighting'),
                'singular_name' => __('Download', 'stage-lighting'),
            ),
            'public'      => true,
            'menu_icon'   => 'dashicons-download',
            'supports'    => array('title', 'editor', 'thumbnail'),
            'has_archive' => true,
            'rewrite'     => array('slug' => 'downloads'),
        )
    );
}
add_action('init', 'stage_lighting_register_cpts');

function stage_lighting_solution_labels() {
    return array(
        'concert-touring' => 'Concert & Touring',
        'theater-auditorium' => 'Theater & Auditorium',
        'tv-studio' => 'TV Studio',
        'nightclub-bar' => 'Nightclub & Bar',
        'wedding-events' => 'Wedding & Events',
        'architectural-lighting' => 'Architectural Lighting',
        'dj-party' => 'DJ & Party',
    );
}

function stage_lighting_get_products_page_url() {
    $page = get_page_by_path('products');
    if ($page instanceof WP_Post) {
        return get_permalink($page->ID);
    }
    return home_url('/products');
}


