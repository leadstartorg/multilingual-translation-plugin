<?php
/**
 * Admin Settings Page
 * Handles all plugin configuration, displays translation logs, and provides cache management
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use Google\Cloud\Storage\StorageClient;

/**
 * Add admin menu
 */
add_action('admin_menu', 'mct_admin_menu');
function mct_admin_menu() {
    add_options_page(
        __('Multilang Cloud Translate Settings', 'multilang-cloud-translate'),
        __('Multilang Translate', 'multilang-cloud-translate'),
        'manage_options',
        'mct-settings',
        'mct_settings_page'
    );
}

/**
 * Register plugin settings
 */
add_action('admin_init', 'mct_register_settings');
function mct_register_settings() {
    register_setting('mct_settings_group', 'mct_settings', 'mct_sanitize_settings');
}

/**
 * Sanitize settings before saving
 */
function mct_sanitize_settings($input) {
    $sanitized = array();
    
    $sanitized['google_api_key'] = sanitize_text_field($input['google_api_key'] ?? '');
    $sanitized['google_project_id'] = sanitize_text_field($input['google_project_id'] ?? '');
    $sanitized['gcs_bucket'] = sanitize_text_field($input['gcs_bucket'] ?? '');
    $sanitized['active_languages'] = sanitize_text_field($input['active_languages'] ?? 'en,fr,es,de');
    $sanitized['default_language'] = sanitize_text_field($input['default_language'] ?? 'en');
    $sanitized['auto_redirect'] = isset($input['auto_redirect']) ? '1' : '0';
    $sanitized['translation_mode'] = sanitize_text_field($input['translation_mode'] ?? 'php');
    $sanitized['ipinfo_token'] = sanitize_text_field($input['ipinfo_token'] ?? '');
    $sanitized['enable_ip_redirect'] = isset($input['enable_ip_redirect']) ? '1' : '0';
    $sanitized['cache_ttl'] = absint($input['cache_ttl'] ?? 3600);
    $sanitized['enable_analytics'] = isset($input['enable_analytics']) ? '1' : '0';
    $sanitized['ga_measurement_id'] = sanitize_text_field($input['ga_measurement_id'] ?? '');
    
    return $sanitized;
}

/**
 * Render field helper function
 */
function mct_field_input($name, $size = 40, $type = 'text', $description = '') {
    $options = get_option('mct_settings');
    $val = $options[$name] ?? '';
    echo "<input type='{$type}' name='mct_settings[{$name}]' value='" . esc_attr($val) . "' size='{$size}' class='regular-text'>";
    if ($description) {
        echo "<p class='description'>{$description}</p>";
    }
}

/**
 * Main settings page
 */
