<?php
/**
 * Sitemap Handler
 * Extends WordPress sitemaps with multilingual URLs
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add translated URLs to WordPress sitemap
 */
add_filter('wp_sitemaps_posts_entry', 'mct_add_translated_sitemap_entries', 10, 3);
add_filter('wp_sitemaps_pages_entry', 'mct_add_translated_sitemap_entries', 10, 3);

function mct_add_translated_sitemap_entries($entry, $post, $post_type) {
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    $mode = $options['translation_mode'] ?? 'php';
    
    if (empty($languages)) {
        return $entry;
    }
    
    $base_url = get_permalink($post);
    
    // Add alternate hreflang URLs
    $entry['alternates'] = array();
    
    foreach ($languages as $lang) {
        $translated_url = mct_build_translated_url($base_url, $lang, $mode);
        $entry['alternates'][] = array(
            'hreflang' => strtolower($lang),
            'href' => $translated_url
        );
    }
    
    return $entry;
}

/**
 * Render alternate links in sitemap XML
 */
add_action('wp_sitemaps_render_entry', 'mct_render_alternate_links_in_sitemap', 10, 2);
function mct_render_alternate_links_in_sitemap($url_entry, $entry) {
    if (empty($entry['alternates'])) {
        return;
    }
    
    foreach ($entry['alternates'] as $alt) {
        printf(
            "\t<xhtml:link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n",
            esc_attr($alt['hreflang']),
            esc_url($alt['href'])
        );
    }
}

/**
 * Add xhtml namespace to sitemap
 */
add_filter('wp_sitemaps_stylesheet_content', 'mct_add_xhtml_namespace');
function mct_add_xhtml_namespace($content) {
    $content = str_replace(
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
        $content
    );
    return $content;
}

/**
 * Filter sitemap index to add language-specific sitemaps
 */
add_filter('wp_sitemaps_index', 'mct_add_language_sitemaps', 10, 1);
function mct_add_language_sitemaps($sitemap_entries) {
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    $default_lang = $options['default_language'] ?? 'en';
    
    // Create language-specific sitemap entries (optional enhancement)
    // This is for future implementation if needed
    
    return $sitemap_entries;
}

/**
 * Generate custom sitemap for specific language (optional)
 * This can be called via a custom endpoint
 */
function mct_generate_language_sitemap($language) {
    $options = get_option('mct_settings');
    $mode = $options['translation_mode'] ?? 'php';
    
    // Get all posts and pages
    $posts = get_posts(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'modified',
        'order' => 'DESC'
    ));
    
    // Build sitemap XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
    
    foreach ($posts as $post) {
        $url = mct_build_translated_url(get_permalink($post), $language, $mode);
        $modified = get_post_modified_time('c', false, $post);
        
        $xml .= '<url>';
        $xml .= '<loc>' . esc_url($url) . '</loc>';
        $xml .= '<lastmod>' . $modified . '</lastmod>';
        $xml .= '<changefreq>weekly</changefreq>';
        $xml .= '<priority>0.8</priority>';
        $xml .= '</url>';
    }
    
    $xml .= '</urlset>';
    
    return $xml;
}

/**
 * Register custom sitemap endpoint (optional)
 */
add_action('init', 'mct_register_sitemap_endpoint');
function mct_register_sitemap_endpoint() {
    add_rewrite_rule(
        '^sitemap-([a-z]{2})\.xml$',
        'index.php?mct_sitemap_lang=$matches[1]',
        'top'
    );
    add_rewrite_tag('%mct_sitemap_lang%', '([a-z]{2})');
}

/**
 * Handle custom sitemap requests
 */
add_action('template_redirect', 'mct_handle_sitemap_request');
function mct_handle_sitemap_request() {
    $lang = get_query_var('mct_sitemap_lang');
    
    if (empty($lang)) {
        return;
    }
    
    // Validate language
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    
    if (!in_array($lang, $languages)) {
        return;
    }
    
    // Generate and output sitemap
    $sitemap_xml = mct_generate_language_sitemap($lang);
    
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: noindex, follow');
    echo $sitemap_xml;
    exit;
}
