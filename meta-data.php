<?php
/**
 * Metadata Handler
 * Handles metadata translation and multilingual content attributes
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add language attribute to HTML tag
 */
add_filter('language_attributes', 'mct_language_attributes');
function mct_language_attributes($output) {
    $current_lang = mct_get_current_lang();
    
    // Replace or add lang attribute
    if (strpos($output, 'lang=') !== false) {
        $output = preg_replace('/lang="[^"]*"/', 'lang="' . esc_attr($current_lang) . '"', $output);
    } else {
        $output .= ' lang="' . esc_attr($current_lang) . '"';
    }
    
    return $output;
}

/**
 * Add language class to body tag
 */
add_filter('body_class', 'mct_body_class');
function mct_body_class($classes) {
    $current_lang = mct_get_current_lang();
    $classes[] = 'lang-' . $current_lang;
    $classes[] = 'mct-translated';
    
    $options = get_option('mct_settings');
    $mode = $options['translation_mode'] ?? 'php';
    $classes[] = 'mct-mode-' . $mode;
    
    return $classes;
}

/**
 * Modify post meta for translations
 */
add_filter('get_post_metadata', 'mct_translate_post_meta', 10, 4);
function mct_translate_post_meta($value, $object_id, $meta_key, $single) {
    // Skip in admin
    if (is_admin()) {
        return $value;
    }
    
    $current_lang = mct_get_current_lang();
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    
    // Don't translate if same language
    if ($current_lang === $default_lang) {
        return $value;
    }
    
    // Only translate specific meta keys
    $translatable_meta = array(
        '_yoast_wpseo_title',
        '_yoast_wpseo_metadesc',
        '_yoast_wpseo_opengraph-title',
        '_yoast_wpseo_opengraph-description',
        '_yoast_wpseo_twitter-title',
        '_yoast_wpseo_twitter-description'
    );
    
    if (!in_array($meta_key, $translatable_meta)) {
        return $value;
    }
    
    // Get original value
    $original = get_metadata('post', $object_id, $meta_key, true);
    
    if (empty($original)) {
        return $value;
    }
    
    // Translate
    $translated = mct_translate($original, $current_lang, $default_lang);
    
    // Return translated value
    return $single ? $translated : array($translated);
}

/**
 * Add translated metadata to posts
 */
add_action('wp_head', 'mct_add_translated_metadata', 2);
function mct_add_translated_metadata() {
    if (is_admin()) {
        return;
    }
    
    global $post;
    
    if (!$post) {
        return;
    }
    
    $current_lang = mct_get_current_lang();
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    
    // Add Content-Language header
    header('Content-Language: ' . $current_lang);
    
    // Add language meta tag
    echo '<meta http-equiv="content-language" content="' . esc_attr($current_lang) . '" />' . "\n";
    
    // Add translation metadata
    echo '<meta name="mct:translated" content="true" />' . "\n";
    echo '<meta name="mct:source_language" content="' . esc_attr($default_lang) . '" />' . "\n";
    echo '<meta name="mct:target_language" content="' . esc_attr($current_lang) . '" />' . "\n";
}

/**
 * Modify RSS feed for specific languages
 */
add_filter('the_content_feed', 'mct_translate_feed_content');
function mct_translate_feed_content($content) {
    $target_lang = $_GET['lang'] ?? mct_get_default_language();
    $source_lang = mct_get_default_language();
    
    if ($target_lang === $source_lang) {
        return $content;
    }
    
    return mct_translate($content, $target_lang, $source_lang);
}

/**
 * Add language parameter to feed URLs
 */
add_filter('feed_link', 'mct_add_lang_to_feed_link', 10, 2);
function mct_add_lang_to_feed_link($output, $feed) {
    $current_lang = mct_get_current_lang();
    $default_lang = mct_get_default_language();
    
    if ($current_lang !== $default_lang) {
        $output = add_query_arg('lang', $current_lang, $output);
    }
    
    return $output;
}

/**
 * Translate widget content
 */
add_filter('widget_text', 'mct_translate_widget_text', 10, 2);
function mct_translate_widget_text($text, $instance) {
    if (is_admin()) {
        return $text;
    }
    
    $current_lang = mct_get_current_lang();
    $default_lang = mct_get_default_language();
    
    if ($current_lang === $default_lang) {
        return $text;
    }
    
    return mct_translate($text, $current_lang, $default_lang);
}

