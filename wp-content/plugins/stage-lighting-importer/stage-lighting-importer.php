<?php
/**
 * Plugin Name: Stage Lighting Product Importer
 * Description: Import WooCommerce products from Excel/CSV with dynamic attributes.
 * Version: 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function stage_importer_register_menu() {
    add_submenu_page(
        'tools.php',
        'Stage Product Importer',
        'Stage Product Importer',
        'manage_options',
        'stage-product-importer',
        'stage_importer_render_page'
    );
    add_submenu_page(
        'tools.php',
        'Stage Import Logs',
        'Stage Import Logs',
        'manage_options',
        'stage-product-importer-logs',
        'stage_importer_render_logs_page'
    );
}
add_action('admin_menu', 'stage_importer_register_menu');

function stage_importer_render_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $preview = null;
    $result  = null;
    if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['stage_importer_preview'])) {
        $preview = stage_importer_handle_preview();
    } elseif ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['stage_importer_confirm'])) {
        $result = stage_importer_handle_confirm_import();
    }
    ?>
    <div class="wrap">
        <h1>Stage Product Importer</h1>
        <p><a href="<?php echo esc_url(admin_url('tools.php?page=stage-product-importer-logs')); ?>">View Import Logs</a></p>
        <p>Upload <code>.xlsx</code> or <code>.csv</code> exported from Excel. Dynamic parameter columns are supported via <code>attr:Parameter Name</code>.</p>
        <p><strong>Required column:</strong> <code>name</code>. Recommended columns: <code>sku</code>, <code>regular_price</code>, <code>categories</code>, <code>tags</code>, <code>description</code>, <code>short_description</code>, <code>stock</code>, <code>video_url</code>, <code>download_links</code>, <code>download_items</code>.</p>
        <p><strong>Dynamic parameters:</strong> add any number of columns, for example <code>attr:Power</code>, <code>attr:Beam Angle</code>, <code>attr:Control Protocol</code>. Multiple values can use comma or <code>|</code>.</p>
        <p>Template: <code>templates/stage-lighting-products-import-template.csv</code> or <code>templates/stage-lighting-products-import-50sku.csv</code></p>

        <?php if (is_array($preview)) : ?>
            <?php if (!empty($preview['errors'])) : ?>
                <div class="notice notice-error"><p><?php echo esc_html('Preview found issues: ' . count($preview['errors'])); ?></p></div>
                <ul>
                    <?php foreach ($preview['errors'] as $err) : ?>
                        <li><?php echo esc_html($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="notice notice-info">
                <p><?php echo esc_html(sprintf('Preview - Will import: %d, update: %d, skip: %d', (int) $preview['imported'], (int) $preview['updated'], (int) $preview['skipped'])); ?></p>
                <?php if (!empty($preview['dynamic_attributes'])) : ?>
                    <p><?php echo esc_html('Detected dynamic attributes: ' . implode(', ', $preview['dynamic_attributes'])); ?></p>
                <?php endif; ?>
            </div>
            <?php if (!empty($preview['import_token'])) : ?>
                <form method="post">
                    <?php wp_nonce_field('stage_importer_confirm', 'stage_importer_confirm_nonce'); ?>
                    <input type="hidden" name="stage_import_token" value="<?php echo esc_attr($preview['import_token']); ?>">
                    <button type="submit" class="button button-primary" name="stage_importer_confirm" value="1">Confirm Import</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (is_array($result)) : ?>
            <?php if (!empty($result['errors'])) : ?>
                <div class="notice notice-error"><p><?php echo esc_html('Import completed with errors: ' . count($result['errors'])); ?></p></div>
                <ul>
                    <?php foreach ($result['errors'] as $err) : ?>
                        <li><?php echo esc_html($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (!empty($result['error_report_token'])) : ?>
                    <p>
                        <a class="button button-secondary" href="<?php echo esc_url(stage_importer_build_error_report_url($result['error_report_token'])); ?>">Download Error CSV</a>
                    </p>
                <?php endif; ?>
                <?php if (!empty($result['error_report_url'])) : ?>
                    <p>
                        <a class="button button-link" href="<?php echo esc_url((string) $result['error_report_url']); ?>" target="_blank" rel="noopener">Open Stored Error CSV</a>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
            <div class="notice notice-success">
                <p><?php echo esc_html(sprintf('Imported: %d, Updated: %d, Skipped: %d', (int) $result['imported'], (int) $result['updated'], (int) $result['skipped'])); ?></p>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('stage_importer_upload', 'stage_importer_nonce'); ?>
            <input type="file" name="stage_import_file" accept=".csv,.xlsx" required>
            <p style="margin-top:12px;">
                <label>
                    <input type="checkbox" name="stage_import_update_by_sku" value="1" checked>
                    Update existing product by SKU if matched
                </label>
            </p>
            <p>
                <button type="submit" class="button button-secondary" name="stage_importer_preview" value="1">Preview Import</button>
            </p>
        </form>
    </div>
    <?php
}

function stage_importer_get_logs() {
    $logs = get_option('stage_importer_logs', array());
    return is_array($logs) ? $logs : array();
}

function stage_importer_add_log($entry) {
    $logs = stage_importer_get_logs();
    array_unshift($logs, $entry);
    $logs = array_slice($logs, 0, 100);
    update_option('stage_importer_logs', $logs, false);
}

function stage_importer_render_logs_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    $logs = stage_importer_get_logs();
    ?>
    <div class="wrap">
        <h1>Stage Import Logs</h1>
        <p>Recent confirmed import jobs (max 100 records).</p>
        <?php if (empty($logs)) : ?>
            <p>No import logs yet.</p>
        <?php else : ?>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Operator</th>
                        <th>File</th>
                        <th>Imported</th>
                        <th>Updated</th>
                        <th>Skipped</th>
                        <th>Errors</th>
                        <th>Error Report</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log) : ?>
                        <tr>
                            <td><?php echo esc_html((string) ($log['time'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['operator'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($log['file_name'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ((int) ($log['imported'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) ((int) ($log['updated'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) ((int) ($log['skipped'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) ((int) ($log['error_count'] ?? 0))); ?></td>
                            <td>
                                <?php if (!empty($log['error_report_url'])) : ?>
                                    <a href="<?php echo esc_url((string) $log['error_report_url']); ?>" target="_blank" rel="noopener">Download CSV</a>
                                <?php else : ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

function stage_importer_handle_preview() {
    if (!isset($_POST['stage_importer_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_importer_nonce'])), 'stage_importer_upload')) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Security check failed.'));
    }
    if (!function_exists('wc_get_product')) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('WooCommerce is required.'));
    }
    if (empty($_FILES['stage_import_file']['name'])) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Please choose a file.'));
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    $uploaded = wp_handle_upload($_FILES['stage_import_file'], array('test_form' => false));
    if (!isset($uploaded['file'])) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Upload failed.'));
    }

    $path = $uploaded['file'];
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    if ('csv' === $ext) {
        $rows = stage_importer_read_csv($path);
    } elseif ('xlsx' === $ext) {
        $rows = stage_importer_read_xlsx($path);
    } else {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Unsupported file type.'));
    }

    if (empty($rows)) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('No data rows found.'));
    }

    $allow_update = !empty($_POST['stage_import_update_by_sku']);
    $preview = stage_importer_analyze_rows($rows, $allow_update);
    $preview['errors'] = array();

    $token = wp_generate_password(16, false, false);
    set_transient(
        'stage_importer_payload_' . $token,
        array(
            'path'         => $path,
            'allow_update' => $allow_update,
            'file_name'    => sanitize_file_name((string) ($_FILES['stage_import_file']['name'] ?? basename($path))),
            'operator_id'  => (int) get_current_user_id(),
        ),
        HOUR_IN_SECONDS
    );
    $preview['import_token'] = $token;

    return $preview;
}

function stage_importer_handle_confirm_import() {
    if (!isset($_POST['stage_importer_confirm_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stage_importer_confirm_nonce'])), 'stage_importer_confirm')) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Security check failed.'));
    }
    $token = isset($_POST['stage_import_token']) ? sanitize_text_field(wp_unslash($_POST['stage_import_token'])) : '';
    if (empty($token)) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Missing import token.'));
    }
    $payload = get_transient('stage_importer_payload_' . $token);
    if (!is_array($payload) || empty($payload['path'])) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Import session expired, please preview again.'));
    }
    delete_transient('stage_importer_payload_' . $token);

    $path = $payload['path'];
    $allow_update = !empty($payload['allow_update']);
    $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ('csv' === $ext) {
        $rows = stage_importer_read_csv($path);
    } elseif ('xlsx' === $ext) {
        $rows = stage_importer_read_xlsx($path);
    } else {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('Unsupported file type.'));
    }
    if (empty($rows)) {
        return array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array('No data rows found.'));
    }

    $result = array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array(), 'error_report_token' => '', 'error_report_url' => '');
    $error_rows = array();

    foreach ($rows as $index => $row) {
        $line_no = $index + 2;
        $name = stage_importer_cell($row, 'name');
        if (empty($name)) {
            $result['skipped']++;
            $result['errors'][] = "Row {$line_no}: missing name.";
            $error_rows[] = stage_importer_build_error_row($line_no, 'missing name', $row);
            continue;
        }

        $status = stage_importer_upsert_product($row, $allow_update);
        if (is_wp_error($status)) {
            $result['skipped']++;
            $result['errors'][] = "Row {$line_no}: " . $status->get_error_message();
            $error_rows[] = stage_importer_build_error_row($line_no, $status->get_error_message(), $row);
        } elseif ('updated' === $status) {
            $result['updated']++;
        } else {
            $result['imported']++;
        }
    }

    if (!empty($error_rows)) {
        $report_token = wp_generate_password(16, false, false);
        set_transient('stage_importer_error_report_' . $report_token, $error_rows, HOUR_IN_SECONDS);
        $result['error_report_token'] = $report_token;
        $result['error_report_url'] = stage_importer_store_error_report_csv($error_rows);
    }

    $operator_id = (int) ($payload['operator_id'] ?? 0);
    $operator = 'Unknown';
    if ($operator_id > 0) {
        $user = get_user_by('id', $operator_id);
        if ($user instanceof WP_User) {
            $operator = !empty($user->display_name) ? $user->display_name : $user->user_login;
        }
    }
    stage_importer_add_log(
        array(
            'time'             => current_time('mysql'),
            'operator'         => $operator,
            'file_name'        => sanitize_text_field((string) ($payload['file_name'] ?? basename($path))),
            'imported'         => (int) $result['imported'],
            'updated'          => (int) $result['updated'],
            'skipped'          => (int) $result['skipped'],
            'error_count'      => count($result['errors']),
            'error_report_url' => esc_url_raw((string) ($result['error_report_url'] ?? '')),
        )
    );

    return $result;
}

function stage_importer_analyze_rows($rows, $allow_update) {
    $result = array('imported' => 0, 'updated' => 0, 'skipped' => 0, 'dynamic_attributes' => array());
    foreach ($rows as $row) {
        $name = stage_importer_cell($row, 'name');
        if (empty($name)) {
            $result['skipped']++;
            continue;
        }
        $sku = stage_importer_cell($row, 'sku');
        if ($allow_update && !empty($sku) && (int) wc_get_product_id_by_sku($sku) > 0) {
            $result['updated']++;
        } else {
            $result['imported']++;
        }
        foreach ($row as $header => $value) {
            if (0 === strpos($header, 'attr:') && '' !== trim((string) $value)) {
                $result['dynamic_attributes'][] = trim(substr($header, 5));
            }
        }
    }
    $result['dynamic_attributes'] = array_values(array_unique(array_filter($result['dynamic_attributes'])));
    return $result;
}

function stage_importer_build_error_row($line_no, $error, $row) {
    return array(
        'line_no' => (string) $line_no,
        'error'   => (string) $error,
        'name'    => stage_importer_cell($row, 'name'),
        'sku'     => stage_importer_cell($row, 'sku'),
    );
}

function stage_importer_build_error_report_url($token) {
    $url = add_query_arg(
        array(
            'action' => 'stage_importer_download_error_report',
            'token'  => $token,
        ),
        admin_url('admin-post.php')
    );
    return wp_nonce_url($url, 'stage_importer_download_error_report');
}

function stage_importer_store_error_report_csv($rows) {
    $uploads = wp_upload_dir();
    if (!empty($uploads['error']) || empty($uploads['basedir']) || empty($uploads['baseurl'])) {
        return '';
    }

    $dir = trailingslashit($uploads['basedir']) . 'stage-importer-reports';
    if (!wp_mkdir_p($dir)) {
        return '';
    }

    $file_name = 'stage-import-errors-' . gmdate('Ymd-His') . '-' . wp_generate_password(6, false, false) . '.csv';
    $file_path = trailingslashit($dir) . $file_name;
    $fp = fopen($file_path, 'w');
    if (!$fp) {
        return '';
    }

    fprintf($fp, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($fp, array('line_no', 'error', 'name', 'sku'));
    foreach ($rows as $row) {
        fputcsv($fp, array($row['line_no'], $row['error'], $row['name'], $row['sku']));
    }
    fclose($fp);

    return trailingslashit($uploads['baseurl']) . 'stage-importer-reports/' . rawurlencode($file_name);
}

function stage_importer_download_error_report() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    check_admin_referer('stage_importer_download_error_report');
    $token = isset($_GET['token']) ? sanitize_text_field(wp_unslash($_GET['token'])) : '';
    if (empty($token)) {
        wp_die('Missing token.');
    }
    $rows = get_transient('stage_importer_error_report_' . $token);
    if (!is_array($rows) || empty($rows)) {
        wp_die('Error report expired.');
    }
    delete_transient('stage_importer_error_report_' . $token);

    nocache_headers();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=stage-import-errors-' . gmdate('Ymd-His') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($out, array('line_no', 'error', 'name', 'sku'));
    foreach ($rows as $row) {
        fputcsv($out, array($row['line_no'], $row['error'], $row['name'], $row['sku']));
    }
    fclose($out);
    exit;
}
add_action('admin_post_stage_importer_download_error_report', 'stage_importer_download_error_report');

function stage_importer_read_csv($path) {
    $fp = fopen($path, 'r');
    if (!$fp) {
        return array();
    }

    $headers = fgetcsv($fp);
    if (!$headers) {
        fclose($fp);
        return array();
    }

    $headers = array_map('stage_importer_normalize_header', $headers);
    $rows = array();
    while (($data = fgetcsv($fp)) !== false) {
        $assoc = array();
        foreach ($headers as $i => $header) {
            $assoc[$header] = isset($data[$i]) ? trim((string) $data[$i]) : '';
        }
        $rows[] = $assoc;
    }
    fclose($fp);
    return $rows;
}

function stage_importer_read_xlsx($path) {
    if (!class_exists('ZipArchive')) {
        return array();
    }
    $zip = new ZipArchive();
    if (true !== $zip->open($path)) {
        return array();
    }

    $shared_strings = array();
    $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
    if ($shared_xml) {
        $xml = simplexml_load_string($shared_xml);
        if ($xml && isset($xml->si)) {
            foreach ($xml->si as $si) {
                if (isset($si->t)) {
                    $shared_strings[] = (string) $si->t;
                } else {
                    $text = '';
                    if (isset($si->r)) {
                        foreach ($si->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }
                    $shared_strings[] = $text;
                }
            }
        }
    }

    $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    $zip->close();
    if (!$sheet_xml) {
        return array();
    }

    $sheet = simplexml_load_string($sheet_xml);
    if (!$sheet || !isset($sheet->sheetData->row)) {
        return array();
    }

    $grid = array();
    foreach ($sheet->sheetData->row as $row) {
        $row_index = (int) $row['r'];
        $grid[$row_index] = array();
        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $col = preg_replace('/\d+/', '', $ref);
            $col_index = stage_importer_col_to_index($col);
            $type = (string) $cell['t'];
            $value = '';
            if ('s' === $type) {
                $idx = (int) $cell->v;
                $value = isset($shared_strings[$idx]) ? $shared_strings[$idx] : '';
            } elseif ('inlineStr' === $type && isset($cell->is->t)) {
                $value = (string) $cell->is->t;
            } elseif (isset($cell->v)) {
                $value = (string) $cell->v;
            }
            $grid[$row_index][$col_index] = trim($value);
        }
    }

    if (empty($grid[1])) {
        return array();
    }

    ksort($grid[1]);
    $headers = array_map('stage_importer_normalize_header', array_values($grid[1]));
    $rows = array();

    foreach ($grid as $index => $cells) {
        if (1 === $index) {
            continue;
        }
        ksort($cells);
        $assoc = array();
        foreach ($headers as $i => $header) {
            $assoc[$header] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
        }
        if (implode('', $assoc) !== '') {
            $rows[] = $assoc;
        }
    }

    return $rows;
}

function stage_importer_col_to_index($letters) {
    $letters = strtoupper($letters);
    $len = strlen($letters);
    $num = 0;
    for ($i = 0; $i < $len; $i++) {
        $num = $num * 26 + (ord($letters[$i]) - 64);
    }
    return $num - 1;
}

function stage_importer_normalize_header($header) {
    return strtolower(trim((string) $header));
}

function stage_importer_cell($row, $key, $default = '') {
    $k = stage_importer_normalize_header($key);
    return isset($row[$k]) ? trim((string) $row[$k]) : $default;
}

function stage_importer_split_values($value) {
    $value = trim((string) $value);
    if ('' === $value) {
        return array();
    }
    $parts = preg_split('/\s*[|,]\s*/', $value);
    $parts = array_filter(array_map('trim', (array) $parts));
    return array_values(array_unique($parts));
}

