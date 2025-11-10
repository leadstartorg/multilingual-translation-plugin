<?php
/**
 * Sitemap Extensions
 * Extends Yoast SEO and other sitemap plugins with multilingual support
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if Yoast SEO is active
 */
function mct_is_yoast_active() {
    return defined('WPSEO_VERSION');
}

/**
 * Add hreflang to Yoast SEO sitemap
 */
if (mct_is_yoast_active()) {
    add_filter('wpseo_sitemap_entry', 'mct_yoast_add_hreflang', 10, 3);
}

function mct_yoast_add_hreflang($output, $url, $post) {
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    $mode = $options['translation_mode'] ?? 'php';
    
    if (empty($languages)) {
        return $output;
    }
    
    // Add hreflang links for each language
    $hreflang_links = '';
    
    foreach ($languages as $lang) {
        if (!empty($url)) {
            $translated_url = mct_build_translated_url($url, $lang, $mode);
            $hreflang_links .= sprintf(
                "\t<xhtml:link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n",
                esc_attr($lang),
                esc_url($translated_url)
            );
        }
    }
    
    // Insert hreflang before closing </url> tag
    if (!empty($hreflang_links)) {
        $output = str_replace('</url>', $hreflang_links . '</url>', $output);
    }
    
    return $output;
}

/**
 * Add xhtml namespace to Yoast sitemap
 */
if (mct_is_yoast_active()) {
    add_filter('wpseo_sitemap_urlset', 'mct_yoast_add_xhtml_namespace');
}

function mct_yoast_add_xhtml_namespace($urlset) {
    if (strpos($urlset, 'xmlns:xhtml') === false) {
        $urlset = str_replace(
            '<urlset',
            '<urlset xmlns:xhtml="http://www.w3.org/1999/xhtml"',
            $urlset
        );
    }
    return $urlset;
}

/**
 * Modify Yoast canonical URL for translations
 */
if (mct_is_yoast_active()) {
    add_filter('wpseo_canonical', 'mct_yoast_canonical_url', 10, 1);
}

function mct_yoast_canonical_url($canonical) {
    $target_lang = mct_get_current_lang();
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    $mode = $options['translation_mode'] ?? 'php';
    
    // Don't modify if same language
    if ($target_lang === $default_lang) {
        return $canonical;
    }
    
    // Build translated canonical URL
    return mct_build_translated_url($canonical, $target_lang, $mode);
}

/**
 * Integrate with Rank Math SEO plugin
 */
function mct_is_rankmath_active() {
    return defined('RANK_MATH_VERSION');
}

if (mct_is_rankmath_active()) {
    add_filter('rank_math/frontend/canonical', 'mct_rankmath_canonical_url');
}

function mct_rankmath_canonical_url($canonical) {
    return mct_yoast_canonical_url($canonical);
}

/**
 * Add multilingual support to XML Sitemap plugins
 */
add_action('do_feed_sitemap', 'mct_xml_sitemap_multilingual', 10, 1);
function mct_xml_sitemap_multilingual($for_comments) {
    if ($for_comments) {
        return;
    }
    
    // This hook is for generic XML sitemap plugins
    // Actual implementation depends on the specific plugin
}

/**
 * Generate alternate links for any sitemap format
 * This is a utility function that can be called by theme developers
 */
function mct_get_alternate_links($url) {
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    $mode = $options['translation_mode'] ?? 'php';
    
    $links = array();
    
    foreach ($languages as $lang) {
        $links[$lang] = mct_build_translated_url($url, $lang, $mode);
    }
    
    return $links;
}

/**
 * REST API endpoint for sitemap data
 */
add_action('rest_api_init', 'mct_register_sitemap_endpoint');
function mct_register_sitemap_endpoint() {
    register_rest_route('mct/v1', '/sitemap/(?P<lang>[a-zA-Z]{2})', array(
        'methods' => 'GET',
        'callback' => 'mct_rest_get_sitemap',
        'permission_callback' => '__return_true',
        'args' => array(
            'lang' => array(
                'required' => true,
                'validate_callback' => function($param) {
                    return preg_match('/^[a-z]{2}$/', $param);
                }
            )
        )
    ));
}

/**
 * REST API callback for sitemap
 */
function mct_rest_get_sitemap($request) {
    $lang = $request->get_param('lang');
    
    // Validate language
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    
    if (!in_array($lang, $languages)) {
        return new WP_Error('invalid_language', 'Invalid language code', array('status' => 400));
    }
    
    $mode = $options['translation_mode'] ?? 'php';
    
    // Get all published posts and pages
    $posts = get_posts(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'modified',
        'order' => 'DESC'
    ));
    
    $urls = array();
    
    foreach ($posts as $post) {
        $urls[] = array(
            'loc' => mct_build_translated_url(get_permalink($post), $lang, $mode),
            'lastmod' => get_post_modified_time('c', false, $post),
            'changefreq' => 'weekly',
            'priority' => 0.8
        );
    }
    
    return rest_ensure_response($urls);
}

/**
 * Add sitemap index for all languages
 */
function mct_generate_sitemap_index() {
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    $home_url = trailingslashit(home_url());
    
    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    
    foreach ($languages as $lang) {
        $xml .= '<sitemap>';
        $xml .= '<loc>' . esc_url($home_url . 'sitemap-' . $lang . '.xml') . '</loc>';
        $xml .= '<lastmod>' . date('c') . '</lastmod>';
        $xml .= '</sitemap>';
    }
    
    $xml .= '</sitemapindex>';
    
    return $xml;
}

/**
 * Handle sitemap index request
 */
add_action('template_redirect', 'mct_handle_sitemap_index_request');
function mct_handle_sitemap_index_request() {
    if (isset($_GET['mct_sitemap_index'])) {
        header('Content-Type: application/xml; charset=utf-8');
        header('X-Robots-Tag: noindex, follow');
        echo mct_generate_sitemap_index();
        exit;
    }
}

/**
 * Add sitemap index link to robots.txt
 */
add_filter('robots_txt', 'mct_add_sitemap_to_robots', 10, 2);
function mct_add_sitemap_to_robots($output, $public) {
    if (!$public) {
        return $output;
    }
    
    $home_url = trailingslashit(home_url());
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    
    // Add individual language sitemaps
    foreach ($languages as $lang) {
        $output .= "\nSitemap: " . esc_url($home_url . 'sitemap-' . $lang . '.xml');
    }
    
    // Add sitemap index
    $output .= "\nSitemap: " . esc_url($home_url . '?mct_sitemap_index=1');
    
    return $output;
}