/**
 * Translate widget title
 */
add_filter('widget_title', 'mct_translate_widget_title', 10, 3);
function mct_translate_widget_title($title, $instance, $id_base) {
    if (is_admin() || empty($title)) {
        return $title;
    }
    
    $current_lang = mct_get_current_lang();
    $default_lang = mct_get_default_language();
    
    if ($current_lang === $default_lang) {
        return $title;
    }
    
    return mct_translate($title, $current_lang, $default_lang);
}

/**
 * Translate menu items
 */
add_filter('wp_nav_menu_items', 'mct_translate_menu_items', 10, 2);
function mct_translate_menu_items($items, $args) {
    if (is_admin()) {
        return $items;
    }
    
    $current_lang = mct_get_current_lang();
    $default_lang = mct_get_default_language();
    
    if ($current_lang === $default_lang) {
        return $items;
    }
    
    // Parse menu HTML and translate text nodes
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="UTF-8">' . $items, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    $xpath = new DOMXPath($dom);
    $textNodes = $xpath->query('//text()');
    
    foreach ($textNodes as $node) {
        $text = trim($node->nodeValue);
        
        if (!empty($text)) {
            $translated = mct_translate($text, $current_lang, $default_lang);
            $node->nodeValue = $translated;
        }
    }
    
    return $dom->saveHTML();
}

/**
 * Add language information to REST API responses
 */
add_action('rest_api_init', 'mct_register_rest_fields');
function mct_register_rest_fields() {
    $post_types = get_post_types(array('public' => true), 'names');
    
    foreach ($post_types as $post_type) {
        register_rest_field($post_type, 'mct_languages', array(
            'get_callback' => function($object) {
                $options = get_option('mct_settings');
                $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
                $mode = $options['translation_mode'] ?? 'php';
                
                $urls = array();
                
                foreach ($languages as $lang) {
                    $urls[$lang] = mct_build_translated_url(get_permalink($object['id']), $lang, $mode);
                }
                
                return $urls;
            },
            'schema' => array(
                'description' => 'Available translations',
                'type' => 'object'
            )
        ));
    }
}

/**
 * Translate search results
 */
add_filter('the_search_query', 'mct_translate_search_query');
function mct_translate_search_query($query) {
    if (is_admin()) {
        return $query;
    }
    
    $current_lang = mct_get_current_lang();
    $default_lang = mct_get_default_language();
    
    if ($current_lang === $default_lang) {
        return $query;
    }
    
    // Don't translate the query itself, just return it
    // Actual results are translated via the_content filter
    return $query;
}

/**
 * Add language switcher to admin bar
 */
add_action('admin_bar_menu', 'mct_admin_bar_language_switcher', 100);
function mct_admin_bar_language_switcher($wp_admin_bar) {
    if (!is_admin() && is_singular()) {
        $options = get_option('mct_settings');
        $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
        $current_lang = mct_get_current_lang();
        $mode = $options['translation_mode'] ?? 'php';
        
        global $post;
        
        if (!$post) {
            return;
        }
        
        // Add parent menu
        $wp_admin_bar->add_node(array(
            'id' => 'mct-language-switcher',
            'title' => '🌐 ' . strtoupper($current_lang),
            'href' => '#'
        ));
        
        // Add language options
        foreach ($languages as $lang) {
            $url = mct_build_translated_url(get_permalink($post), $lang, $mode);
            
            $wp_admin_bar->add_node(array(
                'parent' => 'mct-language-switcher',
                'id' => 'mct-lang-' . $lang,
                'title' => mct_get_language_name($lang) . ($lang === $current_lang ? ' ✓' : ''),
                'href' => $url
            ));
        }
    }
}

/**
 * Store original content for reference
 * Useful for comparing translations
 */
add_action('save_post', 'mct_save_original_content', 10, 3);
function mct_save_original_content($post_id, $post, $update) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    
    $default_lang = mct_get_default_language();
    
    // Store original content hash for cache invalidation
    $content_hash = md5($post->post_content);
    update_post_meta($post_id, '_mct_content_hash', $content_hash);
    update_post_meta($post_id, '_mct_original_lang', $default_lang);
    update_post_meta($post_id, '_mct_last_updated', current_time('mysql'));
}
