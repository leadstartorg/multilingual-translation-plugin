<?php
/**
 * Native WordPress Logging System
 * Tracks translation events in WordPress database
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create translation logs table on plugin activation
 */
function mct_create_logs_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mct_translation_logs';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        timestamp DATETIME NOT NULL,
        source_lang VARCHAR(10) NOT NULL,
        target_lang VARCHAR(10) NOT NULL,
        char_count INT NOT NULL,
        source_url TEXT NOT NULL,
        user_ip VARCHAR(45) NOT NULL,
        user_agent TEXT,
        cache_hit TINYINT(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY timestamp_idx (timestamp),
        KEY target_lang_idx (target_lang),
        KEY cache_hit_idx (cache_hit)
    ) {$charset_collate};";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Log a translation event in the WordPress database
 * 
 * @param string $sourceLang Source language code
 * @param string $targetLang Target language code
 * @param int $charCount Number of characters translated
 * @param string $sourceUrl URL of the translated page
 * @param bool $cacheHit Whether this was served from cache
 */
function mct_log_translation_native($sourceLang, $targetLang, $charCount, $sourceUrl, $cacheHit = false) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mct_translation_logs';
    
    // Get user IP address (respect proxies)
    $user_ip = mct_get_user_ip();
    
    // Get user agent
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    
    $wpdb->insert(
        $table_name,
        array(
            'timestamp' => current_time('mysql'),
            'source_lang' => sanitize_text_field($sourceLang),
            'target_lang' => sanitize_text_field($targetLang),
            'char_count' => absint($charCount),
            'source_url' => esc_url_raw($sourceUrl),
            'user_ip' => sanitize_text_field($user_ip),
            'user_agent' => sanitize_text_field($user_agent),
            'cache_hit' => $cacheHit ? 1 : 0
        ),
        array('%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
    );
    
    // Send to Google Analytics if enabled
    mct_send_to_analytics($sourceLang, $targetLang, $charCount, $sourceUrl);
}

/**
 * Get user IP address, respecting proxy headers
 * 
 * @return string User IP address
 */
function mct_get_user_ip() {
    $ip_keys = array(
        'HTTP_CF_CONNECTING_IP', // Cloudflare
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    );
    
    foreach ($ip_keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            
            // Handle comma-separated IPs
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Validate IP
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    
    return 'unknown';
}

/**
 * Get translation statistics
 * 
 * @param int $days Number of days to analyze (default: 30)
 * @return array Statistics array
 */
function mct_get_translation_stats($days = 30) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mct_translation_logs';
    $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    // Total translations
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE timestamp >= %s",
        $date_from
    ));
    
    // Total characters translated
    $total_chars = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(char_count) FROM {$table_name} WHERE timestamp >= %s",
        $date_from
    ));
    
    // By language
    $by_language = $wpdb->get_results($wpdb->prepare(
        "SELECT target_lang, COUNT(*) as count, SUM(char_count) as chars 
        FROM {$table_name} 
        WHERE timestamp >= %s 
        GROUP BY target_lang 
        ORDER BY count DESC",
        $date_from
    ));
    
    // Cache hit rate
    $cache_hits = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE timestamp >= %s AND cache_hit = 1",
        $date_from
    ));
    
    $cache_hit_rate = $total > 0 ? round(($cache_hits / $total) * 100, 2) : 0;
    
    // Daily activity
    $daily_activity = $wpdb->get_results($wpdb->prepare(
        "SELECT DATE(timestamp) as date, COUNT(*) as count, SUM(char_count) as chars
        FROM {$table_name}
        WHERE timestamp >= %s
        GROUP BY DATE(timestamp)
        ORDER BY date DESC",
        $date_from
    ));
    
    return array(
        'total_translations' => intval($total),
        'total_characters' => intval($total_chars),
        'cache_hit_rate' => $cache_hit_rate,
        'by_language' => $by_language,
        'daily_activity' => $daily_activity
    );
}

/**
 * Get recent translation logs
 * 
 * @param int $limit Number of logs to retrieve
 * @return array Translation logs
 */
function mct_get_recent_logs($limit = 50) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mct_translation_logs';
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} ORDER BY timestamp DESC LIMIT %d",
        $limit
    ));
}

/**
 * Delete old logs
 * 
 * @param int $days Delete logs older than this many days
 * @return int Number of logs deleted
 */
function mct_cleanup_old_logs($days = 90) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mct_translation_logs';
    $date_cutoff = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $deleted = $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table_name} WHERE timestamp < %s",
        $date_cutoff
    ));
    
    return intval($deleted);
}

/**
 * Schedule automatic log cleanup (runs monthly)
 */
add_action('wp', 'mct_schedule_log_cleanup');
function mct_schedule_log_cleanup() {
    if (!wp_next_scheduled('mct_cleanup_logs_event')) {
        wp_schedule_event(time(), 'monthly', 'mct_cleanup_logs_event');
    }
}

add_action('mct_cleanup_logs_event', 'mct_run_log_cleanup');
function mct_run_log_cleanup() {
    // Keep logs for 90 days by default
    mct_cleanup_old_logs(90);
}

/**
 * Send translation event to Google Analytics 4
 * 
 * @param string $sourceLang Source language
 * @param string $targetLang Target language
 * @param int $charCount Character count
 * @param string $sourceUrl Source URL
 */
