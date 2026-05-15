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
    wp_enqueue_script(
        'stage-lighting-theme',
        get_template_directory_uri() . '/assets/js/theme.js',
        array(),
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'stage-lighting-compare',
        get_template_directory_uri() . '/assets/js/compare.js',
        array(),
        '1.0.0',
        true
    );
    wp_enqueue_script(
        'stage-lighting-wishlist',
        get_template_directory_uri() . '/assets/js/wishlist.js',
        array(),
        '1.0.0',
        true
    );
    wp_localize_script(
        'stage-lighting-wishlist',
        'stageWishlistConfig',
        array(
            'ajaxUrl'  => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('stage_wishlist_nonce'),
            'loggedIn' => is_user_logged_in() ? '1' : '0',
        )
    );
}

function stage_lighting_render_breadcrumb($items) {
    if (!is_array($items) || count($items) < 2) {
        return;
    }
    echo '<nav class="stage-breadcrumb" aria-label="Breadcrumb">';
    $last = count($items) - 1;
    foreach ($items as $index => $item) {
        $label = isset($item['label']) ? (string) $item['label'] : '';
        $url = isset($item['url']) ? (string) $item['url'] : '';
        if ($index === $last || empty($url)) {
            echo '<span>' . esc_html($label) . '</span>';
        } else {
            echo '<a href="' . esc_url($url) . '">' . esc_html($label) . '</a><span class="sep">/</span>';
        }
    }
    echo '</nav>';
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
            'supports'    => array('title', 'editor', 'thumbnail', 'excerpt'),
            'has_archive' => true,
            'rewrite'     => array('slug' => 'downloads'),
        )
    );

    register_taxonomy(
        'download_type',
        'download',
        array(
            'labels' => array(
                'name'          => __('Download Types', 'stage-lighting'),
                'singular_name' => __('Download Type', 'stage-lighting'),
            ),
            'public'            => true,
            'show_admin_column' => true,
            'hierarchical'      => true,
            'rewrite'           => array('slug' => 'download-type'),
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

function stage_lighting_get_compare_page_url() {
    $page = get_page_by_path('product-compare');
    if ($page instanceof WP_Post) {
        return get_permalink($page->ID);
    }
    return home_url('/product-compare');
}

function stage_lighting_get_wishlist_page_url() {
    $page = get_page_by_path('wishlist');
    if ($page instanceof WP_Post) {
        return get_permalink($page->ID);
    }
    return home_url('/wishlist');
}

function stage_lighting_parse_id_list($raw) {
    if (is_array($raw)) {
        $ids = $raw;
    } else {
        $ids = explode(',', (string) $raw);
    }
    $clean = array();
    foreach ($ids as $id) {
        $id = (int) $id;
        if ($id > 0) {
            $clean[] = $id;
        }
    }
    return array_values(array_unique($clean));
}

function stage_lighting_get_wishlist_cookie_ids() {
    $raw = isset($_COOKIE['stage_wishlist_ids']) ? sanitize_text_field(wp_unslash($_COOKIE['stage_wishlist_ids'])) : '';
    return stage_lighting_parse_id_list($raw);
}

function stage_lighting_get_user_wishlist_ids($user_id = 0) {
    $user_id = $user_id > 0 ? (int) $user_id : (int) get_current_user_id();
    if ($user_id <= 0) {
        return array();
    }
    $stored = get_user_meta($user_id, 'stage_wishlist_ids', true);
    return stage_lighting_parse_id_list($stored);
}

function stage_lighting_update_user_wishlist_ids($ids, $user_id = 0) {
    $user_id = $user_id > 0 ? (int) $user_id : (int) get_current_user_id();
    if ($user_id <= 0) {
        return;
    }
    update_user_meta($user_id, 'stage_wishlist_ids', stage_lighting_parse_id_list($ids));
}

function stage_lighting_sync_wishlist_cookie_for_user() {
    if (!is_user_logged_in()) {
        return;
    }
    $cookie_ids = stage_lighting_get_wishlist_cookie_ids();
    $user_ids = stage_lighting_get_user_wishlist_ids();
    $merged = array_values(array_unique(array_merge($user_ids, $cookie_ids)));
    if ($merged !== $user_ids) {
        stage_lighting_update_user_wishlist_ids($merged);
    }
    if (!headers_sent()) {
        setcookie(
            'stage_wishlist_ids',
            implode(',', $merged),
            time() + 30 * DAY_IN_SECONDS,
            '/',
            '',
            is_ssl(),
            false
        );
    }
}
add_action('init', 'stage_lighting_sync_wishlist_cookie_for_user', 30);

function stage_lighting_wishlist_ajax_toggle() {
    check_ajax_referer('stage_wishlist_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_success(array('loggedIn' => false));
    }
    $product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
    if ($product_id <= 0) {
        wp_send_json_error(array('message' => 'Invalid product id'));
    }
    $ids = stage_lighting_get_user_wishlist_ids();
    $key = array_search($product_id, $ids, true);
    if (false === $key) {
        $ids[] = $product_id;
    } else {
        unset($ids[$key]);
    }
    $ids = array_values(array_unique(array_map('intval', $ids)));
    stage_lighting_update_user_wishlist_ids($ids);
    wp_send_json_success(array('loggedIn' => true, 'ids' => $ids, 'count' => count($ids)));
}
add_action('wp_ajax_stage_wishlist_toggle', 'stage_lighting_wishlist_ajax_toggle');

function stage_lighting_register_account_wishlist_endpoint() {
    if (!function_exists('wc_get_page_permalink')) {
        return;
    }
    add_rewrite_endpoint('wishlist', EP_ROOT | EP_PAGES);
}
add_action('init', 'stage_lighting_register_account_wishlist_endpoint');

function stage_lighting_account_menu_items($items) {
    if (!is_array($items)) {
        return $items;
    }
    $logout = isset($items['customer-logout']) ? $items['customer-logout'] : null;
    unset($items['customer-logout']);
    $items['wishlist'] = __('Wishlist', 'stage-lighting');
    if (null !== $logout) {
        $items['customer-logout'] = $logout;
    }
    return $items;
}
add_filter('woocommerce_account_menu_items', 'stage_lighting_account_menu_items');

function stage_lighting_account_wishlist_content() {
    if (!function_exists('wc_get_products')) {
        echo '<p>WooCommerce is required for wishlist.</p>';
        return;
    }
    $ids = stage_lighting_get_user_wishlist_ids();
    if (empty($ids)) {
        echo '<p>No wishlist products yet.</p>';
        return;
    }
    $products = wc_get_products(
        array(
            'include' => $ids,
            'limit'   => 100,
            'status'  => 'publish',
        )
    );
    if (empty($products)) {
        echo '<p>No wishlist products available.</p>';
        return;
    }
    echo '<ul class="woocommerce">';
    foreach ($products as $product) {
        echo '<li><a href="' . esc_url(get_permalink($product->get_id())) . '">' . esc_html($product->get_name()) . '</a> - ' . wp_kses_post($product->get_price_html()) . '</li>';
    }
    echo '</ul>';
}
add_action('woocommerce_account_wishlist_endpoint', 'stage_lighting_account_wishlist_content');

function stage_lighting_output_structured_data() {
    if (is_admin()) {
        return;
    }

    $org_data = array(
        '@context' => 'https://schema.org',
        '@type'    => 'Organization',
        'name'     => get_bloginfo('name'),
        'url'      => home_url('/'),
        'logo'     => '',
    );
    $custom_logo_id = get_theme_mod('custom_logo');
    if (!empty($custom_logo_id)) {
        $logo_url = wp_get_attachment_image_url((int) $custom_logo_id, 'full');
        if (!empty($logo_url)) {
            $org_data['logo'] = esc_url_raw($logo_url);
        }
    }
    if (empty($org_data['logo'])) {
        unset($org_data['logo']);
    }

    $website_data = array(
        '@context' => 'https://schema.org',
        '@type'    => 'WebSite',
        'name'     => get_bloginfo('name'),
        'url'      => home_url('/'),
    );

    echo '<script type="application/ld+json">' . wp_json_encode($org_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    echo '<script type="application/ld+json">' . wp_json_encode($website_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';

    if (is_singular()) {
        $crumbs = array(
            array('@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url('/')),
        );
        if (is_singular('product')) {
            $crumbs[] = array('@type' => 'ListItem', 'position' => 2, 'name' => 'Products', 'item' => stage_lighting_get_products_page_url());
            $crumbs[] = array('@type' => 'ListItem', 'position' => 3, 'name' => get_the_title(), 'item' => get_permalink());
        } else {
            $crumbs[] = array('@type' => 'ListItem', 'position' => 2, 'name' => get_the_title(), 'item' => get_permalink());
        }
        $breadcrumb_schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $crumbs,
        );
        echo '<script type="application/ld+json">' . wp_json_encode($breadcrumb_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    }

    if (is_singular()) {
        $title = wp_get_document_title();
        $desc = '';
        if (is_singular('product') && function_exists('wc_get_product')) {
            $p = wc_get_product(get_the_ID());
            if ($p) {
                $desc = wp_strip_all_tags((string) $p->get_short_description());
            }
        }
        if (empty($desc)) {
            $desc = wp_strip_all_tags((string) get_the_excerpt(get_the_ID()));
        }
        $image = get_the_post_thumbnail_url(get_the_ID(), 'full');
        if (empty($image)) {
            $image = '';
        }
        echo '<meta property="og:title" content="' . esc_attr($title) . '">';
        echo '<meta property="og:description" content="' . esc_attr($desc) . '">';
        echo '<meta property="og:type" content="website">';
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '">';
        if (!empty($image)) {
            echo '<meta property="og:image" content="' . esc_url($image) . '">';
        }
        echo '<meta name="twitter:card" content="summary_large_image">';
        echo '<meta name="twitter:title" content="' . esc_attr($title) . '">';
        echo '<meta name="twitter:description" content="' . esc_attr($desc) . '">';
        if (!empty($image)) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '">';
        }
    }

    if (!function_exists('is_product') || !is_product() || !function_exists('wc_get_product')) {
        return;
    }

    global $product;
    if (!is_object($product) || !is_a($product, 'WC_Product')) {
        return;
    }

    $schema_product = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'Product',
        'name'        => $product->get_name(),
        'description' => wp_strip_all_tags((string) $product->get_short_description()),
        'sku'         => $product->get_sku(),
        'url'         => get_permalink($product->get_id()),
        'offers'      => array(
            '@type'         => 'Offer',
            'priceCurrency' => get_woocommerce_currency(),
            'price'         => (string) $product->get_price(),
            'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'           => get_permalink($product->get_id()),
        ),
    );

    $image_id = $product->get_image_id();
    if (!empty($image_id)) {
        $image_url = wp_get_attachment_image_url((int) $image_id, 'full');
        if (!empty($image_url)) {
            $schema_product['image'] = array($image_url);
        }
    }
    if (empty($schema_product['sku'])) {
        unset($schema_product['sku']);
    }
    if (empty($schema_product['description'])) {
        unset($schema_product['description']);
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema_product, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}
add_action('wp_head', 'stage_lighting_output_structured_data', 90);

function stage_lighting_send_verification_email($user_id) {
    $user = get_userdata($user_id);
    if (!$user || empty($user->user_email)) {
        return;
    }
    $verify_key = wp_generate_password(32, false, false);
    update_user_meta($user_id, 'stage_email_verified', '0');
    update_user_meta($user_id, 'stage_email_verify_key', $verify_key);
    $verify_url = add_query_arg(
        array(
            'stage_verify_uid' => $user_id,
            'stage_verify_key' => $verify_key,
        ),
        home_url('/')
    );
    $subject = 'Verify your account email';
    $message = "Hi {$user->display_name},\n\nPlease verify your email by opening this link:\n{$verify_url}\n\nIf you did not create this account, please ignore this email.";
    wp_mail($user->user_email, $subject, $message);
}

function stage_lighting_after_user_register($user_id) {
    stage_lighting_send_verification_email((int) $user_id);
}
add_action('user_register', 'stage_lighting_after_user_register');

function stage_lighting_handle_email_verify() {
    if (empty($_GET['stage_verify_uid']) || empty($_GET['stage_verify_key'])) {
        return;
    }
    $uid = (int) wp_unslash($_GET['stage_verify_uid']);
    $key = sanitize_text_field(wp_unslash($_GET['stage_verify_key']));
    if ($uid <= 0 || empty($key)) {
        wp_safe_redirect(add_query_arg('verified', 'invalid', wp_login_url()));
        exit;
    }
    $saved = (string) get_user_meta($uid, 'stage_email_verify_key', true);
    if (!hash_equals($saved, $key)) {
        wp_safe_redirect(add_query_arg('verified', 'invalid', wp_login_url()));
        exit;
    }
    update_user_meta($uid, 'stage_email_verified', '1');
    delete_user_meta($uid, 'stage_email_verify_key');
    $target = home_url('/my-account/');
    if (function_exists('wc_get_page_permalink')) {
        $wc_target = wc_get_page_permalink('myaccount');
        if (!empty($wc_target)) {
            $target = $wc_target;
        }
    }
    wp_safe_redirect(add_query_arg('verified', 'success', $target));
    exit;
}
add_action('init', 'stage_lighting_handle_email_verify');

function stage_lighting_require_email_verified($user) {
    if (!($user instanceof WP_User)) {
        return $user;
    }
    $verified = get_user_meta($user->ID, 'stage_email_verified', true);
    if ('' === (string) $verified) {
        return $user;
    }
    if ('1' !== (string) $verified) {
        return new WP_Error(
            'stage_email_not_verified',
            __('Please verify your email before logging in. Check your inbox for activation link.', 'stage-lighting')
        );
    }
    return $user;
}
add_filter('wp_authenticate_user', 'stage_lighting_require_email_verified');


