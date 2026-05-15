<?php
/**
 * Plugin Name: Stage Lighting B2B Quote
 * Description: Bulk quote form shortcode for stage lighting website.
 * Version: 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function stage_b2b_get_settings() {
    $defaults = array(
        'sales_email'      => get_option('admin_email'),
        'whatsapp_number'  => '10000000000',
        'whatsapp_display' => '+1 000 000 0000',
    );
    $saved = get_option('stage_b2b_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    return wp_parse_args($saved, $defaults);
}

function stage_b2b_get_sales_email() {
    $settings = stage_b2b_get_settings();
    return sanitize_email($settings['sales_email']);
}

function stage_b2b_get_whatsapp_number() {
    $settings = stage_b2b_get_settings();
    return preg_replace('/\D+/', '', (string) $settings['whatsapp_number']);
}

function stage_b2b_get_whatsapp_display() {
    $settings = stage_b2b_get_settings();
    return sanitize_text_field($settings['whatsapp_display']);
}

function stage_b2b_register_settings_page() {
    add_options_page(
        'Stage B2B Settings',
        'Stage B2B Settings',
        'manage_options',
        'stage-b2b-settings',
        'stage_b2b_render_settings_page'
    );
}
add_action('admin_menu', 'stage_b2b_register_settings_page');

function stage_b2b_register_settings() {
    register_setting(
        'stage_b2b_settings_group',
        'stage_b2b_settings',
        array(
            'sanitize_callback' => 'stage_b2b_sanitize_settings',
        )
    );
}
add_action('admin_init', 'stage_b2b_register_settings');

function stage_b2b_sanitize_settings($input) {
    if (!is_array($input)) {
        return stage_b2b_get_settings();
    }
    return array(
        'sales_email'      => sanitize_email($input['sales_email'] ?? ''),
        'whatsapp_number'  => preg_replace('/\D+/', '', (string) ($input['whatsapp_number'] ?? '')),
        'whatsapp_display' => sanitize_text_field($input['whatsapp_display'] ?? ''),
    );
}

function stage_b2b_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $settings = stage_b2b_get_settings();
    $manual_notice = '';
    if (
        isset($_POST['stage_b2b_send_overdue_now'])
        && isset($_POST['stage_b2b_overdue_nonce'])
        && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_b2b_overdue_nonce'])), 'stage_b2b_send_overdue_now')
    ) {
        $result = stage_b2b_send_overdue_digest(true);
        if (!empty($result['sent'])) {
            $manual_notice = sprintf('Reminder email sent. Overdue leads in digest: %d.', (int) $result['count']);
        } else {
            $manual_notice = sprintf('No reminder email sent: %s', (string) ($result['reason'] ?? 'unknown'));
        }
    }
    ?>
    <div class="wrap">
        <h1>Stage B2B Settings</h1>
        <?php if (!empty($manual_notice)) : ?>
            <div class="notice notice-info"><p><?php echo esc_html($manual_notice); ?></p></div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php settings_fields('stage_b2b_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="stage_sales_email">Sales Email</label></th>
                    <td><input id="stage_sales_email" type="email" name="stage_b2b_settings[sales_email]" class="regular-text" value="<?php echo esc_attr($settings['sales_email']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_whatsapp_number">WhatsApp Number</label></th>
                    <td><input id="stage_whatsapp_number" type="text" name="stage_b2b_settings[whatsapp_number]" class="regular-text" value="<?php echo esc_attr($settings['whatsapp_number']); ?>">
                    <p class="description">Digits only, including country code. Example: 8613800138000</p></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_whatsapp_display">WhatsApp Display Text</label></th>
                    <td><input id="stage_whatsapp_display" type="text" name="stage_b2b_settings[whatsapp_display]" class="regular-text" value="<?php echo esc_attr($settings['whatsapp_display']); ?>"></td>
                </tr>
            </table>
            <?php submit_button('Save Settings'); ?>
        </form>
        <hr>
        <h2>Overdue Lead Reminder</h2>
        <p>Daily digest is sent automatically to Sales Email for overdue leads.</p>
        <form method="post">
            <?php wp_nonce_field('stage_b2b_send_overdue_now', 'stage_b2b_overdue_nonce'); ?>
            <p><button class="button button-secondary" type="submit" name="stage_b2b_send_overdue_now" value="1">Send Reminder Now</button></p>
        </form>
    </div>
    <?php
}

function stage_b2b_register_lead_cpt() {
    register_post_type(
        'stage_b2b_lead',
        array(
            'labels' => array(
                'name'          => __('B2B Leads', 'stage-lighting'),
                'singular_name' => __('B2B Lead', 'stage-lighting'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-id',
            'supports'            => array('title'),
            'capability_type'     => 'post',
            'map_meta_cap'        => true,
            'exclude_from_search' => true,
        )
    );
}
add_action('init', 'stage_b2b_register_lead_cpt');

function stage_b2b_get_status_labels() {
    return array(
        'new'       => 'New',
        'contacted' => 'Contacted',
        'quoted'    => 'Quoted',
        'won'       => 'Won',
        'lost'      => 'Lost',
    );
}

function stage_b2b_get_overdue_leads($limit = 100) {
    $today = wp_date('Y-m-d', current_time('timestamp'));
    $query = new WP_Query(
        array(
            'post_type'      => 'stage_b2b_lead',
            'post_status'    => 'publish',
            'posts_per_page' => max(1, (int) $limit),
            'orderby'        => 'meta_value',
            'meta_key'       => 'follow_up_date',
            'order'          => 'ASC',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => 'follow_up_date',
                    'value'   => $today,
                    'compare' => '<',
                    'type'    => 'DATE',
                ),
                array(
                    'key'     => 'lead_status',
                    'value'   => array('won', 'lost'),
                    'compare' => 'NOT IN',
                ),
            ),
        )
    );
    return $query->posts;
}

function stage_b2b_send_overdue_digest($force = false) {
    $today = wp_date('Y-m-d', current_time('timestamp'));
    $last_sent = (string) get_option('stage_b2b_last_digest_date', '');
    if (!$force && $last_sent === $today) {
        return array('sent' => false, 'reason' => 'already_sent_today', 'count' => 0);
    }

    $overdue = stage_b2b_get_overdue_leads(120);
    if (empty($overdue)) {
        return array('sent' => false, 'reason' => 'no_overdue_leads', 'count' => 0);
    }

    $to = stage_b2b_get_sales_email();
    if (empty($to) || !is_email($to)) {
        return array('sent' => false, 'reason' => 'invalid_sales_email', 'count' => count($overdue));
    }

    $subject = sprintf('[Stage B2B] Overdue Leads Reminder (%s)', $today);
    $body = "Daily overdue lead reminder.\n\n";
    $body .= sprintf("Total overdue leads: %d\n\n", count($overdue));
    $body .= "Leads:\n";

    foreach ($overdue as $lead) {
        $lead_id = (int) $lead->ID;
        $company = (string) get_post_meta($lead_id, 'company_name', true);
        $name = (string) get_post_meta($lead_id, 'full_name', true);
        $email = (string) get_post_meta($lead_id, 'email', true);
        $status = (string) get_post_meta($lead_id, 'lead_status', true);
        $follow_up = (string) get_post_meta($lead_id, 'follow_up_date', true);
        $edit_url = admin_url('post.php?post=' . $lead_id . '&action=edit');
        $body .= sprintf(
            "- #%d | %s | %s | %s | status=%s | follow_up=%s\n  %s\n",
            $lead_id,
            $company,
            $name,
            $email,
            $status,
            $follow_up,
            $edit_url
        );
    }

    $sent = wp_mail($to, $subject, $body);
    if ($sent) {
        update_option('stage_b2b_last_digest_date', $today, false);
        return array('sent' => true, 'reason' => 'ok', 'count' => count($overdue));
    }

    return array('sent' => false, 'reason' => 'wp_mail_failed', 'count' => count($overdue));
}

function stage_b2b_daily_digest_cron_handler() {
    stage_b2b_send_overdue_digest(false);
}
add_action('stage_b2b_daily_digest_event', 'stage_b2b_daily_digest_cron_handler');

function stage_b2b_maybe_schedule_daily_digest() {
    if (wp_next_scheduled('stage_b2b_daily_digest_event')) {
        return;
    }
    wp_schedule_event(time() + 300, 'daily', 'stage_b2b_daily_digest_event');
}
add_action('init', 'stage_b2b_maybe_schedule_daily_digest');

function stage_b2b_cleanup_cron_on_deactivate() {
    wp_clear_scheduled_hook('stage_b2b_daily_digest_event');
}
register_deactivation_hook(__FILE__, 'stage_b2b_cleanup_cron_on_deactivate');

function stage_b2b_create_lead($data) {
    $lead_id = wp_insert_post(
        array(
            'post_type'   => 'stage_b2b_lead',
            'post_status' => 'publish',
            'post_title'  => sprintf('%s - %s', $data['company'], current_time('mysql')),
        )
    );

    if (!$lead_id || is_wp_error($lead_id)) {
        return 0;
    }

    update_post_meta($lead_id, 'full_name', $data['full_name']);
    update_post_meta($lead_id, 'company_name', $data['company']);
    update_post_meta($lead_id, 'email', $data['email']);
    update_post_meta($lead_id, 'phone', $data['phone']);
    update_post_meta($lead_id, 'country', $data['country']);
    update_post_meta($lead_id, 'interested_products', $data['products']);
    update_post_meta($lead_id, 'estimated_quantity', $data['quantity']);
    update_post_meta($lead_id, 'project_description', $data['project']);
    update_post_meta($lead_id, 'attachment_url', $data['attachment_url']);
    update_post_meta($lead_id, 'lead_status', 'new');
    update_post_meta($lead_id, 'follow_up_date', wp_date('Y-m-d', strtotime('+2 days', current_time('timestamp'))));

    return (int) $lead_id;
}

function stage_b2b_add_lead_columns($columns) {
    $columns['full_name']    = 'Full Name';
    $columns['company_name'] = 'Company';
    $columns['email']        = 'Email';
    $columns['country']      = 'Country';
    $columns['quantity']     = 'Quantity';
    $columns['lead_status']  = 'Status';
    $columns['follow_up']    = 'Follow-up Date';
    $columns['overdue']      = 'Overdue';
    return $columns;
}
add_filter('manage_stage_b2b_lead_posts_columns', 'stage_b2b_add_lead_columns');

function stage_b2b_is_lead_overdue($post_id) {
    $status = (string) get_post_meta($post_id, 'lead_status', true);
    if (in_array($status, array('won', 'lost'), true)) {
        return false;
    }
    $follow_up = (string) get_post_meta($post_id, 'follow_up_date', true);
    if (empty($follow_up)) {
        return false;
    }
    $today = wp_date('Y-m-d', current_time('timestamp'));
    return $follow_up < $today;
}

function stage_b2b_render_lead_columns($column, $post_id) {
    if ('full_name' === $column) {
        echo esc_html((string) get_post_meta($post_id, 'full_name', true));
    } elseif ('company_name' === $column) {
        echo esc_html((string) get_post_meta($post_id, 'company_name', true));
    } elseif ('email' === $column) {
        echo esc_html((string) get_post_meta($post_id, 'email', true));
    } elseif ('country' === $column) {
        echo esc_html((string) get_post_meta($post_id, 'country', true));
    } elseif ('quantity' === $column) {
        echo esc_html((string) get_post_meta($post_id, 'estimated_quantity', true));
    } elseif ('lead_status' === $column) {
        echo esc_html((string) get_post_meta($post_id, 'lead_status', true));
    } elseif ('follow_up' === $column) {
        echo esc_html((string) get_post_meta($post_id, 'follow_up_date', true));
    } elseif ('overdue' === $column) {
        if (stage_b2b_is_lead_overdue($post_id)) {
            echo '<strong style="color:#b42318;">YES</strong>';
        } else {
            echo '<span style="color:#067647;">NO</span>';
        }
    }
}
add_action('manage_stage_b2b_lead_posts_custom_column', 'stage_b2b_render_lead_columns', 10, 2);

function stage_b2b_add_lead_status_filter($post_type) {
    if ('stage_b2b_lead' !== $post_type) {
        return;
    }

    $current = isset($_GET['lead_status']) ? sanitize_text_field(wp_unslash($_GET['lead_status'])) : '';
    $labels = stage_b2b_get_status_labels();
    ?>
    <select name="lead_status">
        <option value="">All Statuses</option>
        <?php foreach ($labels as $key => $label) : ?>
            <option value="<?php echo esc_attr($key); ?>" <?php selected($current, $key); ?>><?php echo esc_html($label); ?></option>
        <?php endforeach; ?>
    </select>
    <?php
    $follow_up_from = isset($_GET['follow_up_from']) ? sanitize_text_field(wp_unslash($_GET['follow_up_from'])) : '';
    $follow_up_to = isset($_GET['follow_up_to']) ? sanitize_text_field(wp_unslash($_GET['follow_up_to'])) : '';
    ?>
    <input type="date" name="follow_up_from" value="<?php echo esc_attr($follow_up_from); ?>" placeholder="Follow-up from">
    <input type="date" name="follow_up_to" value="<?php echo esc_attr($follow_up_to); ?>" placeholder="Follow-up to">
    <?php
}
add_action('restrict_manage_posts', 'stage_b2b_add_lead_status_filter');

function stage_b2b_filter_lead_query($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    $post_type = $query->get('post_type');
    if ('stage_b2b_lead' !== $post_type) {
        return;
    }
    $status = isset($_GET['lead_status']) ? sanitize_text_field(wp_unslash($_GET['lead_status'])) : '';
    if (empty($status)) {
        $meta_query = (array) $query->get('meta_query');
    } else {
        $meta_query = (array) $query->get('meta_query');
        $meta_query[] = array(
            'key'   => 'lead_status',
            'value' => $status,
        );
    }

    $follow_up_from = isset($_GET['follow_up_from']) ? sanitize_text_field(wp_unslash($_GET['follow_up_from'])) : '';
    $follow_up_to = isset($_GET['follow_up_to']) ? sanitize_text_field(wp_unslash($_GET['follow_up_to'])) : '';
    if (!empty($follow_up_from) || !empty($follow_up_to)) {
        $date_clause = array(
            'key'     => 'follow_up_date',
            'type'    => 'DATE',
            'compare' => 'BETWEEN',
            'value'   => array(
                !empty($follow_up_from) ? $follow_up_from : '1970-01-01',
                !empty($follow_up_to) ? $follow_up_to : '2999-12-31',
            ),
        );
        $meta_query[] = $date_clause;
    }
    $query->set('meta_query', $meta_query);
}
add_action('pre_get_posts', 'stage_b2b_filter_lead_query');

function stage_b2b_render_export_button($which) {
    if ('top' !== $which) {
        return;
    }
    global $typenow;
    if ('stage_b2b_lead' !== $typenow) {
        return;
    }
    $args = array();
    $allowed = array('lead_status', 'follow_up_from', 'follow_up_to');
    foreach ($allowed as $key) {
        if (isset($_GET[$key]) && '' !== $_GET[$key]) {
            $args[$key] = sanitize_text_field(wp_unslash($_GET[$key]));
        }
    }
    $export_url = add_query_arg(
        array_merge(
            array('action' => 'stage_b2b_export_leads'),
            $args
        ),
        admin_url('admin-post.php')
    );
    $export_url = wp_nonce_url($export_url, 'stage_b2b_export_leads');
    echo '<a class="button button-secondary" href="' . esc_url($export_url) . '" style="margin-left:8px;">Export CSV</a>';
}
add_action('manage_posts_extra_tablenav', 'stage_b2b_render_export_button');

function stage_b2b_export_leads_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    check_admin_referer('stage_b2b_export_leads');

    $meta_query = array();
    $status = isset($_GET['lead_status']) ? sanitize_text_field(wp_unslash($_GET['lead_status'])) : '';
    if (!empty($status)) {
        $meta_query[] = array(
            'key'   => 'lead_status',
            'value' => $status,
        );
    }
    $follow_up_from = isset($_GET['follow_up_from']) ? sanitize_text_field(wp_unslash($_GET['follow_up_from'])) : '';
    $follow_up_to = isset($_GET['follow_up_to']) ? sanitize_text_field(wp_unslash($_GET['follow_up_to'])) : '';
    if (!empty($follow_up_from) || !empty($follow_up_to)) {
        $meta_query[] = array(
            'key'     => 'follow_up_date',
            'type'    => 'DATE',
            'compare' => 'BETWEEN',
            'value'   => array(
                !empty($follow_up_from) ? $follow_up_from : '1970-01-01',
                !empty($follow_up_to) ? $follow_up_to : '2999-12-31',
            ),
        );
    }

    $query_args = array(
        'post_type'      => 'stage_b2b_lead',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC',
    );
    if (!empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    $leads = get_posts($query_args);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=stage-b2b-leads-' . gmdate('Ymd-His') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, array('ID', 'Created At', 'Full Name', 'Company', 'Email', 'Phone', 'Country', 'Interested Products', 'Estimated Quantity', 'Lead Status', 'Follow-up Date', 'Overdue', 'Attachment URL', 'Project Description'));

    foreach ($leads as $lead) {
        $lead_id = $lead->ID;
        fputcsv(
            $output,
            array(
                $lead_id,
                get_the_date('Y-m-d H:i:s', $lead_id),
                (string) get_post_meta($lead_id, 'full_name', true),
                (string) get_post_meta($lead_id, 'company_name', true),
                (string) get_post_meta($lead_id, 'email', true),
                (string) get_post_meta($lead_id, 'phone', true),
                (string) get_post_meta($lead_id, 'country', true),
                (string) get_post_meta($lead_id, 'interested_products', true),
                (string) get_post_meta($lead_id, 'estimated_quantity', true),
                (string) get_post_meta($lead_id, 'lead_status', true),
                (string) get_post_meta($lead_id, 'follow_up_date', true),
                stage_b2b_is_lead_overdue($lead_id) ? 'YES' : 'NO',
                (string) get_post_meta($lead_id, 'attachment_url', true),
                (string) get_post_meta($lead_id, 'project_description', true),
            )
        );
    }
    fclose($output);
    exit;
}
add_action('admin_post_stage_b2b_export_leads', 'stage_b2b_export_leads_csv');

function stage_b2b_register_lead_meta_box() {
    add_meta_box(
        'stage_b2b_lead_details',
        'Lead Details',
        'stage_b2b_render_lead_meta_box',
        'stage_b2b_lead',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'stage_b2b_register_lead_meta_box');

function stage_b2b_render_lead_meta_box($post) {
    wp_nonce_field('stage_b2b_save_lead_meta', 'stage_b2b_lead_meta_nonce');
    $labels = stage_b2b_get_status_labels();
    $current_status = (string) get_post_meta($post->ID, 'lead_status', true);
    $fields = array(
        'full_name'           => 'Full Name',
        'company_name'        => 'Company Name',
        'email'               => 'Email',
        'phone'               => 'Phone',
        'country'             => 'Country',
        'interested_products' => 'Interested Products',
        'estimated_quantity'  => 'Estimated Quantity',
        'project_description' => 'Project Description',
        'attachment_url'      => 'Attachment URL',
    );
    $follow_up_date = (string) get_post_meta($post->ID, 'follow_up_date', true);
    ?>
    <table class="form-table" role="presentation">
        <?php foreach ($fields as $meta_key => $label) : ?>
            <tr>
                <th scope="row"><?php echo esc_html($label); ?></th>
                <td>
                    <?php
                    $value = (string) get_post_meta($post->ID, $meta_key, true);
                    if ('project_description' === $meta_key) {
                        echo '<textarea readonly rows="4" style="width:100%;">' . esc_textarea($value) . '</textarea>';
                    } elseif ('attachment_url' === $meta_key && !empty($value)) {
                        echo '<a href="' . esc_url($value) . '" target="_blank" rel="noopener">View Attachment</a>';
                    } else {
                        echo '<input type="text" class="regular-text" readonly value="' . esc_attr($value) . '">';
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <th scope="row">Lead Status</th>
            <td>
                <select name="stage_b2b_lead_status">
                    <?php foreach ($labels as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($current_status, $key); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">Follow-up Date</th>
            <td>
                <input type="date" name="stage_b2b_follow_up_date" value="<?php echo esc_attr($follow_up_date); ?>">
            </td>
        </tr>
    </table>
    <?php
}

function stage_b2b_save_lead_meta($post_id) {
    if (!isset($_POST['stage_b2b_lead_meta_nonce'])) {
        return;
    }
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_b2b_lead_meta_nonce'])), 'stage_b2b_save_lead_meta')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    if ('stage_b2b_lead' !== get_post_type($post_id)) {
        return;
    }
    $status = isset($_POST['stage_b2b_lead_status']) ? sanitize_text_field(wp_unslash($_POST['stage_b2b_lead_status'])) : '';
    $labels = stage_b2b_get_status_labels();
    if (array_key_exists($status, $labels)) {
        update_post_meta($post_id, 'lead_status', $status);
    }
    $follow_up_date = isset($_POST['stage_b2b_follow_up_date']) ? sanitize_text_field(wp_unslash($_POST['stage_b2b_follow_up_date'])) : '';
    if (!empty($follow_up_date)) {
        update_post_meta($post_id, 'follow_up_date', $follow_up_date);
    }
}
add_action('save_post', 'stage_b2b_save_lead_meta');

function stage_b2b_get_for_business_url() {
    $page = get_page_by_path('for-business');
    if ($page instanceof WP_Post) {
        return get_permalink($page->ID);
    }
    return home_url('/for-business');
}

function stage_b2b_get_client_ip() {
    $keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $raw = sanitize_text_field(wp_unslash($_SERVER[$key]));
            $ip = trim(explode(',', $raw)[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return 'unknown';
}

function stage_b2b_rate_limit_check() {
    $ip = stage_b2b_get_client_ip();
    $bucket = 'stage_b2b_rate_' . md5($ip);
    $state = get_transient($bucket);
    if (!is_array($state)) {
        $state = array('last' => 0, 'count' => 0);
    }
    $now = time();
    if ($state['last'] > 0 && ($now - (int) $state['last']) < 20) {
        return new WP_Error('rate_limit', 'Please wait before submitting again.');
    }
    if ((int) $state['count'] >= 20) {
        return new WP_Error('rate_limit_hour', 'Too many requests from this network. Try again later.');
    }
    $state['last'] = $now;
    $state['count'] = (int) $state['count'] + 1;
    set_transient($bucket, $state, HOUR_IN_SECONDS);
    return true;
}

function stage_b2b_get_product_name_options($limit = 60) {
    if (!post_type_exists('product')) {
        return array();
    }
    $query = new WP_Query(
        array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => max(1, (int) $limit),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        )
    );
    if (empty($query->posts)) {
        return array();
    }
    $names = array();
    foreach ($query->posts as $product_id) {
        $name = get_the_title((int) $product_id);
        if (!empty($name)) {
            $names[] = (string) $name;
        }
    }
    return array_values(array_unique($names));
}

function stage_b2b_split_products_text($text) {
    $parts = preg_split('/\s*[,|\n]\s*/', (string) $text);
    if (!is_array($parts)) {
        return array();
    }
    $clean = array();
    foreach ($parts as $part) {
        $item = sanitize_text_field(trim((string) $part));
        if (!empty($item)) {
            $clean[] = $item;
        }
    }
    return array_values(array_unique($clean));
}

