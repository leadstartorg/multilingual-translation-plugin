<?php
/**
 * Translation Helper Functions
 * Utility functions for translation and language management
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main translation function with caching
 * This is a wrapper that combines API call and caching
 * 
 * @param string $text Text to translate
 * @param string $targetLang Target language
 * @param string $sourceLang Source language
 * @return string Translated text
 */
function mct_translate($text, $targetLang, $sourceLang = 'en') {
    // Don't translate empty text
    if (empty(trim($text))) {
        return $text;
    }
    
    // Don't translate if same language
    if ($targetLang === $sourceLang) {
        return $text;
    }
    
    // Create cache key
    $cache_key = md5($sourceLang . ':' . $targetLang . ':' . $text);
    
    // Try GCS cache first
    $cached = mct_get_translation_from_gcs($cache_key, $targetLang);
    
    if ($cached) {
        return $cached;
    }
    
    // Translate using API
    $translated = mct_translate_text($text, $targetLang, $sourceLang);
    
    // Save to cache
    if ($translated && $translated !== $text) {
        mct_save_translation_to_gcs($cache_key, $targetLang, $translated);
    }
    
    return $translated ?: $text;
}

/**
 * Translate array of strings
 * 
 * @param array $texts Array of texts to translate
 * @param string $targetLang Target language
 * @param string $sourceLang Source language
 * @return array Translated texts
 */
function mct_translate_array($texts, $targetLang, $sourceLang = 'en') {
    if (empty($texts) || !is_array($texts)) {
        return $texts;
    }
    
    $translated = array();
    
    foreach ($texts as $key => $text) {
        $translated[$key] = mct_translate($text, $targetLang, $sourceLang);
    }
    
    return $translated;
}

/**
 * Translate post title
 * 
 * @param int|WP_Post $post Post ID or object
 * @param string $targetLang Target language
 * @return string Translated title
 */
function mct_translate_post_title($post, $targetLang) {
    $post = get_post($post);
    
    if (!$post) {
        return '';
    }
    
    $options = get_option('mct_settings');
    $sourceLang = $options['default_language'] ?? 'en';
    
    return mct_translate($post->post_title, $targetLang, $sourceLang);
}

/**
 * Translate post excerpt
 * 
 * @param int|WP_Post $post Post ID or object
 * @param string $targetLang Target language
 * @return string Translated excerpt
 */
function mct_translate_post_excerpt($post, $targetLang) {
    $post = get_post($post);
    
    if (!$post) {
        return '';
    }
    
    $options = get_option('mct_settings');
    $sourceLang = $options['default_language'] ?? 'en';
    
    $excerpt = $post->post_excerpt;
    
    if (empty($excerpt)) {
        $excerpt = wp_trim_words($post->post_content, 55, '...');
    }
    
    return mct_translate($excerpt, $targetLang, $sourceLang);
}

/**
 * Get available languages
 * 
 * @return array Array of language codes
 */
function mct_get_available_languages() {
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    
    return $languages;
}

/**
 * Get language name from code
 * 
 * @param string $code Language code
 * @return string Language name
 */
function mct_get_language_name($code) {
    $names = array(
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어',
        'ar' => 'العربية',
        'nl' => 'Nederlands',
        'pl' => 'Polski',
        'sv' => 'Svenska',
        'no' => 'Norsk',
        'da' => 'Dansk',
        'fi' => 'Suomi',
        'tr' => 'Türkçe',
        'el' => 'Ελληνικά',
        'he' => 'עברית',
        'hi' => 'हिन्दी',
        'th' => 'ไทย',
        'vi' => 'Tiếng Việt',
        'id' => 'Bahasa Indonesia',
        'ms' => 'Bahasa Melayu',
        'cs' => 'Čeština',
        'sk' => 'Slovenčina',
        'hu' => 'Magyar',
        'ro' => 'Română',
        'bg' => 'Български',
        'uk' => 'Українська'
    );
    
    return $names[$code] ?? strtoupper($code);
}

/**
 * Check if a language is active
 * 
 * @param string $lang Language code
 * @return bool True if active
 */
function mct_is_language_active($lang) {
    $languages = mct_get_available_languages();
    return in_array($lang, $languages);
}

/**
 * Get default language
 * 
 * @return string Default language code
 */