function stage_importer_get_term_ids($taxonomy, $names) {
    $ids = array();
    foreach ($names as $name) {
        $term = get_term_by('name', $name, $taxonomy);
        if (!$term) {
            $created = wp_insert_term($name, $taxonomy);
            if (is_wp_error($created)) {
                continue;
            }
            $ids[] = (int) $created['term_id'];
        } else {
            $ids[] = (int) $term->term_id;
        }
    }
    return array_values(array_unique($ids));
}

function stage_importer_upsert_product($row, $allow_update) {
    $sku = stage_importer_cell($row, 'sku');
    $existing_id = 0;
    if (!empty($sku) && $allow_update) {
        $existing_id = (int) wc_get_product_id_by_sku($sku);
    }

    if ($existing_id > 0) {
        $product = wc_get_product($existing_id);
        if (!$product) {
            return new WP_Error('importer_product_load_failed', 'Failed to load existing product.');
        }
        $operation = 'updated';
    } else {
        $product = new WC_Product_Simple();
        $operation = 'imported';
    }

    $name = stage_importer_cell($row, 'name');
    $product->set_name($name);
    $product->set_status('publish');
    $product->set_description(stage_importer_cell($row, 'description'));
    $product->set_short_description(stage_importer_cell($row, 'short_description'));

    $regular_price = stage_importer_cell($row, 'regular_price');
    if ('' !== $regular_price) {
        $product->set_regular_price((string) $regular_price);
    }
    $sale_price = stage_importer_cell($row, 'sale_price');
    if ('' !== $sale_price) {
        $product->set_sale_price((string) $sale_price);
    }
    if (!empty($sku)) {
        $product->set_sku($sku);
    }

    $stock = stage_importer_cell($row, 'stock');
    if ('' !== $stock) {
        $product->set_manage_stock(true);
        $product->set_stock_quantity((int) $stock);
        $product->set_stock_status(((int) $stock) > 0 ? 'instock' : 'outofstock');
    }

    $category_names = stage_importer_split_values(stage_importer_cell($row, 'categories'));
    if (!empty($category_names)) {
        $product->set_category_ids(stage_importer_get_term_ids('product_cat', $category_names));
    }
    $tag_names = stage_importer_split_values(stage_importer_cell($row, 'tags'));
    if (!empty($tag_names)) {
        $product->set_tag_ids(stage_importer_get_term_ids('product_tag', $tag_names));
    }

    $attributes = array();
    $position = 0;
    foreach ($row as $header => $value) {
        if (0 !== strpos($header, 'attr:')) {
            continue;
        }
        $label = trim(substr($header, 5));
        if (empty($label) || '' === trim((string) $value)) {
            continue;
        }

        $attr = new WC_Product_Attribute();
        $attr->set_id(0);
        $attr->set_name($label);
        $attr->set_options(stage_importer_split_values($value));
        $attr->set_position($position++);
        $attr->set_visible(true);
        $attr->set_variation(false);
        $attributes[] = $attr;
    }
    if (!empty($attributes)) {
        $product->set_attributes($attributes);
    }

    $product_id = $product->save();
    if (!$product_id) {
        return new WP_Error('importer_save_failed', 'Failed to save product.');
    }

    $download_links = stage_importer_cell($row, 'download_links');
    if (!empty($download_links)) {
        $download_links = str_replace('|', "\n", $download_links);
        update_post_meta($product_id, 'stage_download_links', trim($download_links));
    }
    $download_items = stage_importer_cell($row, 'download_items');
    if (!empty($download_items)) {
        $items = array_filter(array_map('trim', explode('|', $download_items)));
        update_post_meta($product_id, 'stage_download_links', implode("\n", $items));
    }

    $video_url = stage_importer_cell($row, 'video_url');
    if (!empty($video_url)) {
        update_post_meta($product_id, 'stage_video_url', esc_url_raw($video_url));
    }

    return $operation;
}
