<?php
/**
 * Plugin Name: Multilang Cloud Translate
 * Plugin URI: https://github.com/leadstartorg/multilang-cloud-translate
 * Description: Custom multilingual plugin using Google Cloud Translation API v3, Google Cloud Storage, Cloudflare Workers, and IP/Browser-based language detection. Supports subdomains (en.example.com) and query parameters (?lang=en).
 * Version: 1.0.0
 * Author: Jessica Kafor
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: multilang-cloud-translate
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('MCT_VERSION', '1.0.0');
define('MCT_PATH', plugin_dir_path(__FILE__));
define('MCT_URL', plugin_dir_url(__FILE__));
define('MCT_BASENAME', plugin_basename(__FILE__));

// Autoload Composer dependencies
if (file_exists(MCT_PATH . 'vendor/autoload.php')) {
    require_once MCT_PATH . 'vendor/autoload.php';
}

// Include core files
require_once MCT_PATH . 'includes/admin-settings.php';
require_once MCT_PATH . 'includes/translation-api.php';
require_once MCT_PATH . 'includes/cache-handler.php';
require_once MCT_PATH . 'includes/seo-tags.php';
require_once MCT_PATH . 'includes/sitemap-handler.php';
require_once MCT_PATH . 'includes/redirect-handler.php';
require_once MCT_PATH . 'includes/logging-native.php';
require_once MCT_PATH . 'includes/translate-functions.php';
require_once MCT_PATH . 'includes/meta-data.php';
require_once MCT_PATH . 'includes/sitemap-extensions.php';

/**
 * Plugin activation hook
 * Creates necessary database tables and sets default options
 */
register_activation_hook(__FILE__, 'mct_activate_plugin');
function mct_activate_plugin() {
    // Create translation logs table
    mct_create_logs_table();
    
    // Set default options if not exists
    $default_options = array(
        'google_api_key' => '',
        'google_project_id' => '',
        'gcs_bucket' => '',
        'active_languages' => 'en,fr,es,de',
        'default_language' => 'en',
        'auto_redirect' => '0',
        'translation_mode' => 'php',
        'ipinfo_token' => '',
        'enable_ip_redirect' => '0',
        'cache_ttl' => '3600',
        'enable_analytics' => '0',
        'ga_measurement_id' => ''
    );
    
    if (!get_option('mct_settings')) {
        add_option('mct_settings', $default_options);
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation hook
 */
register_deactivation_hook(__FILE__, 'mct_deactivate_plugin');
function mct_deactivate_plugin() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Initialize plugin
 * Loads after all plugins are loaded
 */
add_action('plugins_loaded', 'mct_init_plugin');
function mct_init_plugin() {
    // Load text domain for translations
    load_plugin_textdomain('multilang-cloud-translate', false, dirname(MCT_BASENAME) . '/languages');
    
    // Load saved options
    $options = get_option('mct_settings');
    
    // Check for required settings
    if (empty($options['google_api_key'])) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Multilang Cloud Translate:</strong> Please configure your Google API key in ';
            echo '<a href="' . admin_url('options-general.php?page=mct-settings') . '">plugin settings</a>.';
            echo '</p></div>';
        });
    }
    
    if (empty($options['gcs_bucket'])) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Multilang Cloud Translate:</strong> Please configure your GCS bucket in ';
            echo '<a href="' . admin_url('options-general.php?page=mct-settings') . '">plugin settings</a>.';
            echo '</p></div>';
        });
    }
    
    // Check for credentials file
    $creds_path = WP_CONTENT_DIR . '/google-credentials.json';
    if (!file_exists($creds_path)) {
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Multilang Cloud Translate:</strong> Google credentials file not found. ';
            echo 'Please upload <code>google-credentials.json</code> to <code>wp-content/</code> directory.';
            echo '</p></div>';
        });
    }
}

/**
 * Hook into post content to translate
 */
add_filter('the_content', 'mct_translate_post_content', 999);
function mct_translate_post_content($content) {
    // Skip in admin
    if (is_admin()) {
        return $content;
    }
    
    $options = get_option('mct_settings');
    $mode = $options['translation_mode'] ?? 'php';
    
    // Determine target language from header or query parameter
    $target_lang = $_SERVER['HTTP_X_MCT_TARGET_LANG'] ?? $_GET['lang'] ?? mct_get_current_lang();
    $source_lang = $options['default_language'] ?? 'en';
    
    // Don't translate if same language
    if ($target_lang === $source_lang) {
        return $content;
    }
    
    // Don't translate if empty
    if (empty(trim($content))) {
        return $content;
    }
    
    // Get current URL for caching
    $url = get_permalink();
    $cache_key = md5($url . '_' . $target_lang . '_' . md5($content));
    
    // Try to fetch from cache
    $translated = mct_get_translation_from_gcs($cache_key, $target_lang);
    
    if (!$translated) {
        // Translate using Google API
        $translated = mct_translate_text($content, $target_lang, $source_lang);
        
        // Save to cache
        if ($translated && $translated !== $content) {
            mct_save_translation_to_gcs($cache_key, $target_lang, $translated);
        }
    }
    
    // Log translation
    if ($translated && $translated !== $content) {
        mct_log_translation_native($source_lang, $target_lang, strlen($content), $url);
    }
    
    return $translated ?: $content;
}

/**
 * Get current language from various sources
 */
function mct_get_current_lang() {
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    
    // Check cookie first (user preference)
    if (isset($_COOKIE['mct_lang'])) {
        return sanitize_text_field($_COOKIE['mct_lang']);
    }
    
    // Check query parameter
    if (isset($_GET['lang'])) {
        return sanitize_text_field($_GET['lang']);
    }
    
    // Check subdomain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = explode('.', $host)[0] ?? '';
    $active_langs = explode(',', $options['active_languages'] ?? 'en');
    
    if (in_array($subdomain, $active_langs)) {
        return $subdomain;
    }
    
    return $default_lang;
}

/**
 * Add settings link on plugins page
 */
add_filter('plugin_action_links_' . MCT_BASENAME, 'mct_add_action_links');
function mct_add_action_links($links) {
    $settings_link = '<a href="' . admin_url('options-general.php?page=mct-settings') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}

/**
 * Enqueue admin styles
 */
add_action('admin_enqueue_scripts', 'mct_admin_enqueue_scripts');
function mct_admin_enqueue_scripts($hook) {
    if ($hook !== 'settings_page_mct-settings') {
        return;
    }
    
    wp_enqueue_style('mct-admin-styles', MCT_URL . 'assets/admin-style.css', array(), MCT_VERSION);
}