function mct_get_default_language() {
    $options = get_option('mct_settings');
    return $options['default_language'] ?? 'en';
}

/**
 * Strip HTML tags but preserve structure for translation
 * 
 * @param string $html HTML content
 * @return string Cleaned HTML
 */
function mct_prepare_html_for_translation($html) {
    // Remove script and style tags completely
    $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
    
    return $html;
}

/**
 * Create a translation job for batch processing
 * Useful for pre-generating translations
 * 
 * @param array $post_ids Array of post IDs to translate
 * @param string $targetLang Target language
 * @return bool Success status
 */
function mct_create_translation_job($post_ids, $targetLang) {
    if (empty($post_ids) || !is_array($post_ids)) {
        return false;
    }
    
    $options = get_option('mct_settings');
    $sourceLang = $options['default_language'] ?? 'en';
    
    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);
        
        if (!$post) {
            continue;
        }
        
        // Translate title
        $translated_title = mct_translate($post->post_title, $targetLang, $sourceLang);
        
        // Translate content
        $translated_content = mct_translate($post->post_content, $targetLang, $sourceLang);
        
        // Create cache keys and store
        $url = get_permalink($post_id);
        $cache_key = md5($url . '_' . $targetLang . '_' . md5($post->post_content));
        
        mct_save_translation_to_gcs($cache_key, $targetLang, $translated_content);
    }
    
    return true;
}

/**
 * Get translation progress for a language
 * 
 * @param string $lang Language code
 * @return array Progress information
 */
function mct_get_translation_progress($lang) {
    global $wpdb;
    
    // Count total posts
    $total_posts = $wpdb->get_var("
        SELECT COUNT(*) 
        FROM {$wpdb->posts} 
        WHERE post_status = 'publish' 
        AND post_type IN ('post', 'page')
    ");
    
    // Count translated posts (from logs)
    $table = $wpdb->prefix . 'mct_translation_logs';
    $translated_posts = $wpdb->get_var($wpdb->prepare("
        SELECT COUNT(DISTINCT source_url) 
        FROM {$table} 
        WHERE target_lang = %s
    ", $lang));
    
    $percentage = $total_posts > 0 ? round(($translated_posts / $total_posts) * 100, 2) : 0;
    
    return array(
        'total' => intval($total_posts),
        'translated' => intval($translated_posts),
        'percentage' => $percentage
    );
}

/**
 * WP-CLI command support (if WP-CLI is available)
 */
if (defined('WP_CLI') && WP_CLI) {
    /**
     * Translate all posts to a specific language
     * 
     * ## OPTIONS
     * 
     * <language>
     * : Target language code
     * 
     * ## EXAMPLES
     * 
     *     wp mct translate-all fr
     */
    WP_CLI::add_command('mct translate-all', function($args) {
        $targetLang = $args[0];
        
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1
        ));
        
        $progress = \WP_CLI\Utils\make_progress_bar('Translating posts', count($posts));
        
        foreach ($posts as $post) {
            mct_create_translation_job(array($post->ID), $targetLang);
            $progress->tick();
        }
        
        $progress->finish();
        WP_CLI::success('Translation job completed for ' . count($posts) . ' posts.');
    });
    
    /**
     * Clear translation cache
     * 
     * ## EXAMPLES
     * 
     *     wp mct clear-cache
     */
    WP_CLI::add_command('mct clear-cache', function() {
        $count = mct_clear_all_cache();
        WP_CLI::success("Cleared {$count} cached translations.");
    });
}

/**
 * AJAX handler for live translation preview
 */
add_action('wp_ajax_mct_preview_translation', 'mct_ajax_preview_translation');
function mct_ajax_preview_translation() {
    check_ajax_referer('mct_preview_nonce', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Unauthorized');
    }
    
    $text = wp_unslash($_POST['text'] ?? '');
    $targetLang = sanitize_text_field($_POST['target_lang'] ?? 'en');
    
    if (empty($text)) {
        wp_send_json_error('No text provided');
    }
    
    $translated = mct_translate($text, $targetLang);
    
    wp_send_json_success(array(
        'original' => $text,
        'translated' => $translated,
        'language' => $targetLang
    ));
}

/**
 * Register AJAX action for frontend translation
 */
add_action('wp_ajax_nopriv_mct_preview_translation', 'mct_ajax_preview_translation');
