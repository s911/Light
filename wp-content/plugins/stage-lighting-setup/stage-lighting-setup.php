<?php
/**
 * Plugin Name: Stage Lighting Site Setup
 * Description: One-click initializer for pages, menus, WooCommerce categories, and sample data.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function stage_setup_homepage_module_defaults() {
    return array(
        'hero'            => '1',
        'hot_products'    => '1',
        'categories'      => '1',
        'solutions'       => '1',
        'projects'        => '1',
        'why_choose_us'   => '1',
        'partner_logos'   => '1',
        'blog'            => '1',
        'newsletter'      => '1',
    );
}

function stage_setup_get_homepage_modules() {
    $saved = get_option('stage_homepage_modules', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    return wp_parse_args($saved, stage_setup_homepage_module_defaults());
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
        'compare' => array(
            'title'   => 'Product Compare',
            'slug'    => 'product-compare',
            'content' => 'Compare selected products side by side.',
            'page_template' => 'page-compare.php',
        ),
        'wishlist' => array(
            'title'   => 'Wishlist',
            'slug'    => 'wishlist',
            'content' => 'Saved products for future inquiry or purchase.',
            'page_template' => 'page-wishlist.php',
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
        'order_tracking' => array(
            'title'   => 'Order Tracking',
            'slug'    => 'order-tracking',
            'content' => 'Track your order and shipment status.',
            'page_template' => 'page-order-tracking.php',
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
    stage_setup_seed_demo_products();
}

function stage_setup_seed_sample_project() {
    stage_setup_seed_demo_projects();
}

function stage_setup_seed_sample_download() {
    stage_setup_seed_demo_downloads();
}

function stage_setup_seed_download_types() {
    if (!taxonomy_exists('download_type')) {
        return;
    }
    $types = array('Manual', 'Certificate', 'IES');
    foreach ($types as $type_name) {
        if (!term_exists($type_name, 'download_type')) {
            wp_insert_term($type_name, 'download_type');
        }
    }
}

function stage_setup_seed_sample_blog_post() {
    stage_setup_seed_demo_blog_posts();
}

function stage_setup_upsert_post_by_title($post_type, $title, $content, $excerpt = '') {
    $existing = get_page_by_title($title, OBJECT, $post_type);
    $payload = array(
        'post_title'   => $title,
        'post_content' => $content,
        'post_excerpt' => $excerpt,
        'post_type'    => $post_type,
        'post_status'  => 'publish',
    );
    if ($existing instanceof WP_Post) {
        $payload['ID'] = $existing->ID;
        return wp_update_post($payload);
    }
    return wp_insert_post($payload);
}

function stage_setup_mark_demo_post($post_id) {
    if (!$post_id || is_wp_error($post_id)) {
        return;
    }
    update_post_meta((int) $post_id, '_stage_is_demo_content', '1');
}

function stage_setup_seed_demo_products() {
    if (!post_type_exists('product') || !function_exists('wc_get_product')) {
        return;
    }
    $products = array(
        array('title' => 'Beam Moving Head 350W', 'price' => '599', 'cat' => 'Stage Lighting', 'tag' => 'Concert & Touring', 'power' => '350W'),
        array('title' => 'Beam Moving Head 440W Pro', 'price' => '899', 'cat' => 'Stage Lighting', 'tag' => 'Concert & Touring', 'power' => '440W'),
        array('title' => 'LED Wash Zoom 19x40W', 'price' => '529', 'cat' => 'Stage Lighting', 'tag' => 'Theater & Auditorium', 'power' => '200W'),
        array('title' => 'LED Par 18x12W RGBAW', 'price' => '149', 'cat' => 'Stage Lighting', 'tag' => 'Wedding & Events', 'power' => '200W'),
        array('title' => 'Follow Spot 1000W', 'price' => '769', 'cat' => 'Stage Lighting', 'tag' => 'Theater & Auditorium', 'power' => '1000W'),
        array('title' => 'DMX Console 1024ch', 'price' => '699', 'cat' => 'Lighting Control', 'tag' => 'TV Studio', 'power' => '200W'),
        array('title' => 'ArtNet Node 8 Port', 'price' => '239', 'cat' => 'Lighting Control', 'tag' => 'Architectural Lighting', 'power' => '200W'),
        array('title' => 'Haze Machine 1500W', 'price' => '319', 'cat' => 'Special Effects', 'tag' => 'Nightclub & Bar', 'power' => '1000W'),
        array('title' => 'Fog Machine Vertical Jet 3000W', 'price' => '449', 'cat' => 'Special Effects', 'tag' => 'DJ & Party', 'power' => '1000W'),
        array('title' => 'Stage Truss 290mm 2m', 'price' => '129', 'cat' => 'Stage Structure', 'tag' => 'Concert & Touring', 'power' => '200W'),
        array('title' => 'Power Cable Set 20m', 'price' => '59', 'cat' => 'Cables & Accessories', 'tag' => 'Architectural Lighting', 'power' => '200W'),
        array('title' => 'Signal Splitter 8-way', 'price' => '89', 'cat' => 'Cables & Accessories', 'tag' => 'TV Studio', 'power' => '200W'),
    );

    foreach ($products as $index => $item) {
        $title = (string) $item['title'];
        $content = 'Demo product data for visual review and operation rehearsal. Replace with real SKU specs before go-live.';
        $excerpt = 'Test SKU for storefront layout and filtering preview.';
        $product_id = stage_setup_upsert_post_by_title('product', $title, $content, $excerpt);
        if (!$product_id || is_wp_error($product_id)) {
            continue;
        }
        stage_setup_mark_demo_post($product_id);
        wp_set_object_terms($product_id, (string) $item['cat'], 'product_cat');
        wp_set_object_terms($product_id, (string) $item['tag'], 'product_tag', true);
        if (taxonomy_exists('pa_power')) {
            wp_set_object_terms($product_id, (string) $item['power'], 'pa_power', false);
        }
        update_post_meta($product_id, '_regular_price', (string) $item['price']);
        update_post_meta($product_id, '_price', (string) $item['price']);
        update_post_meta($product_id, '_stock_status', 'instock');
        update_post_meta($product_id, 'total_sales', (string) (20 + ($index * 7)));
        update_post_meta($product_id, '_wc_average_rating', (string) (4 + (($index % 9) / 10)));
        update_post_meta($product_id, 'stage_download_links', "Manual::https://example.com/manuals/" . sanitize_title($title) . ".pdf\nCertificate::https://example.com/certs/" . sanitize_title($title) . "-ce.pdf");
        update_post_meta($product_id, 'stage_video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
    }
}

function stage_setup_seed_demo_projects() {
    if (!post_type_exists('project')) {
        return;
    }
    $projects = array(
        array('title' => 'Arena Touring Lighting Upgrade', 'client' => 'XYZ Festival Organizer', 'venue' => 'Concert & Touring', 'country' => 'USA'),
        array('title' => 'Broadway Theater Retrofit', 'client' => 'Central City Theater', 'venue' => 'Theater & Auditorium', 'country' => 'UK'),
        array('title' => 'TV Studio RGBW Rebuild', 'client' => 'Vision Media Group', 'venue' => 'TV Studio', 'country' => 'Singapore'),
        array('title' => 'Nightclub Smart Lighting Revamp', 'client' => 'Pulse Club', 'venue' => 'Nightclub & Bar', 'country' => 'Germany'),
        array('title' => 'Wedding Venue Seasonal Setup', 'client' => 'Lakeview Resort', 'venue' => 'Wedding & Events', 'country' => 'Italy'),
        array('title' => 'Museum Facade Dynamic Illumination', 'client' => 'City Art Center', 'venue' => 'Architectural Lighting', 'country' => 'UAE'),
    );
    foreach ($projects as $item) {
        $title = (string) $item['title'];
        $project_id = stage_setup_upsert_post_by_title(
            'project',
            $title,
            'Demo project case with planning, fixture package, and execution summary for presentation purpose.',
            'Sample project for portfolio and trust-building section.'
        );
        if (!$project_id || is_wp_error($project_id)) {
            continue;
        }
        stage_setup_mark_demo_post($project_id);
        update_post_meta($project_id, 'project_client', (string) $item['client']);
        update_post_meta($project_id, 'project_venue_type', (string) $item['venue']);
        update_post_meta($project_id, 'project_country', (string) $item['country']);
        update_post_meta($project_id, 'project_used_products', 'Beam Moving Head 350W, LED Wash Zoom 19x40W, DMX Console 1024ch');
        update_post_meta($project_id, 'project_results', 'Demo KPI: 30% faster setup, stable show run, positive audience feedback.');
    }
}

function stage_setup_seed_demo_downloads() {
    if (!post_type_exists('download')) {
        return;
    }
    $downloads = array(
        array('title' => 'Beam 350W Product Manual', 'type' => 'Manual'),
        array('title' => 'Beam 440W Product Manual', 'type' => 'Manual'),
        array('title' => 'LED Wash Zoom Quick Start', 'type' => 'Manual'),
        array('title' => 'CE Compliance Certificate Pack', 'type' => 'Certificate'),
        array('title' => 'RoHS Compliance Statement', 'type' => 'Certificate'),
        array('title' => 'Arena Lighting IES Bundle', 'type' => 'IES'),
    );
    foreach ($downloads as $item) {
        $title = (string) $item['title'];
        $download_id = stage_setup_upsert_post_by_title(
            'download',
            $title,
            'Demo file entry for downloads center visual preview.',
            'Sample download item.'
        );
        if (!$download_id || is_wp_error($download_id)) {
            continue;
        }
        stage_setup_mark_demo_post($download_id);
        update_post_meta($download_id, 'download_file_url', 'https://example.com/downloads/' . sanitize_title($title) . '.pdf');
        if (taxonomy_exists('download_type')) {
            wp_set_object_terms($download_id, (string) $item['type'], 'download_type', false);
        }
    }
}

function stage_setup_seed_demo_blog_posts() {
    $posts = array(
        'How to Choose the Right Moving Head Light',
        'Stage Lighting Layout for 500-Seat Theater',
        'DMX Addressing Mistakes and How to Avoid Them',
        'Beam vs Wash vs Spot: Practical Selection Guide',
        'Touring Show Power Planning Checklist',
        'How to Build a Reliable Rental Fixture Pool',
        'Energy Saving Tips for Permanent Installations',
        'Maintenance SOP for High-Use Stage Fixtures',
    );
    foreach ($posts as $title) {
        $blog_id = stage_setup_upsert_post_by_title(
            'post',
            (string) $title,
            'This is demo blog content for operations preview. Replace this article with your SEO-focused production content before launch.',
            'Demo article used to show blog list and detail pages.'
        );
        stage_setup_mark_demo_post($blog_id);
    }
}

function stage_setup_seed_demo_content() {
    stage_setup_seed_demo_products();
    stage_setup_seed_demo_projects();
    stage_setup_seed_demo_downloads();
    stage_setup_seed_demo_blog_posts();
}

function stage_setup_clear_demo_content() {
    $target_types = array('product', 'project', 'download', 'post');
    $deleted_count = 0;
    foreach ($target_types as $post_type) {
        $query = new WP_Query(
            array(
                'post_type'      => $post_type,
                'post_status'    => 'any',
                'posts_per_page' => 300,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => '_stage_is_demo_content',
                        'value' => '1',
                    ),
                ),
            )
        );
        if (empty($query->posts)) {
            continue;
        }
        foreach ($query->posts as $post_id) {
            $deleted = wp_delete_post((int) $post_id, true);
            if ($deleted) {
                $deleted_count++;
            }
        }
    }
    return $deleted_count;
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

    $primary_items = array('home', 'products', 'solutions', 'projects', 'for_business', 'about', 'blog', 'contact');
    $footer_items = array('for_business', 'oem_odm', 'downloads', 'order_tracking', 'contact');
    stage_setup_sync_menu_items($primary_menu_id, $primary_items, $page_ids);
    stage_setup_sync_menu_items($footer_menu_id, $footer_items, $page_ids);

    $locations['primary'] = (int) $primary_menu_id;
    $locations['footer']  = (int) $footer_menu_id;
    set_theme_mod('nav_menu_locations', $locations);
}

function stage_setup_sync_menu_items($menu_id, $item_keys, $page_ids) {
    $desired_page_ids = array();
    foreach ($item_keys as $key) {
        if (!empty($page_ids[$key]) && !is_wp_error($page_ids[$key])) {
            $desired_page_ids[] = (int) $page_ids[$key];
        }
    }
    $desired_page_ids = array_values(array_unique($desired_page_ids));

    $existing = wp_get_nav_menu_items($menu_id, array('post_status' => 'any'));
    $seen_page_ids = array();

    if (is_array($existing)) {
        foreach ($existing as $menu_item) {
            $object_id = (int) $menu_item->object_id;
            $is_target_page_item = ('post_type' === $menu_item->type && 'page' === $menu_item->object);

            if (!$is_target_page_item) {
                continue;
            }

            if (!in_array($object_id, $desired_page_ids, true)) {
                wp_delete_post($menu_item->ID, true);
                continue;
            }

            if (in_array($object_id, $seen_page_ids, true)) {
                wp_delete_post($menu_item->ID, true);
                continue;
            }

            $seen_page_ids[] = $object_id;
        }
    }

    foreach ($desired_page_ids as $page_id) {
        if (in_array($page_id, $seen_page_ids, true)) {
            continue;
        }
        wp_update_nav_menu_item(
            $menu_id,
            0,
            array(
                'menu-item-object-id' => $page_id,
                'menu-item-object'    => 'page',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => 'publish',
            )
        );
    }
}

function stage_setup_run_initializer() {
    $page_ids = stage_setup_seed_pages();
    stage_setup_seed_product_taxonomies();
    stage_setup_seed_woocommerce_attributes();
    stage_setup_seed_download_types();
    stage_setup_seed_demo_content();
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

    $clear_notice = '';
    if (isset($_POST['stage_setup_run']) && check_admin_referer('stage_setup_action', 'stage_setup_nonce')) {
        stage_setup_run_initializer();
        echo '<div class="notice notice-success"><p>Initialization completed.</p></div>';
    }
    if (isset($_POST['stage_setup_clear_demo']) && check_admin_referer('stage_setup_clear_demo_action', 'stage_setup_clear_demo_nonce')) {
        $deleted_count = stage_setup_clear_demo_content();
        $clear_notice = sprintf('Demo content cleanup completed. Deleted %d posts.', (int) $deleted_count);
    }
    if (isset($_POST['stage_home_modules_save']) && check_admin_referer('stage_home_modules_action', 'stage_home_modules_nonce')) {
        $defaults = stage_setup_homepage_module_defaults();
        $new = array();
        foreach ($defaults as $key => $default_val) {
            $new[$key] = isset($_POST['stage_home_module'][$key]) ? '1' : '0';
        }
        update_option('stage_homepage_modules', $new);
        echo '<div class="notice notice-success"><p>Homepage module settings saved.</p></div>';
    }
    $modules = stage_setup_get_homepage_modules();
    ?>
    <div class="wrap">
        <h1>Stage Lighting One-Click Setup</h1>
        <?php if (!empty($clear_notice)) : ?>
            <div class="notice notice-info"><p><?php echo esc_html($clear_notice); ?></p></div>
        <?php endif; ?>
        <p>Creates pages, menus, WooCommerce product taxonomy, and bulk demo content (products/projects/downloads/blog) for UI preview.</p>
        <form method="post">
            <?php wp_nonce_field('stage_setup_action', 'stage_setup_nonce'); ?>
            <p><button class="button button-primary" type="submit" name="stage_setup_run" value="1">Run Initialization</button></p>
        </form>
        <form method="post" style="margin-top:10px;">
            <?php wp_nonce_field('stage_setup_clear_demo_action', 'stage_setup_clear_demo_nonce'); ?>
            <p><button class="button button-secondary" type="submit" name="stage_setup_clear_demo" value="1">Clear Demo Content</button></p>
            <p class="description">Only deletes posts marked as demo content by this setup tool.</p>
        </form>
        <hr>
        <h2>Homepage Module Switches</h2>
        <p>Toggle front page sections without editing template code.</p>
        <form method="post">
            <?php wp_nonce_field('stage_home_modules_action', 'stage_home_modules_nonce'); ?>
            <table class="form-table" role="presentation">
                <?php foreach ($modules as $key => $enabled) : ?>
                    <tr>
                        <th scope="row"><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="stage_home_module[<?php echo esc_attr($key); ?>]" value="1" <?php checked('1', (string) $enabled); ?>>
                                Enabled
                            </label>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <p><button class="button button-secondary" type="submit" name="stage_home_modules_save" value="1">Save Homepage Modules</button></p>
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

function stage_setup_register_project_meta_box() {
    if (!post_type_exists('project')) {
        return;
    }
    add_meta_box(
        'stage_project_details',
        'Project Details',
        'stage_setup_render_project_meta_box',
        'project',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'stage_setup_register_project_meta_box');

function stage_setup_render_project_meta_box($post) {
    wp_nonce_field('stage_save_project_details', 'stage_project_details_nonce');
    $client = get_post_meta($post->ID, 'project_client', true);
    $venue = get_post_meta($post->ID, 'project_venue_type', true);
    $country = get_post_meta($post->ID, 'project_country', true);
    $products = get_post_meta($post->ID, 'project_used_products', true);
    $results = get_post_meta($post->ID, 'project_results', true);
    ?>
    <p><label>Client Name</label><br><input type="text" name="project_client" value="<?php echo esc_attr((string) $client); ?>" style="width:100%;"></p>
    <p><label>Venue Type</label><br><input type="text" name="project_venue_type" value="<?php echo esc_attr((string) $venue); ?>" style="width:100%;"></p>
    <p><label>Country</label><br><input type="text" name="project_country" value="<?php echo esc_attr((string) $country); ?>" style="width:100%;"></p>
    <p><label>Used Products</label><br><textarea name="project_used_products" rows="3" style="width:100%;"><?php echo esc_textarea((string) $products); ?></textarea></p>
    <p><label>Project Results</label><br><textarea name="project_results" rows="4" style="width:100%;"><?php echo esc_textarea((string) $results); ?></textarea></p>
    <?php
}

function stage_setup_register_download_meta_box() {
    if (!post_type_exists('download')) {
        return;
    }
    add_meta_box(
        'stage_download_details',
        'Download Details',
        'stage_setup_render_download_meta_box',
        'download',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'stage_setup_register_download_meta_box');

function stage_setup_render_download_meta_box($post) {
    wp_nonce_field('stage_save_download_details', 'stage_download_details_nonce');
    $file_url = get_post_meta($post->ID, 'download_file_url', true);
    ?>
    <p><label>File URL</label><br><input type="url" name="download_file_url" value="<?php echo esc_attr((string) $file_url); ?>" style="width:100%;" placeholder="https://example.com/file.pdf"></p>
    <p>Assign type with taxonomy on right panel: Manual / Certificate / IES</p>
    <?php
}

function stage_setup_render_product_meta_box($post) {
    wp_nonce_field('stage_save_product_downloads', 'stage_product_downloads_nonce');
    $value = get_post_meta($post->ID, 'stage_download_links', true);
    $video_url = get_post_meta($post->ID, 'stage_video_url', true);
    ?>
    <p>Add video URL (YouTube/Vimeo) for product detail page.</p>
    <input
        name="stage_video_url"
        type="url"
        style="width:100%;margin-bottom:10px;"
        placeholder="https://www.youtube.com/watch?v=..."
        value="<?php echo esc_attr((string) $video_url); ?>"
    >
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

    $video_raw = isset($_POST['stage_video_url']) ? wp_unslash($_POST['stage_video_url']) : '';
    update_post_meta($post_id, 'stage_video_url', esc_url_raw(trim((string) $video_raw)));
}
add_action('save_post', 'stage_setup_save_product_meta_box');

function stage_setup_save_project_meta_box($post_id) {
    if (!isset($_POST['stage_project_details_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_project_details_nonce'])), 'stage_save_project_details')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id) || get_post_type($post_id) !== 'project') {
        return;
    }
    update_post_meta($post_id, 'project_client', sanitize_text_field(wp_unslash($_POST['project_client'] ?? '')));
    update_post_meta($post_id, 'project_venue_type', sanitize_text_field(wp_unslash($_POST['project_venue_type'] ?? '')));
    update_post_meta($post_id, 'project_country', sanitize_text_field(wp_unslash($_POST['project_country'] ?? '')));
    update_post_meta($post_id, 'project_used_products', sanitize_textarea_field(wp_unslash($_POST['project_used_products'] ?? '')));
    update_post_meta($post_id, 'project_results', sanitize_textarea_field(wp_unslash($_POST['project_results'] ?? '')));
}
add_action('save_post', 'stage_setup_save_project_meta_box');

function stage_setup_save_download_meta_box($post_id) {
    if (!isset($_POST['stage_download_details_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_download_details_nonce'])), 'stage_save_download_details')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id) || get_post_type($post_id) !== 'download') {
        return;
    }
    update_post_meta($post_id, 'download_file_url', esc_url_raw(trim((string) wp_unslash($_POST['download_file_url'] ?? ''))));
}
add_action('save_post', 'stage_setup_save_download_meta_box');

function stage_setup_admin_product_columns($columns) {
    $new_columns = array();
    foreach ($columns as $key => $label) {
        $new_columns[$key] = $label;
        if ('name' === $key) {
            $new_columns['stage_power'] = 'Power';
            $new_columns['stage_price'] = 'Price (USD)';
            $new_columns['stage_video'] = 'Video';
        }
    }
    return $new_columns;
}
add_filter('manage_edit-product_columns', 'stage_setup_admin_product_columns');

function stage_setup_admin_product_column_content($column, $post_id) {
    if ('stage_power' === $column) {
        $terms = get_the_terms($post_id, 'pa_power');
        if (is_array($terms) && !empty($terms)) {
            echo esc_html(implode(', ', wp_list_pluck($terms, 'name')));
        } else {
            echo '-';
        }
        return;
    }
    if ('stage_price' === $column) {
        $price = get_post_meta($post_id, '_price', true);
        echo '' !== (string) $price ? esc_html((string) $price) : '-';
        return;
    }
    if ('stage_video' === $column) {
        $video = get_post_meta($post_id, 'stage_video_url', true);
        if (!empty($video)) {
            echo '<a href="' . esc_url((string) $video) . '" target="_blank" rel="noopener">View</a>';
        } else {
            echo '-';
        }
    }
}
add_action('manage_product_posts_custom_column', 'stage_setup_admin_product_column_content', 10, 2);

function stage_setup_admin_product_filters() {
    global $typenow;
    if ('product' !== $typenow || !taxonomy_exists('pa_power')) {
        return;
    }
    $selected = isset($_GET['stage_power_filter']) ? sanitize_text_field(wp_unslash($_GET['stage_power_filter'])) : '';
    $terms = get_terms(
        array(
            'taxonomy'   => 'pa_power',
            'hide_empty' => false,
        )
    );
    if (is_wp_error($terms) || empty($terms)) {
        return;
    }
    echo '<select name="stage_power_filter">';
    echo '<option value="">All Power</option>';
    foreach ($terms as $term) {
        echo '<option value="' . esc_attr((string) $term->slug) . '" ' . selected($selected, (string) $term->slug, false) . '>' . esc_html((string) $term->name) . '</option>';
    }
    echo '</select>';
}
add_action('restrict_manage_posts', 'stage_setup_admin_product_filters');

function stage_setup_admin_product_filter_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    $post_type = isset($_GET['post_type']) ? sanitize_text_field(wp_unslash($_GET['post_type'])) : '';
    if ('product' !== $post_type) {
        return;
    }
    $power = isset($_GET['stage_power_filter']) ? sanitize_text_field(wp_unslash($_GET['stage_power_filter'])) : '';
    if (empty($power) || !taxonomy_exists('pa_power')) {
        return;
    }
    $tax_query = (array) $query->get('tax_query');
    $tax_query[] = array(
        'taxonomy' => 'pa_power',
        'field'    => 'slug',
        'terms'    => array($power),
    );
    $query->set('tax_query', $tax_query);
}
add_action('pre_get_posts', 'stage_setup_admin_product_filter_query');

function stage_setup_admin_product_row_actions($actions, $post) {
    if (!($post instanceof WP_Post) || 'product' !== $post->post_type) {
        return $actions;
    }
    if (!current_user_can('edit_post', $post->ID)) {
        return $actions;
    }
    $url = wp_nonce_url(
        admin_url('admin.php?action=stage_duplicate_product&post=' . (int) $post->ID),
        'stage_duplicate_product_' . (int) $post->ID
    );
    $actions['stage_duplicate'] = '<a href="' . esc_url($url) . '">Duplicate</a>';
    return $actions;
}
add_filter('post_row_actions', 'stage_setup_admin_product_row_actions', 20, 2);

function stage_setup_handle_duplicate_product() {
    if (!is_admin()) {
        wp_die('Invalid request.');
    }
    $source_id = isset($_GET['post']) ? (int) wp_unslash($_GET['post']) : 0;
    if ($source_id <= 0 || !current_user_can('edit_post', $source_id)) {
        wp_die('Permission denied.');
    }
    check_admin_referer('stage_duplicate_product_' . $source_id);
    $source = get_post($source_id);
    if (!($source instanceof WP_Post) || 'product' !== $source->post_type) {
        wp_die('Source product not found.');
    }

    $new_id = wp_insert_post(
        array(
            'post_type'    => 'product',
            'post_status'  => 'draft',
            'post_title'   => $source->post_title . ' (Copy)',
            'post_content' => $source->post_content,
            'post_excerpt' => $source->post_excerpt,
        )
    );
    if (!$new_id || is_wp_error($new_id)) {
        wp_die('Failed to duplicate product.');
    }

    $meta = get_post_meta($source_id);
    foreach ($meta as $key => $values) {
        if ('_edit_lock' === $key || '_edit_last' === $key) {
            continue;
        }
        delete_post_meta($new_id, $key);
        foreach ((array) $values as $value) {
            add_post_meta($new_id, $key, maybe_unserialize($value));
        }
    }

    $taxonomies = get_object_taxonomies('product');
    foreach ($taxonomies as $taxonomy) {
        $terms = wp_get_object_terms($source_id, $taxonomy, array('fields' => 'slugs'));
        if (!is_wp_error($terms)) {
            wp_set_object_terms($new_id, $terms, $taxonomy, false);
        }
    }

    wp_safe_redirect(
        add_query_arg(
            array(
                'post'   => $new_id,
                'action' => 'edit',
            ),
            admin_url('post.php')
        )
    );
    exit;
}
add_action('admin_action_stage_duplicate_product', 'stage_setup_handle_duplicate_product');

