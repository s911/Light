<?php
/**
 * Plugin Name: Stage Lighting Marketing
 * Description: Social links, analytics settings, and newsletter popup module.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function stage_marketing_defaults() {
    return array(
        'instagram_url'       => '',
        'facebook_url'        => '',
        'tiktok_url'          => '',
        'youtube_url'         => '',
        'ga4_measurement_id'  => '',
        'meta_pixel_id'       => '',
        'google_ads_id'       => '',
        'google_ads_label'    => '',
        'enable_newsletter'   => '1',
        'live_chat_provider'  => 'none',
        'tawk_property_id'    => '',
        'tawk_widget_id'      => '',
        'tidio_public_key'    => '',
        'live_chat_custom'    => '',
    );
}

function stage_marketing_get_settings() {
    $saved = get_option('stage_marketing_settings', array());
    if (!is_array($saved)) {
        $saved = array();
    }
    return wp_parse_args($saved, stage_marketing_defaults());
}

function stage_marketing_register_settings() {
    register_setting(
        'stage_marketing_settings_group',
        'stage_marketing_settings',
        array(
            'sanitize_callback' => 'stage_marketing_sanitize_settings',
        )
    );
}
add_action('admin_init', 'stage_marketing_register_settings');

function stage_marketing_sanitize_settings($input) {
    if (!is_array($input)) {
        return stage_marketing_defaults();
    }
    $provider = sanitize_text_field($input['live_chat_provider'] ?? 'none');
    $allowed_providers = array('none', 'tawk', 'tidio', 'custom');
    if (!in_array($provider, $allowed_providers, true)) {
        $provider = 'none';
    }
    $allowed_chat_html = array(
        'script' => array(
            'src' => true,
            'type' => true,
            'id' => true,
            'async' => true,
            'defer' => true,
            'charset' => true,
            'crossorigin' => true,
        ),
        'div' => array('id' => true, 'class' => true),
    );
    return array(
        'instagram_url'      => esc_url_raw($input['instagram_url'] ?? ''),
        'facebook_url'       => esc_url_raw($input['facebook_url'] ?? ''),
        'tiktok_url'         => esc_url_raw($input['tiktok_url'] ?? ''),
        'youtube_url'        => esc_url_raw($input['youtube_url'] ?? ''),
        'ga4_measurement_id' => sanitize_text_field($input['ga4_measurement_id'] ?? ''),
        'meta_pixel_id'      => sanitize_text_field($input['meta_pixel_id'] ?? ''),
        'google_ads_id'      => sanitize_text_field($input['google_ads_id'] ?? ''),
        'google_ads_label'   => sanitize_text_field($input['google_ads_label'] ?? ''),
        'enable_newsletter'  => empty($input['enable_newsletter']) ? '0' : '1',
        'live_chat_provider' => $provider,
        'tawk_property_id'   => sanitize_text_field($input['tawk_property_id'] ?? ''),
        'tawk_widget_id'     => sanitize_text_field($input['tawk_widget_id'] ?? ''),
        'tidio_public_key'   => sanitize_text_field($input['tidio_public_key'] ?? ''),
        'live_chat_custom'   => wp_kses((string) ($input['live_chat_custom'] ?? ''), $allowed_chat_html),
    );
}

function stage_marketing_register_menu() {
    add_options_page(
        'Stage Marketing Settings',
        'Stage Marketing Settings',
        'manage_options',
        'stage-marketing-settings',
        'stage_marketing_render_settings_page'
    );
}
add_action('admin_menu', 'stage_marketing_register_menu');

function stage_marketing_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $s = stage_marketing_get_settings();
    ?>
    <div class="wrap">
        <h1>Stage Marketing Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields('stage_marketing_settings_group'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label for="stage_instagram">Instagram URL</label></th>
                    <td><input id="stage_instagram" class="regular-text" name="stage_marketing_settings[instagram_url]" type="url" value="<?php echo esc_attr($s['instagram_url']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_facebook">Facebook URL</label></th>
                    <td><input id="stage_facebook" class="regular-text" name="stage_marketing_settings[facebook_url]" type="url" value="<?php echo esc_attr($s['facebook_url']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_tiktok">TikTok URL</label></th>
                    <td><input id="stage_tiktok" class="regular-text" name="stage_marketing_settings[tiktok_url]" type="url" value="<?php echo esc_attr($s['tiktok_url']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_youtube">YouTube URL</label></th>
                    <td><input id="stage_youtube" class="regular-text" name="stage_marketing_settings[youtube_url]" type="url" value="<?php echo esc_attr($s['youtube_url']); ?>"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_ga4">GA4 Measurement ID</label></th>
                    <td><input id="stage_ga4" class="regular-text" name="stage_marketing_settings[ga4_measurement_id]" type="text" value="<?php echo esc_attr($s['ga4_measurement_id']); ?>" placeholder="G-XXXXXXXXXX"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_meta_pixel">Meta Pixel ID</label></th>
                    <td><input id="stage_meta_pixel" class="regular-text" name="stage_marketing_settings[meta_pixel_id]" type="text" value="<?php echo esc_attr($s['meta_pixel_id']); ?>" placeholder="1234567890"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_google_ads_id">Google Ads Conversion ID</label></th>
                    <td><input id="stage_google_ads_id" class="regular-text" name="stage_marketing_settings[google_ads_id]" type="text" value="<?php echo esc_attr($s['google_ads_id']); ?>" placeholder="AW-123456789"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_google_ads_label">Google Ads Conversion Label</label></th>
                    <td><input id="stage_google_ads_label" class="regular-text" name="stage_marketing_settings[google_ads_label]" type="text" value="<?php echo esc_attr($s['google_ads_label']); ?>" placeholder="AbCdEFghijklmNopQ"></td>
                </tr>
                <tr>
                    <th scope="row">Newsletter Popup</th>
                    <td>
                        <label><input type="checkbox" name="stage_marketing_settings[enable_newsletter]" value="1" <?php checked('1', $s['enable_newsletter']); ?>> Enable Newsletter Popup</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_live_chat_provider">Live Chat Provider</label></th>
                    <td>
                        <select id="stage_live_chat_provider" name="stage_marketing_settings[live_chat_provider]">
                            <option value="none" <?php selected('none', (string) $s['live_chat_provider']); ?>>Disabled</option>
                            <option value="tawk" <?php selected('tawk', (string) $s['live_chat_provider']); ?>>Tawk.to</option>
                            <option value="tidio" <?php selected('tidio', (string) $s['live_chat_provider']); ?>>Tidio</option>
                            <option value="custom" <?php selected('custom', (string) $s['live_chat_provider']); ?>>Custom Script</option>
                        </select>
                        <p class="description">Choose one provider and fill corresponding fields below.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_tawk_property">Tawk.to Property ID</label></th>
                    <td><input id="stage_tawk_property" class="regular-text" name="stage_marketing_settings[tawk_property_id]" type="text" value="<?php echo esc_attr($s['tawk_property_id']); ?>" placeholder="e.g. 6620abc1234567890abcdef"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_tawk_widget">Tawk.to Widget ID</label></th>
                    <td><input id="stage_tawk_widget" class="regular-text" name="stage_marketing_settings[tawk_widget_id]" type="text" value="<?php echo esc_attr($s['tawk_widget_id']); ?>" placeholder="e.g. 1hsxxxxxx"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_tidio_key">Tidio Public Key</label></th>
                    <td><input id="stage_tidio_key" class="regular-text" name="stage_marketing_settings[tidio_public_key]" type="text" value="<?php echo esc_attr($s['tidio_public_key']); ?>" placeholder="e.g. abcdefghijklmnop"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="stage_live_chat_custom">Custom Live Chat Script</label></th>
                    <td>
                        <textarea id="stage_live_chat_custom" class="large-text code" rows="6" name="stage_marketing_settings[live_chat_custom]" placeholder="<script>...your chat code...</script>"><?php echo esc_textarea((string) $s['live_chat_custom']); ?></textarea>
                        <p class="description">Only used when provider is set to Custom Script.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Marketing Settings'); ?>
        </form>
    </div>
    <?php
}

function stage_marketing_register_subscriber_cpt() {
    register_post_type(
        'stage_subscriber',
        array(
            'labels' => array(
                'name'          => __('Newsletter Subscribers', 'stage-lighting'),
                'singular_name' => __('Subscriber', 'stage-lighting'),
            ),
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-email-alt',
            'supports'            => array('title'),
            'exclude_from_search' => true,
        )
    );
}
add_action('init', 'stage_marketing_register_subscriber_cpt');

function stage_marketing_add_head_scripts() {
    if (is_admin()) {
        return;
    }
    $s = stage_marketing_get_settings();
    $ga4 = trim((string) $s['ga4_measurement_id']);
    $google_ads_id = trim((string) ($s['google_ads_id'] ?? ''));
    $google_ads_label = trim((string) ($s['google_ads_label'] ?? ''));
    if (!empty($ga4)) {
        ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($ga4); ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_js($ga4); ?>');
        </script>
        <?php
    }
    if (!empty($google_ads_id) && empty($ga4)) {
        ?>
        <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr($google_ads_id); ?>"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo esc_js($google_ads_id); ?>');
        </script>
        <?php
    }
    if (!empty($google_ads_id) && !empty($google_ads_label)) {
        ?>
        <script>
        window.stageTrackAdsConversion = function (value, currency) {
            if (typeof gtag !== 'function') {
                return;
            }
            gtag('event', 'conversion', {
                'send_to': '<?php echo esc_js($google_ads_id . '/' . $google_ads_label); ?>',
                'value': value || 1.0,
                'currency': currency || 'USD'
            });
        };
        </script>
        <?php
    }

    $pixel = trim((string) $s['meta_pixel_id']);
    if (!empty($pixel)) {
        ?>
        <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}
        (window, document,'script','https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', '<?php echo esc_js($pixel); ?>');
        fbq('track', 'PageView');
        </script>
        <?php
    }
}
add_action('wp_head', 'stage_marketing_add_head_scripts', 99);

function stage_marketing_render_live_chat() {
    if (is_admin()) {
        return;
    }
    $s = stage_marketing_get_settings();
    $provider = (string) ($s['live_chat_provider'] ?? 'none');
    if ('tawk' === $provider) {
        $property = trim((string) ($s['tawk_property_id'] ?? ''));
        $widget = trim((string) ($s['tawk_widget_id'] ?? ''));
        if (!empty($property) && !empty($widget)) {
            ?>
            <script>
            var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
            (function(){
                var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                s1.async=true;
                s1.src='https://embed.tawk.to/<?php echo esc_js($property); ?>/<?php echo esc_js($widget); ?>';
                s1.charset='UTF-8';
                s1.setAttribute('crossorigin','*');
                s0.parentNode.insertBefore(s1,s0);
            })();
            </script>
            <?php
        }
        return;
    }

    if ('tidio' === $provider) {
        $key = trim((string) ($s['tidio_public_key'] ?? ''));
        if (!empty($key)) {
            echo '<script src="//code.tidio.co/' . esc_attr($key) . '.js" async></script>';
        }
        return;
    }

    if ('custom' === $provider) {
        $custom = (string) ($s['live_chat_custom'] ?? '');
        if (!empty(trim($custom))) {
            echo $custom; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
    }
}
add_action('wp_footer', 'stage_marketing_render_live_chat', 99);

function stage_marketing_social_links($echo = true) {
    $s = stage_marketing_get_settings();
    $items = array(
        'Instagram' => $s['instagram_url'],
        'Facebook'  => $s['facebook_url'],
        'TikTok'    => $s['tiktok_url'],
        'YouTube'   => $s['youtube_url'],
    );

    $html = '<div class="stage-social-links">';
    foreach ($items as $label => $url) {
        if (!empty($url)) {
            $html .= '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($label) . '</a>';
        }
    }
    $html .= '</div>';

    if ($echo) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return;
    }
    return $html;
}

function stage_marketing_newsletter_form_handler() {
    if (!isset($_POST['stage_news_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_news_nonce'])), 'stage_news_submit')) {
        wp_safe_redirect(add_query_arg('newsletter', 'invalid', wp_get_referer() ?: home_url('/')));
        exit;
    }
    $email = sanitize_email(wp_unslash($_POST['stage_news_email'] ?? ''));
    if (!is_email($email)) {
        wp_safe_redirect(add_query_arg('newsletter', 'invalid', wp_get_referer() ?: home_url('/')));
        exit;
    }
    $exists = get_page_by_title($email, OBJECT, 'stage_subscriber');
    if (!$exists) {
        $subscriber_id = wp_insert_post(
            array(
                'post_type'   => 'stage_subscriber',
                'post_status' => 'publish',
                'post_title'  => $email,
            )
        );
        if ($subscriber_id && !is_wp_error($subscriber_id)) {
            update_post_meta($subscriber_id, 'source_url', esc_url_raw(wp_get_referer() ?: home_url('/')));
        }
    }
    wp_safe_redirect(add_query_arg('newsletter', 'subscribed', wp_get_referer() ?: home_url('/')));
    exit;
}
add_action('admin_post_nopriv_stage_news_subscribe', 'stage_marketing_newsletter_form_handler');
add_action('admin_post_stage_news_subscribe', 'stage_marketing_newsletter_form_handler');

function stage_marketing_footer_widgets() {
    if (is_admin()) {
        return;
    }
    $s = stage_marketing_get_settings();
    if ('1' !== $s['enable_newsletter']) {
        return;
    }
    $subscribed = isset($_GET['newsletter']) && 'subscribed' === $_GET['newsletter'];
    ?>
    <?php if ($subscribed) : ?>
        <div class="stage-news-toast">Thanks for subscribing to our newsletter.</div>
    <?php endif; ?>
    <div class="stage-news-popup" id="stage-news-popup" hidden>
        <button class="stage-news-close" type="button" id="stage-news-close" aria-label="Close">x</button>
        <h3>Subscribe for Lighting Updates</h3>
        <p>Get new product launches, setup tutorials and project cases.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('stage_news_submit', 'stage_news_nonce'); ?>
            <input type="hidden" name="action" value="stage_news_subscribe">
            <input type="email" name="stage_news_email" required placeholder="you@company.com">
            <button class="btn btn-gradient" type="submit">Subscribe</button>
        </form>
    </div>
    <script>
    (function(){
        var key = "stage_news_popup_closed";
        var popup = document.getElementById("stage-news-popup");
        var closeBtn = document.getElementById("stage-news-close");
        if (!popup || localStorage.getItem(key) === "1") {
            return;
        }
        setTimeout(function() {
            popup.hidden = false;
        }, 1800);
        if (closeBtn) {
            closeBtn.addEventListener("click", function() {
                popup.hidden = true;
                localStorage.setItem(key, "1");
            });
        }
    })();
    </script>
    <?php
}
add_action('wp_footer', 'stage_marketing_footer_widgets', 120);
