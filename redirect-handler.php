<?php
/**
 * Redirect Handler
 * Manages IP-based language detection and automatic redirection
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * PHP-based IP language detection and redirect (when Cloudflare Workers mode is off)
 */
add_action('init', 'mct_handle_auto_redirect', 1);
function mct_handle_auto_redirect() {
    // Skip in admin
    if (is_admin()) {
        return;
    }
    
    // Skip for AJAX requests
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    
    // Skip for REST API requests
    if (defined('REST_REQUEST') && REST_REQUEST) {
        return;
    }
    
    $options = get_option('mct_settings');
    
    // Only run in PHP mode
    $mode = $options['translation_mode'] ?? 'php';
    if ($mode !== 'php') {
        return;
    }
    
    // Check if auto-redirect is enabled
    if (empty($options['enable_ip_redirect'])) {
        return;
    }
    
    // Don't redirect if user already chose a language
    if (isset($_COOKIE['mct_lang']) || isset($_GET['lang'])) {
        return;
    }
    
    // Don't redirect if already on a language subdomain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = explode('.', $host)[0] ?? '';
    $active_langs = explode(',', $options['active_languages'] ?? 'en');
    
    if (in_array($subdomain, $active_langs)) {
        return;
    }
    
    // Exclude specific paths
    $excluded_paths = array('/wp-admin', '/wp-login.php', '/wp-json', '/xmlrpc.php');
    $current_path = $_SERVER['REQUEST_URI'] ?? '';
    
    foreach ($excluded_paths as $excluded) {
        if (strpos($current_path, $excluded) === 0) {
            return;
        }
    }
    
    // Get IPinfo token
    $ipinfo_token = $options['ipinfo_token'] ?? '';
    
    if (empty($ipinfo_token)) {
        // Fallback to browser language if no IP detection
        $target_lang = mct_detect_browser_language();
    } else {
        // Detect language from IP
        $user_ip = mct_get_user_ip();
        
        if (!$user_ip || $user_ip === 'unknown' || $user_ip === '127.0.0.1') {
            // Fallback to browser language
            $target_lang = mct_detect_browser_language();
        } else {
            $target_lang = mct_detect_lang_from_ip($user_ip, $ipinfo_token);
        }
    }
    
    if (!$target_lang) {
        return;
    }
    
    // Check if detected language is in active languages
    if (!in_array($target_lang, $active_langs)) {
        return;
    }
    
    // Don't redirect to default language
    $default_lang = $options['default_language'] ?? 'en';
    if ($target_lang === $default_lang) {
        return;
    }
    
    // Set cookie to remember user preference (30 days)
    setcookie('mct_lang', $target_lang, time() + (DAY_IN_SECONDS * 30), '/');
    
    // Build redirect URL
    $current_url = home_url($_SERVER['REQUEST_URI']);
    $redirect_url = add_query_arg('lang', $target_lang, $current_url);
    
    // Perform redirect
    wp_redirect($redirect_url, 302);
    exit;
}

/**
 * Detect language from IP using IPinfo.io
 * 
 * @param string $ip User IP address
 * @param string $token IPinfo.io API token
 * @return string|null Detected language code or null
 */
function mct_detect_lang_from_ip($ip, $token) {
    // Check cache first (valid for 24 hours)
    $cache_key = 'mct_ip_lang_' . md5($ip);
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    // Call IPinfo.io API
    $api_url = "https://ipinfo.io/{$ip}?token={$token}";
    $response = wp_remote_get($api_url, array(
        'timeout' => 5,
        'user-agent' => 'WordPress/MultilangCloudTranslate'
    ));
    
    if (is_wp_error($response)) {
        error_log('MCT IP Detection Error: ' . $response->get_error_message());
        return null;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['country'])) {
        return null;
    }
    
    $country = strtoupper($data['country']);
    
    // Map country codes to language codes
    $country_lang_map = array(
        // Major countries
        'US' => 'en',
        'GB' => 'en',
        'CA' => 'en',
        'AU' => 'en',
        'NZ' => 'en',
        'IE' => 'en',
        'FR' => 'fr',
        'BE' => 'fr', // Belgium - could be Dutch
        'CH' => 'fr', // Switzerland - could be German/Italian
        'ES' => 'es',
        'MX' => 'es',
        'AR' => 'es',
        'CL' => 'es',
        'CO' => 'es',
        'PE' => 'es',
        'VE' => 'es',
        'DE' => 'de',
        'AT' => 'de',
        'IT' => 'it',
        'PT' => 'pt',
        'BR' => 'pt',
        'RU' => 'ru',
        'UA' => 'ru',
        'CN' => 'zh',
        'TW' => 'zh',
        'HK' => 'zh',
        'JP' => 'ja',
        'KR' => 'ko',
        'IN' => 'en',
        'PH' => 'en',
        'SG' => 'en',
        'ZA' => 'en',
        'NL' => 'nl',
        'SE' => 'sv',
        'NO' => 'no',
        'DK' => 'da',
        'FI' => 'fi',
        'PL' => 'pl',
        'TR' => 'tr',
        'GR' => 'el',
        'IL' => 'he',
        'SA' => 'ar',
        'AE' => 'ar',
        'EG' => 'ar'
    );
    
    $detected_lang = $country_lang_map[$country] ?? 'en';
    
    // Cache for 24 hours
    set_transient($cache_key, $detected_lang, DAY_IN_SECONDS);
    
    return $detected_lang;
}

