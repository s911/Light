<?php
/**
 * Plugin Name: Stage Lighting Site Setup
 * Description: One-click initializer for pages, menus, WooCommerce categories, and sample data.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function stage_setup_get_page_config() {
    return array(
        'home' => array(
            'title'   => 'Home',
            'slug'    => 'home',
            'content' => 'Homepage content is managed by theme sections.',
        ),
        'products' => array(
            'title'   => 'Products',
            'slug'    => 'products',
            'content' => 'Browse our full stage lighting catalog.',
            'page_template' => 'page-products.php',
        ),
        'solutions' => array(
            'title'   => 'Solutions',
            'slug'    => 'solutions',
            'content' => 'Find lighting solutions by venue and application.',
            'page_template' => 'page-solutions.php',
        ),
        'projects' => array(
            'title'   => 'Projects',
            'slug'    => 'projects',
            'content' => 'Case studies and completed event projects.',
            'page_template' => 'page-projects.php',
        ),
        'for_business' => array(
            'title'       => 'For Business',
            'slug'        => 'for-business',
            'content'     => '[stage_bulk_quote_form]',
            'page_template' => 'page-for-business.php',
        ),
        'oem_odm' => array(
            'title'   => 'OEM / ODM',
            'slug'    => 'oem-odm',
            'content' => 'Custom engineering, private labeling and project support.',
        ),
        'downloads' => array(
            'title'   => 'Downloads',
            'slug'    => 'downloads-center',
            'content' => 'Product manuals, certificates and IES files.',
            'page_template' => 'page-downloads.php',
        ),
        'about' => array(
            'title'   => 'About Us',
            'slug'    => 'about-us',
            'content' => 'Company profile, factory capability and quality control.',
        ),
        'blog' => array(
            'title'   => 'Blog',
            'slug'    => 'blog',
            'content' => 'Lighting guides, industry trends and setup tutorials.',
            'page_template' => 'page-blog.php',
        ),
        'contact' => array(
            'title'   => 'Contact',
            'slug'    => 'contact',
            'content' => 'Get in touch for retail and distribution inquiries.',
        ),
    );
}

function stage_setup_upsert_page($title, $slug, $content, $template = '') {
    $existing = get_page_by_path($slug);
    $page_data = array(
        'post_title'   => $title,
        'post_name'    => $slug,
        'post_type'    => 'page',
        'post_status'  => 'publish',
        'post_content' => $content,
    );

    if ($existing instanceof WP_Post) {
        $page_data['ID'] = $existing->ID;
        $page_id = wp_update_post($page_data);
    } else {
        $page_id = wp_insert_post($page_data);
    }

    if ($page_id && !is_wp_error($page_id) && !empty($template)) {
        update_post_meta($page_id, '_wp_page_template', $template);
    }

    return $page_id;
}

function stage_setup_seed_pages() {
    $config = stage_setup_get_page_config();
    $ids = array();

    foreach ($config as $key => $page) {
        $ids[$key] = stage_setup_upsert_page(
            $page['title'],
            $page['slug'],
            $page['content'],
            $page['page_template'] ?? ''
        );
    }

    if (!empty($ids['home']) && !is_wp_error($ids['home'])) {
        update_option('show_on_front', 'page');
        update_option('page_on_front', (int) $ids['home']);
    }

    return $ids;
}

function stage_setup_seed_product_taxonomies() {
    if (!taxonomy_exists('product_cat')) {
        return;
    }

    $cats = array(
        'Stage Lighting',
        'Lighting Control',
        'Stage Structure',
        'Special Effects',
        'Cables & Accessories',
    );

    foreach ($cats as $cat_name) {
        if (!term_exists($cat_name, 'product_cat')) {
            wp_insert_term($cat_name, 'product_cat');
        }
    }

    $solutions = array(
        'Concert & Touring',
        'Theater & Auditorium',
        'TV Studio',
        'Nightclub & Bar',
        'Wedding & Events',
        'Architectural Lighting',
        'DJ & Party',
    );

    foreach ($solutions as $tag_name) {
        if (!term_exists($tag_name, 'product_tag')) {
            wp_insert_term($tag_name, 'product_tag');
        }
    }
}

function stage_setup_seed_woocommerce_attributes() {
    if (!function_exists('wc_create_attribute')) {
        return;
    }

    $attributes = array(
        array('name' => 'Power', 'slug' => 'power'),
        array('name' => 'Application', 'slug' => 'application'),
        array('name' => 'Control Protocol', 'slug' => 'control-protocol'),
        array('name' => 'Certification', 'slug' => 'certification'),
    );

    foreach ($attributes as $attr) {
        if (!taxonomy_exists('pa_' . $attr['slug'])) {
            wc_create_attribute(
                array(
                    'name' => $attr['name'],
                    'slug' => $attr['slug'],
                    'type' => 'select',
                )
            );
        }
    }

    $power_terms = array('200W', '350W', '440W', '1000W');
    if (taxonomy_exists('pa_power')) {
        foreach ($power_terms as $term_name) {
            if (!term_exists($term_name, 'pa_power')) {
                wp_insert_term($term_name, 'pa_power');
            }
        }
    }
}

function stage_setup_seed_sample_product() {
    if (!post_type_exists('product') || !function_exists('wc_get_product')) {
        return;
    }

    $existing = get_page_by_title('Beam Moving Head 350W', OBJECT, 'product');
    if ($existing instanceof WP_Post) {
        $product_id = $existing->ID;
    } else {
        $product_id = wp_insert_post(
            array(
                'post_title'   => 'Beam Moving Head 350W',
                'post_content' => 'High-intensity moving head beam light for concerts and touring setups.',
                'post_excerpt' => 'Professional 350W beam fixture for stage performance.',
                'post_type'    => 'product',
                'post_status'  => 'publish',
            )
        );
    }

    if (!$product_id || is_wp_error($product_id)) {
        return;
    }

    wp_set_object_terms($product_id, 'Stage Lighting', 'product_cat');
    wp_set_object_terms($product_id, 'Concert & Touring', 'product_tag', true);
    if (taxonomy_exists('pa_power')) {
        wp_set_object_terms($product_id, '350W', 'pa_power', false);
    }
    update_post_meta($product_id, '_regular_price', '599');
    update_post_meta($product_id, '_price', '599');
    update_post_meta($product_id, '_stock_status', 'instock');
    update_post_meta($product_id, 'stage_download_links', "https://example.com/manuals/beam-350w.pdf\nhttps://example.com/certs/beam-350w-ce.pdf");
}

function stage_setup_seed_sample_project() {
    if (!post_type_exists('project')) {
        return;
    }
    $existing = get_page_by_title('Arena Touring Lighting Upgrade', OBJECT, 'project');
    if ($existing instanceof WP_Post) {
        return;
    }

    wp_insert_post(
        array(
            'post_title'   => 'Arena Touring Lighting Upgrade',
            'post_content' => 'Complete beam, wash, and control system delivery for a 20-city tour.',
            'post_excerpt' => 'Touring deployment with high-output moving heads and DMX control integration.',
            'post_type'    => 'project',
            'post_status'  => 'publish',
        )
    );
}

function stage_setup_seed_sample_download() {
    if (!post_type_exists('download')) {
        return;
    }
    $existing = get_page_by_title('Beam 350W Product Manual', OBJECT, 'download');
    if ($existing instanceof WP_Post) {
        return;
    }

    wp_insert_post(
        array(
            'post_title'   => 'Beam 350W Product Manual',
            'post_content' => 'Sample document entry. Replace with actual PDF/IES/certification assets.',
            'post_excerpt' => 'Technical documentation and operation instructions.',
            'post_type'    => 'download',
            'post_status'  => 'publish',
        )
    );
}

function stage_setup_seed_sample_blog_post() {
    $existing = get_page_by_title('How to Choose the Right Moving Head Light', OBJECT, 'post');
    if ($existing instanceof WP_Post) {
        return;
    }
    wp_insert_post(
        array(
            'post_title'   => 'How to Choose the Right Moving Head Light',
            'post_content' => 'A starter guide for fixture power, beam angle, venue size and DMX control planning.',
            'post_excerpt' => 'Practical checklist for selecting stage moving head fixtures.',
            'post_type'    => 'post',
            'post_status'  => 'publish',
        )
    );
}

function stage_setup_create_menus($page_ids) {
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) {
        $locations = array();
    }

    $primary_menu_name = 'Primary Menu';
    $footer_menu_name  = 'Footer Menu';
    $primary_menu_id   = wp_get_nav_menu_object($primary_menu_name);
    $footer_menu_id    = wp_get_nav_menu_object($footer_menu_name);

    if (!$primary_menu_id) {
        $primary_menu_id = wp_create_nav_menu($primary_menu_name);
    } else {
        $primary_menu_id = $primary_menu_id->term_id;
    }

    if (!$footer_menu_id) {
        $footer_menu_id = wp_create_nav_menu($footer_menu_name);
    } else {
        $footer_menu_id = $footer_menu_id->term_id;
    }

    $existing_primary_items = wp_get_nav_menu_items($primary_menu_id);
    if (is_array($existing_primary_items)) {
        foreach ($existing_primary_items as $menu_item) {
            wp_delete_post($menu_item->ID, true);
        }
    }
    $existing_footer_items = wp_get_nav_menu_items($footer_menu_id);
    if (is_array($existing_footer_items)) {
        foreach ($existing_footer_items as $menu_item) {
            wp_delete_post($menu_item->ID, true);
        }
    }

    $primary_items = array('home', 'products', 'solutions', 'projects', 'for_business', 'about', 'blog', 'contact');
    foreach ($primary_items as $key) {
        if (empty($page_ids[$key]) || is_wp_error($page_ids[$key])) {
            continue;
        }
        wp_update_nav_menu_item(
            $primary_menu_id,
            0,
            array(
                'menu-item-object-id' => (int) $page_ids[$key],
                'menu-item-object'    => 'page',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
            )
        );
    }

    $footer_items = array('for_business', 'oem_odm', 'downloads', 'contact');
    foreach ($footer_items as $key) {
        if (empty($page_ids[$key]) || is_wp_error($page_ids[$key])) {
            continue;
        }
        wp_update_nav_menu_item(
            $footer_menu_id,
            0,
            array(
                'menu-item-object-id' => (int) $page_ids[$key],
                'menu-item-object'    => 'page',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
            )
        );
    }

    $locations['primary'] = (int) $primary_menu_id;
    $locations['footer']  = (int) $footer_menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}

function stage_setup_run_initializer() {
    $page_ids = stage_setup_seed_pages();
    stage_setup_seed_product_taxonomies();
    stage_setup_seed_woocommerce_attributes();
    stage_setup_seed_sample_product();
    stage_setup_seed_sample_project();
    stage_setup_seed_sample_download();
    stage_setup_seed_sample_blog_post();
    stage_setup_create_menus($page_ids);
    flush_rewrite_rules();
}

function stage_setup_admin_menu() {
    add_submenu_page(
        'tools.php',
        'Stage Lighting Setup',
        'Stage Lighting Setup',
        'manage_options',
        'stage-lighting-setup',
        'stage_setup_admin_page'
    );
}
add_action('admin_menu', 'stage_setup_admin_menu');

function stage_setup_admin_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    if (isset($_POST['stage_setup_run']) && check_admin_referer('stage_setup_action', 'stage_setup_nonce')) {
        stage_setup_run_initializer();
        echo '<div class="notice notice-success"><p>Initialization completed.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Stage Lighting One-Click Setup</h1>
        <p>Creates pages, menus, WooCommerce product categories, tags, attributes, and a sample product.</p>
        <form method="post">
            <?php wp_nonce_field('stage_setup_action', 'stage_setup_nonce'); ?>
            <p><button class="button button-primary" type="submit" name="stage_setup_run" value="1">Run Initialization</button></p>
        </form>
    </div>
    <?php
}

function stage_setup_register_product_meta_box() {
    if (!post_type_exists('product')) {
        return;
    }

    add_meta_box(
        'stage_product_downloads',
        'Product Download Links',
        'stage_setup_render_product_meta_box',
        'product',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'stage_setup_register_product_meta_box');

function stage_setup_render_product_meta_box($post) {
    wp_nonce_field('stage_save_product_downloads', 'stage_product_downloads_nonce');
    $value = get_post_meta($post->ID, 'stage_download_links', true);
    ?>
    <p>Add one file URL per line. These links are shown in the product detail "Downloads" section.</p>
    <textarea
        name="stage_download_links"
        rows="6"
        style="width:100%;"
        placeholder="https://example.com/manuals/product-manual.pdf&#10;https://example.com/certs/ce-certificate.pdf"
    ><?php echo esc_textarea((string) $value); ?></textarea>
    <?php
}

function stage_setup_save_product_meta_box($post_id) {
    if (!isset($_POST['stage_product_downloads_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_product_downloads_nonce'])), 'stage_save_product_downloads')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if (get_post_type($post_id) !== 'product') {
        return;
    }

    $raw = isset($_POST['stage_download_links']) ? wp_unslash($_POST['stage_download_links']) : '';
    $lines = preg_split('/\r\n|\r|\n/', (string) $raw);
    $clean = array();

    foreach ($lines as $line) {
        $url = esc_url_raw(trim($line));
        if (!empty($url)) {
            $clean[] = $url;
        }
    }
    update_post_meta($post_id, 'stage_download_links', implode("\n", $clean));
}
add_action('save_post', 'stage_setup_save_product_meta_box');