function stage_b2b_quote_form_shortcode() {
    $notice = '';
    $error  = '';
    $prefill_product = '';
    $product_options = stage_b2b_get_product_name_options(80);

    if (isset($_GET['product'])) {
        $prefill_product = sanitize_text_field(wp_unslash($_GET['product']));
    }

    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['stage_quote_submit'])) {
        if (!isset($_POST['stage_quote_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_quote_nonce'])), 'stage_quote_submit')) {
            $error = __('Security check failed. Please refresh and try again.', 'stage-lighting');
        } else {
            $honeypot = sanitize_text_field(wp_unslash($_POST['website'] ?? ''));
            if (!empty($honeypot)) {
                $error = __('Submission rejected.', 'stage-lighting');
            }
            if (empty($error)) {
                $rate_check = stage_b2b_rate_limit_check();
                if (is_wp_error($rate_check)) {
                    $error = __($rate_check->get_error_message(), 'stage-lighting');
                }
            }

            $full_name      = sanitize_text_field(wp_unslash($_POST['full_name'] ?? ''));
            $company        = sanitize_text_field(wp_unslash($_POST['company_name'] ?? ''));
            $email          = sanitize_email(wp_unslash($_POST['email'] ?? ''));
            $phone          = sanitize_text_field(wp_unslash($_POST['phone'] ?? ''));
            $country        = sanitize_text_field(wp_unslash($_POST['country'] ?? ''));
            $products       = sanitize_text_field(wp_unslash($_POST['interested_products'] ?? ''));
            $products_manual = sanitize_text_field(wp_unslash($_POST['interested_products_manual'] ?? ''));
            $products_selected_raw = isset($_POST['interested_products_list']) ? wp_unslash($_POST['interested_products_list']) : array();
            $products_selected = array();
            if (is_array($products_selected_raw)) {
                foreach ($products_selected_raw as $item) {
                    $item = sanitize_text_field((string) $item);
                    if (!empty($item)) {
                        $products_selected[] = $item;
                    }
                }
            }
            $products_parts = array_merge(stage_b2b_split_products_text($products_manual), $products_selected);
            if (!empty($products_parts)) {
                $products = implode(', ', array_values(array_unique($products_parts)));
            }
            $quantity       = sanitize_text_field(wp_unslash($_POST['estimated_quantity'] ?? ''));
            $project        = sanitize_textarea_field(wp_unslash($_POST['project_description'] ?? ''));
            $attachment     = '';
            $attachment_url = '';

            if (!empty($error)) {
                // noop: error already set by anti-spam checks.
            } elseif (empty($full_name) || empty($company) || empty($email) || empty($country) || empty($products) || empty($quantity) || empty($project)) {
                $error = __('Please fill all required fields.', 'stage-lighting');
            } elseif (!is_email($email)) {
                $error = __('Please enter a valid email address.', 'stage-lighting');
            } else {
                if (!empty($_FILES['request_file']['name'])) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                    $uploaded = wp_handle_upload($_FILES['request_file'], array('test_form' => false));
                    if (isset($uploaded['file'])) {
                        $attachment = $uploaded['file'];
                        $attachment_url = $uploaded['url'] ?? '';
                    }
                }

                stage_b2b_create_lead(
                    array(
                        'full_name'      => $full_name,
                        'company'        => $company,
                        'email'          => $email,
                        'phone'          => $phone,
                        'country'        => $country,
                        'products'       => $products,
                        'quantity'       => $quantity,
                        'project'        => $project,
                        'attachment_url' => $attachment_url,
                    )
                );

                $to      = stage_b2b_get_sales_email();
                $subject = 'New B2B Bulk Quote Request';
                $body    = "Full Name: {$full_name}\n";
                $body   .= "Company Name: {$company}\n";
                $body   .= "Email: {$email}\n";
                $body   .= "Phone: {$phone}\n";
                $body   .= "Country: {$country}\n";
                $body   .= "Interested Products: {$products}\n";
                $body   .= "Estimated Quantity: {$quantity}\n";
                $body   .= "Project Description:\n{$project}\n";
                if (!empty($attachment_url)) {
                    $body .= "Attachment URL: {$attachment_url}\n";
                }
                $headers = array('Reply-To: ' . $email);
                $files   = $attachment ? array($attachment) : array();

                $sent = wp_mail($to, $subject, $body, $headers, $files);
                if ($sent) {
                    wp_mail(
                        $email,
                        'Quote Request Received',
                        "Hi {$full_name},\n\nThanks for your request. Our sales team will respond within 24 hours.\n\nRegards,\nStage Lighting Team"
                    );
                    $notice = __('Thank you. Your quote request has been submitted.', 'stage-lighting');
                } else {
                    $error = __('Submission failed. Please try again later.', 'stage-lighting');
                }
            }
        }
    }

    ob_start();
    if (!empty($notice)) {
        echo '<div class="stage-form-success">' . esc_html($notice) . '</div>';
        echo "<script>if (typeof window.stageTrackAdsConversion === 'function') { window.stageTrackAdsConversion(1.0, 'USD'); }</script>";
    }
    if (!empty($error)) {
        echo '<div class="stage-form-error">' . esc_html($error) . '</div>';
    }
    ?>
    <div class="stage-quote-layout">
        <form class="stage-quote-form stage-track-quote-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('stage_quote_submit', 'stage_quote_nonce'); ?>
            <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;height:1px;width:1px;opacity:0;">

            <div>
                <label for="full_name">Full Name *</label>
                <input id="full_name" name="full_name" type="text" required>
            </div>

            <div>
                <label for="company_name">Company Name *</label>
                <input id="company_name" name="company_name" type="text" required>
            </div>

            <div>
                <label for="email">Email Address *</label>
                <input id="email" name="email" type="email" required>
            </div>

            <div>
                <label for="phone">Phone Number</label>
                <input id="phone" name="phone" type="text">
            </div>

            <div>
                <label for="country">Country *</label>
                <select id="country" name="country" required>
                    <option value="">Please Select</option>
                    <?php
                    $country_options = array(
                        'United States', 'Canada', 'United Kingdom', 'Germany', 'France', 'Italy', 'Spain', 'Netherlands',
                        'Australia', 'New Zealand', 'Singapore', 'Malaysia', 'Thailand', 'Philippines', 'Vietnam', 'Japan',
                        'South Korea', 'United Arab Emirates', 'Saudi Arabia', 'South Africa', 'Other',
                    );
                    foreach ($country_options as $country_name) :
                        ?>
                        <option value="<?php echo esc_attr($country_name); ?>"><?php echo esc_html($country_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label for="interested_products_manual">Interested Product(s) *</label>
                <input id="interested_products" name="interested_products" type="hidden" value="">
                <input id="interested_products_manual" name="interested_products_manual" type="text" placeholder="Type product names, separated by comma" value="<?php echo esc_attr($prefill_product); ?>" autocomplete="off">
                <?php if (!empty($product_options)) : ?>
                    <div class="stage-product-picker">
                        <label for="interested_products_search">Quick Select (multi-select + search)</label>
                        <input id="interested_products_search" type="search" placeholder="Search products...">
                        <div class="stage-product-picker-list" id="stage-product-picker-list">
                            <?php foreach ($product_options as $option_name) : ?>
                                <?php $is_checked = (!empty($prefill_product) && strtolower($prefill_product) === strtolower($option_name)); ?>
                                <label class="stage-product-option">
                                    <input type="checkbox" name="interested_products_list[]" value="<?php echo esc_attr($option_name); ?>" <?php checked($is_checked); ?>>
                                    <span><?php echo esc_html($option_name); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div>
                <label for="estimated_quantity">Estimated Quantity *</label>
                <select id="estimated_quantity" name="estimated_quantity" required>
                    <option value="">Please Select</option>
                    <option value="10-50">10-50</option>
                    <option value="50-100">50-100</option>
                    <option value="100-500">100-500</option>
                    <option value="500+">500+</option>
                </select>
            </div>

            <div>
                <label for="project_description">Project Description *</label>
                <textarea id="project_description" name="project_description" rows="5" required></textarea>
            </div>

            <div>
                <label for="request_file">Upload File (optional)</label>
                <div id="stage-dropzone" class="stage-dropzone" tabindex="0">
                    <p>Drag and drop a file here, or click to choose.</p>
                    <small id="stage-dropzone-name">No file selected</small>
                </div>
                <input id="request_file" name="request_file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.png,.jpg,.jpeg,.webp" style="display:none;">
            </div>

            <div>
                <button class="btn btn-gradient" type="submit" name="stage_quote_submit">Submit Quote Request</button>
            </div>
        </form>
        <aside class="stage-quote-sidebar">
            <h3>Need Fast Support?</h3>
            <p>Email: <?php echo esc_html(stage_b2b_get_sales_email()); ?></p>
            <p>WhatsApp: <?php echo esc_html(stage_b2b_get_whatsapp_display()); ?></p>
            <ul>
                <li>24h response commitment</li>
                <li>CE / FCC / RoHS certified</li>
                <li>Warranty and global shipment support</li>
            </ul>
        </aside>
    </div>
    <script>
    (function () {
        var form = document.querySelector(".stage-quote-form");
        if (!form) {
            return;
        }
        var hiddenProducts = form.querySelector("#interested_products");
        var manualProducts = form.querySelector("#interested_products_manual");
        var listSelector = 'input[name="interested_products_list[]"]';
        var pickerSearch = form.querySelector("#interested_products_search");
        var pickerList = form.querySelector("#stage-product-picker-list");
        var fileInput = form.querySelector("#request_file");
        var dropzone = form.querySelector("#stage-dropzone");
        var dropzoneName = form.querySelector("#stage-dropzone-name");

        function splitProducts(text) {
            if (!text) {
                return [];
            }
            return text.split(/[,\n|]/).map(function (item) {
                return item.trim();
            }).filter(Boolean);
        }

        function buildProductsValue() {
            var values = [];
            splitProducts(manualProducts ? manualProducts.value : "").forEach(function (item) {
                values.push(item);
            });
            form.querySelectorAll(listSelector).forEach(function (checkbox) {
                if (checkbox.checked && checkbox.value) {
                    values.push(checkbox.value.trim());
                }
            });
            var unique = Array.from(new Set(values));
            if (hiddenProducts) {
                hiddenProducts.value = unique.join(", ");
            }
            if (manualProducts) {
                if (unique.length === 0) {
                    manualProducts.setCustomValidity("Please choose at least one product.");
                } else {
                    manualProducts.setCustomValidity("");
                }
            }
        }

        if (pickerSearch && pickerList) {
            pickerSearch.addEventListener("input", function () {
                var keyword = pickerSearch.value.toLowerCase().trim();
                pickerList.querySelectorAll(".stage-product-option").forEach(function (row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = (!keyword || text.indexOf(keyword) !== -1) ? "" : "none";
                });
            });
        }

        if (dropzone && fileInput) {
            var updateName = function () {
                if (!dropzoneName) {
                    return;
                }
                dropzoneName.textContent = (fileInput.files && fileInput.files[0]) ? fileInput.files[0].name : "No file selected";
            };
            dropzone.addEventListener("click", function () {
                fileInput.click();
            });
            dropzone.addEventListener("keydown", function (evt) {
                if (evt.key === "Enter" || evt.key === " ") {
                    evt.preventDefault();
                    fileInput.click();
                }
            });
            fileInput.addEventListener("change", updateName);
            dropzone.addEventListener("dragover", function (evt) {
                evt.preventDefault();
                dropzone.classList.add("is-dragover");
            });
            dropzone.addEventListener("dragleave", function () {
                dropzone.classList.remove("is-dragover");
            });
            dropzone.addEventListener("drop", function (evt) {
                evt.preventDefault();
                dropzone.classList.remove("is-dragover");
                if (evt.dataTransfer && evt.dataTransfer.files && evt.dataTransfer.files.length > 0) {
                    fileInput.files = evt.dataTransfer.files;
                    updateName();
                }
            });
        }

        form.addEventListener("submit", buildProductsValue);
        buildProductsValue();
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('stage_bulk_quote_form', 'stage_b2b_quote_form_shortcode');

function stage_b2b_render_quote_button_single_product() {
    if (!function_exists('is_product') || !is_product()) {
        return;
    }

    global $product;
    if (!$product) {
        return;
    }

    $quote_url = add_query_arg(
        array(
            'product' => $product->get_name(),
        ),
        stage_b2b_get_for_business_url()
    );
    ?>
    <a class="btn btn-outline stage-quote-product-cta" href="<?php echo esc_url($quote_url); ?>">Request Bulk Quote</a>
    <?php
}
add_action('woocommerce_single_product_summary', 'stage_b2b_render_quote_button_single_product', 31);

function stage_b2b_render_quote_button_loop() {
    if (!function_exists('wc_get_product')) {
        return;
    }

    global $product;
    if (!$product) {
        return;
    }
    $quote_url = add_query_arg(
        array(
            'product' => $product->get_name(),
        ),
        stage_b2b_get_for_business_url()
    );
    echo '<a class="btn btn-outline stage-quote-loop-cta" href="' . esc_url($quote_url) . '">Request Bulk Quote</a>';
}
add_action('woocommerce_after_shop_loop_item', 'stage_b2b_render_quote_button_loop', 15);

function stage_b2b_enqueue_tracking_script() {
    if (is_admin()) {
        return;
    }
    ?>
    <script>
    (function () {
        function trackEvent(eventName, payload) {
            if (typeof window.gtag === "function") {
                window.gtag("event", eventName, payload || {});
            }
            if (Array.isArray(window.dataLayer)) {
                window.dataLayer.push(Object.assign({ event: eventName }, payload || {}));
            }
            if (typeof window.fbq === "function") {
                window.fbq("trackCustom", eventName, payload || {});
            }
        }

        document.addEventListener("click", function (evt) {
            var quoteBtn = evt.target.closest(".stage-quote-product-cta, .stage-quote-loop-cta");
            if (quoteBtn) {
                trackEvent("b2b_quote_click", { source: "product" });
            }
            var whatsappBtn = evt.target.closest(".stage-whatsapp-float");
            if (whatsappBtn) {
                trackEvent("whatsapp_click", { source: "floating_button" });
            }
        });

        document.addEventListener("DOMContentLoaded", function () {
            if (document.querySelector(".stage-form-success")) {
                trackEvent("b2b_quote_submit_success", { source: "quote_form" });
            }
        });
    })();
    </script>
    <?php
}
add_action('wp_footer', 'stage_b2b_enqueue_tracking_script', 100);