function mct_send_to_analytics($sourceLang, $targetLang, $charCount, $sourceUrl) {
    $options = get_option('mct_settings');
    
    // Check if analytics is enabled
    if (empty($options['enable_analytics']) || empty($options['ga_measurement_id'])) {
        return;
    }
    
    $measurement_id = $options['ga_measurement_id'];
    
    // This would typically be sent via client-side JavaScript
    // For server-side, you'd need to use GA4 Measurement Protocol
    // Add to page footer for client-side tracking
    add_action('wp_footer', function() use ($sourceLang, $targetLang, $charCount, $sourceUrl, $measurement_id) {
        ?>
        <script>
        if (typeof gtag !== 'undefined') {
            gtag('event', 'translation', {
                'event_category': 'Content Translation',
                'event_label': '<?php echo esc_js($sourceLang); ?> to <?php echo esc_js($targetLang); ?>',
                'source_lang': '<?php echo esc_js($sourceLang); ?>',
                'target_lang': '<?php echo esc_js($targetLang); ?>',
                'char_count': <?php echo intval($charCount); ?>,
                'page_url': '<?php echo esc_js($sourceUrl); ?>'
            });
        }
        </script>
        <?php
    }, 999);
}

/**
 * Export logs to CSV
 * 
 * @param int $days Number of days to export
 * @return string CSV content
 */
function mct_export_logs_csv($days = 30) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'mct_translation_logs';
    $date_from = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} WHERE timestamp >= %s ORDER BY timestamp DESC",
        $date_from
    ), ARRAY_A);
    
    $csv = "Timestamp,Source Language,Target Language,Characters,URL,IP Address,Cache Hit\n";
    
    foreach ($logs as $log) {
        $csv .= sprintf(
            "%s,%s,%s,%d,\"%s\",%s,%s\n",
            $log['timestamp'],
            $log['source_lang'],
            $log['target_lang'],
            $log['char_count'],
            $log['source_url'],
            $log['user_ip'],
            $log['cache_hit'] ? 'Yes' : 'No'
        );
    }
    
    return $csv;
}

/**
 * Add admin submenu for detailed analytics
 */
add_action('admin_menu', 'mct_add_analytics_submenu');
function mct_add_analytics_submenu() {
    add_submenu_page(
        'options-general.php',
        __('Translation Analytics', 'multilang-cloud-translate'),
        __('Translation Analytics', 'multilang-cloud-translate'),
        'manage_options',
        'mct-analytics',
        'mct_analytics_page'
    );
}

/**
 * Analytics page with charts and statistics
 */
function mct_analytics_page() {
    $stats = mct_get_translation_stats(30);
    ?>
    <div class="wrap">
        <h1><?php _e('Translation Analytics', 'multilang-cloud-translate'); ?></h1>
        
        <div class="mct-stats-grid">
            <div class="mct-stat-card">
                <h3><?php _e('Total Translations', 'multilang-cloud-translate'); ?></h3>
                <p class="mct-stat-number"><?php echo number_format($stats['total_translations']); ?></p>
                <span class="mct-stat-label"><?php _e('Last 30 days', 'multilang-cloud-translate'); ?></span>
            </div>
            
            <div class="mct-stat-card">
                <h3><?php _e('Characters Translated', 'multilang-cloud-translate'); ?></h3>
                <p class="mct-stat-number"><?php echo number_format($stats['total_characters']); ?></p>
                <span class="mct-stat-label"><?php _e('Last 30 days', 'multilang-cloud-translate'); ?></span>
            </div>
            
            <div class="mct-stat-card">
                <h3><?php _e('Cache Hit Rate', 'multilang-cloud-translate'); ?></h3>
                <p class="mct-stat-number"><?php echo $stats['cache_hit_rate']; ?>%</p>
                <span class="mct-stat-label"><?php _e('Efficiency', 'multilang-cloud-translate'); ?></span>
            </div>
        </div>
        
        <h2><?php _e('Translations by Language', 'multilang-cloud-translate'); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Language', 'multilang-cloud-translate'); ?></th>
                    <th><?php _e('Translations', 'multilang-cloud-translate'); ?></th>
                    <th><?php _e('Characters', 'multilang-cloud-translate'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stats['by_language'] as $lang_stat): ?>
                    <tr>
                        <td><strong><?php echo esc_html(strtoupper($lang_stat->target_lang)); ?></strong></td>
                        <td><?php echo number_format($lang_stat->count); ?></td>
                        <td><?php echo number_format($lang_stat->chars); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <p>
            <a href="<?php echo admin_url('admin-post.php?action=mct_export_csv'); ?>" class="button">
                <?php _e('Export to CSV', 'multilang-cloud-translate'); ?>
            </a>
        </p>
    </div>
    
    <style>
        .mct-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .mct-stat-card {
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 20px;
        }
        .mct-stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #2271b1;
            margin: 10px 0;
        }
        .mct-stat-label {
            color: #666;
            font-size: 13px;
        }
    </style>
    <?php
}

/**
 * Handle CSV export
 */
add_action('admin_post_mct_export_csv', 'mct_handle_csv_export');
function mct_handle_csv_export() {
    if (!current_user_can('manage_options')) {
        wp_die('Unauthorized');
    }
    
    $csv = mct_export_logs_csv(30);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="translation-logs-' . date('Y-m-d') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo $csv;
    exit;
}