function mct_settings_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'mct_translation_logs';
    $logs = $wpdb->get_results("SELECT * FROM {$table} ORDER BY timestamp DESC LIMIT 50");
    $options = get_option('mct_settings');
    $current_mode = $options['translation_mode'] ?? 'php';
    ?>
    
    <div class="wrap mct-settings-wrap">
        <h1><?php _e('Multilang Cloud Translate Settings', 'multilang-cloud-translate'); ?></h1>
        
        <!-- Mode Badge -->
        <div class="mct-mode-badge">
            <?php if ($current_mode === 'cloudflare'): ?>
                <span class="badge badge-cloudflare">⚡ Using Cloudflare Workers Mode</span>
            <?php else: ?>
                <span class="badge badge-php">🔧 Using PHP Rewrites Mode</span>
            <?php endif; ?>
        </div>
        
        <form method="post" action="options.php">
            <?php settings_fields('mct_settings_group'); ?>
            
            <!-- Google Cloud Settings -->
            <h2><?php _e('Google Cloud Settings', 'multilang-cloud-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="google_api_key"><?php _e('Google API Key', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('google_api_key', 50, 'text', 'Your Google Cloud Translation API key'); ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="google_project_id"><?php _e('Google Project ID', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('google_project_id', 40, 'text', 'Your Google Cloud project ID'); ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="gcs_bucket"><?php _e('GCS Bucket Name', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('gcs_bucket', 40, 'text', 'Google Cloud Storage bucket for caching translations'); ?>
                    </td>
                </tr>
            </table>
            
            <!-- Language Settings -->
            <h2><?php _e('Language Settings', 'multilang-cloud-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="active_languages"><?php _e('Active Languages', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('active_languages', 40, 'text', 'Comma-separated ISO language codes (e.g., en,fr,es,de)'); ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="default_language"><?php _e('Default Language', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('default_language', 10, 'text', 'Primary language of your content (e.g., en)'); ?>
                    </td>
                </tr>
            </table>
            
            <!-- Translation Mode -->
            <h2><?php _e('Translation Mode', 'multilang-cloud-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="translation_mode"><?php _e('Mode', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <select name="mct_settings[translation_mode]" class="regular-text">
                            <option value="cloudflare" <?php selected($current_mode, 'cloudflare'); ?>>
                                <?php _e('Cloudflare Workers (Subdomain routing)', 'multilang-cloud-translate'); ?>
                            </option>
                            <option value="php" <?php selected($current_mode, 'php'); ?>>
                                <?php _e('PHP Rewrites (Query parameters)', 'multilang-cloud-translate'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <strong><?php _e('Cloudflare Workers:', 'multilang-cloud-translate'); ?></strong> <?php _e('Edge-level caching, subdomain routing (fr.example.com), requires Cloudflare Worker deployment', 'multilang-cloud-translate'); ?><br>
                            <strong><?php _e('PHP Rewrites:', 'multilang-cloud-translate'); ?></strong> <?php _e('Native WordPress handling, query parameters (?lang=fr), works on any hosting', 'multilang-cloud-translate'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <!-- IP Detection & Auto-Redirect -->
            <h2><?php _e('Auto-Detection & Redirection', 'multilang-cloud-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="ipinfo_token"><?php _e('IPinfo.io Token', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('ipinfo_token', 40, 'text', 'Optional: Used for IP-based language detection (get free token at ipinfo.io)'); ?>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="enable_ip_redirect"><?php _e('Enable IP-based Redirect', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="mct_settings[enable_ip_redirect]" value="1" <?php checked(!empty($options['enable_ip_redirect'])); ?>>
                            <?php _e('Automatically redirect visitors based on their IP location (PHP mode only)', 'multilang-cloud-translate'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="auto_redirect"><?php _e('Enable Auto Redirect', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="mct_settings[auto_redirect]" value="1" <?php checked(!empty($options['auto_redirect'])); ?>>
                            <?php _e('Automatically redirect users to their preferred language', 'multilang-cloud-translate'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <!-- Cache Settings -->
            <h2><?php _e('Cache Settings', 'multilang-cloud-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="cache_ttl"><?php _e('Cache TTL (seconds)', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('cache_ttl', 10, 'number', 'How long to cache translations (default: 3600 = 1 hour)'); ?>
                    </td>
                </tr>
            </table>
            
            <!-- Analytics Settings -->
            <h2><?php _e('Analytics (Optional)', 'multilang-cloud-translate'); ?></h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="enable_analytics"><?php _e('Enable GA4 Integration', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" name="mct_settings[enable_analytics]" value="1" <?php checked(!empty($options['enable_analytics'])); ?>>
                            <?php _e('Send translation events to Google Analytics 4', 'multilang-cloud-translate'); ?>
                        </label>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ga_measurement_id"><?php _e('GA4 Measurement ID', 'multilang-cloud-translate'); ?></label>
                    </th>
                    <td>
                        <?php mct_field_input('ga_measurement_id', 20, 'text', 'Your GA4 Measurement ID (e.g., G-XXXXXXXXXX)'); ?>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(__('Save Settings', 'multilang-cloud-translate')); ?>
        </form>
        
        <!-- Cache Management -->
        <hr>
        <h2><?php _e('Cache Management', 'multilang-cloud-translate'); ?></h2>
        <p><?php _e('Clear all cached translations stored in Google Cloud Storage. This will force fresh translations on next page load.', 'multilang-cloud-translate'); ?></p>
        <form method="post" action="">
            <?php wp_nonce_field('mct_clear_cache_action', 'mct_clear_cache_nonce'); ?>
            <input type="hidden" name="mct_clear_cache" value="1">
            <?php submit_button(__('Clear Translation Cache', 'multilang-cloud-translate'), 'delete', 'submit', false); ?>
        </form>
        
        <!-- Translation Logs -->
        <hr>
        <h2><?php _e('Translation Logs (Last 50)', 'multilang-cloud-translate'); ?></h2>
        <div class="mct-logs-container">
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'multilang-cloud-translate'); ?></th>
                        <th><?php _e('Source Lang', 'multilang-cloud-translate'); ?></th>
                        <th><?php _e('Target Lang', 'multilang-cloud-translate'); ?></th>
                        <th><?php _e('Characters', 'multilang-cloud-translate'); ?></th>
                        <th><?php _e('URL', 'multilang-cloud-translate'); ?></th>
                        <th><?php _e('IP Address', 'multilang-cloud-translate'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo esc_html($log->timestamp); ?></td>
                                <td><span class="lang-badge"><?php echo esc_html(strtoupper($log->source_lang)); ?></span></td>
                                <td><span class="lang-badge"><?php echo esc_html(strtoupper($log->target_lang)); ?></span></td>
                                <td><?php echo number_format($log->char_count); ?></td>
                                <td><a href="<?php echo esc_url($log->source_url); ?>" target="_blank"><?php echo esc_html(wp_trim_words($log->source_url, 8, '...')); ?></a></td>
                                <td><?php echo esc_html($log->user_ip); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <?php _e('No translation logs yet. Translations will appear here once content is translated.', 'multilang-cloud-translate'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- System Info -->
        <hr>
        <h2><?php _e('System Information', 'multilang-cloud-translate'); ?></h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <td><strong><?php _e('Plugin Version:', 'multilang-cloud-translate'); ?></strong></td>
                    <td><?php echo MCT_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('PHP Version:', 'multilang-cloud-translate'); ?></strong></td>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('WordPress Version:', 'multilang-cloud-translate'); ?></strong></td>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <td><strong><?php _e('Google Credentials File:', 'multilang-cloud-translate'); ?></strong></td>
                    <td>
                        <?php if (file_exists(WP_CONTENT_DIR . '/google-credentials.json')): ?>
                            <span style="color: green;">✓ <?php _e('Found', 'multilang-cloud-translate'); ?></span>
                        <?php else: ?>
                            <span style="color: red;">✗ <?php _e('Missing', 'multilang-cloud-translate'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong><?php _e('Composer Dependencies:', 'multilang-cloud-translate'); ?></strong></td>
                    <td>
                        <?php if (file_exists(MCT_PATH . 'vendor/autoload.php')): ?>
                            <span style="color: green;">✓ <?php _e('Installed', 'multilang-cloud-translate'); ?></span>
                        <?php else: ?>
                            <span style="color: red;">✗ <?php _e('Not Installed', 'multilang-cloud-translate'); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <style>
        .mct-settings-wrap { max-width: 1200px; }
        .mct-mode-badge { margin: 20px 0; }
        .badge { padding: 8px 15px; border-radius: 4px; font-weight: bold; display: inline-block; }
        .badge-cloudflare { background: #f6821f; color: white; }
        .badge-php { background: #4b5563; color: white; }
        .lang-badge { background: #e0e7ff; color: #3730a3; padding: 3px 8px; border-radius: 3px; font-size: 11px; font-weight: bold; }
        .mct-logs-container { margin-top: 15px; overflow-x: auto; }
    </style>
    <?php
}

/**
 * Handle cache clearing request
 */
add_action('admin_init', 'mct_handle_clear_cache_request');
function mct_handle_clear_cache_request() {
    if (!isset($_POST['mct_clear_cache']) || !check_admin_referer('mct_clear_cache_action', 'mct_clear_cache_nonce')) {
        return;
    }
    
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    
    if (empty($bucketName)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo __('Error: GCS bucket name is not configured.', 'multilang-cloud-translate');
            echo '</p></div>';
        });
        return;
    }
    
    try {
        $creds_path = WP_CONTENT_DIR . '/google-credentials.json';
        
        if (!file_exists($creds_path)) {
            throw new Exception(__('Google credentials file not found.', 'multilang-cloud-translate'));
        }
        
        $storage = new StorageClient([
            'keyFilePath' => $creds_path
        ]);
        
        $bucket = $storage->bucket($bucketName);
        $objects = $bucket->objects(['prefix' => 'translations/']);
        
        $count = 0;
        foreach ($objects as $object) {
            $object->delete();
            $count++;
        }
        
        add_action('admin_notices', function() use ($count) {
            echo '<div class="notice notice-success is-dismissible"><p>';
            printf(__('Translation cache cleared successfully! %d files deleted.', 'multilang-cloud-translate'), $count);
            echo '</p></div>';
        });
        
    } catch (Exception $e) {
        add_action('admin_notices', function() use ($e) {
            echo '<div class="notice notice-error is-dismissible"><p>';
            echo __('Cache clear failed: ', 'multilang-cloud-translate') . esc_html($e->getMessage());
            echo '</p></div>';
        });
    }
}