/**
 * Detect language from browser Accept-Language header
 * 
 * @return string|null Detected language code or null
 */
function mct_detect_browser_language() {
    if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        return null;
    }
    
    $accept_lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
    
    // Parse Accept-Language header
    // Format: en-US,en;q=0.9,fr;q=0.8
    preg_match_all('/([a-z]{2})(?:-[A-Z]{2})?(;q=([0-9.]+))?/', $accept_lang, $matches);
    
    if (empty($matches[1])) {
        return null;
    }
    
    // Get the first (highest priority) language
    $browser_lang = strtolower($matches[1][0]);
    
    // Validate against active languages
    $options = get_option('mct_settings');
    $active_langs = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    
    if (in_array($browser_lang, $active_langs)) {
        return $browser_lang;
    }
    
    return null;
}

/**
 * Handle manual language selection
 * When user clicks language switcher
 */
add_action('init', 'mct_handle_manual_language_selection');
function mct_handle_manual_language_selection() {
    if (isset($_GET['set_lang'])) {
        $lang = sanitize_text_field($_GET['set_lang']);
        
        // Validate language
        $options = get_option('mct_settings');
        $active_langs = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
        
        if (in_array($lang, $active_langs)) {
            // Set cookie (30 days)
            setcookie('mct_lang', $lang, time() + (DAY_IN_SECONDS * 30), '/');
            
            // Redirect to current page with new language
            $redirect_url = remove_query_arg('set_lang');
            $redirect_url = add_query_arg('lang', $lang, $redirect_url);
            
            wp_redirect($redirect_url);
            exit;
        }
    }
}

/**
 * Add rewrite rules for language parameters
 */
add_action('init', 'mct_add_language_rewrite_rules');
function mct_add_language_rewrite_rules() {
    $options = get_option('mct_settings');
    $mode = $options['translation_mode'] ?? 'php';
    
    // Only add rewrites in PHP mode
    if ($mode !== 'php') {
        return;
    }
    
    // Add rewrite tag for language
    add_rewrite_tag('%lang%', '([a-z]{2})');
    
    // Add rewrite rules for language paths (optional)
    // Example: /fr/about/ -> /?lang=fr&pagename=about
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    
    foreach ($languages as $lang) {
        // Posts
        add_rewrite_rule(
            "^{$lang}/([^/]+)/?$",
            'index.php?lang=' . $lang . '&name=$matches[1]',
            'top'
        );
        
        // Pages
        add_rewrite_rule(
            "^{$lang}/(.+?)/?$",
            'index.php?lang=' . $lang . '&pagename=$matches[1]',
            'top'
        );
    }
}

/**
 * Clear language preference cookie
 */
function mct_clear_language_cookie() {
    setcookie('mct_lang', '', time() - 3600, '/');
}

/**
 * Get user's preferred language
 * Checks multiple sources in order of priority
 * 
 * @return string Language code
 */
function mct_get_preferred_language() {
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    
    // 1. Check cookie (user's explicit choice)
    if (isset($_COOKIE['mct_lang'])) {
        return sanitize_text_field($_COOKIE['mct_lang']);
    }
    
    // 2. Check query parameter
    if (isset($_GET['lang'])) {
        return sanitize_text_field($_GET['lang']);
    }
    
    // 3. Check subdomain
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $subdomain = explode('.', $host)[0] ?? '';
    $active_langs = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    
    if (in_array($subdomain, $active_langs)) {
        return $subdomain;
    }
    
    // 4. Return default
    return $default_lang;
}

/**
 * Check if current page should be excluded from translation/redirection
 * 
 * @return bool True if should be excluded
 */
function mct_is_excluded_path() {
    $excluded = array(
        '/wp-admin',
        '/wp-login.php',
        '/wp-json',
        '/wp-cron.php',
        '/xmlrpc.php'
    );
    
    $current_path = $_SERVER['REQUEST_URI'] ?? '';
    
    foreach ($excluded as $path) {
        if (strpos($current_path, $path) === 0) {
            return true;
        }
    }
    
    return false;
}
