<?php
/**
 * SEO Tags Handler
 * Manages hreflang, canonical tags, and multilingual metadata
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add hreflang and canonical tags to <head>
 */
add_action('wp_head', 'mct_add_hreflang_and_canonical', 1);
function mct_add_hreflang_and_canonical() {
    // Skip in admin
    if (is_admin()) {
        return;
    }
    
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    $mode = $options['translation_mode'] ?? 'php';
    $default_lang = $options['default_language'] ?? 'en';
    
    // Get current post/page
    global $post;
    
    if (!$post) {
        return;
    }
    
    $current_url = get_permalink($post->ID);
    $current_lang = mct_get_current_lang();
    
    // Output canonical tag (points to current language version)
    $canonical_url = mct_build_translated_url($current_url, $current_lang, $mode);
    echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
    
    // Output hreflang tags for all languages
    foreach ($languages as $lang) {
        $lang_url = mct_build_translated_url($current_url, $lang, $mode);
        echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($lang_url) . '" />' . "\n";
    }
    
    // Add x-default for default language
    $default_url = mct_build_translated_url($current_url, $default_lang, $mode);
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url($default_url) . '" />' . "\n";
}

/**
 * Build translated URL based on translation mode
 * 
 * @param string $url Original URL
 * @param string $lang Language code
 * @param string $mode Translation mode ('cloudflare' or 'php')
 * @return string Translated URL
 */
function mct_build_translated_url($url, $lang, $mode) {
    $parsed = parse_url($url);
    $host = $parsed['host'] ?? '';
    $scheme = $parsed['scheme'] ?? 'https';
    $path = $parsed['path'] ?? '/';
    $query = $parsed['query'] ?? '';
    
    if ($mode === 'cloudflare') {
        // Subdomain mode: fr.example.com
        $base_domain = mct_get_base_domain($host);
        $new_host = "{$lang}.{$base_domain}";
        $new_url = "{$scheme}://{$new_host}{$path}";
        
        if ($query) {
            $new_url .= '?' . $query;
        }
        
        return $new_url;
    } else {
        // Query parameter mode: example.com?lang=fr
        return add_query_arg('lang', $lang, $url);
    }
}

/**
 * Extract base domain from hostname
 * 
 * @param string $host Hostname
 * @return string Base domain
 */
function mct_get_base_domain($host) {
    $parts = explode('.', $host);
    
    // Remove subdomain if present
    if (count($parts) > 2) {
        return implode('.', array_slice($parts, -2));
    }
    
    return $host;
}

/**
 * Translate page title
 */
add_filter('document_title_parts', 'mct_translate_title_parts');
function mct_translate_title_parts($title) {
    // Skip in admin
    if (is_admin()) {
        return $title;
    }
    
    $target_lang = mct_get_current_lang();
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    
    // Don't translate if same language
    if ($target_lang === $default_lang) {
        return $title;
    }
    
    // Translate title components
    if (isset($title['title'])) {
        $title['title'] = mct_translate_text($title['title'], $target_lang, $default_lang);
    }
    
    if (isset($title['tagline'])) {
        $title['tagline'] = mct_translate_text($title['tagline'], $target_lang, $default_lang);
    }
    
    return $title;
}

/**
 * Translate meta description
 */
add_action('wp_head', 'mct_translate_meta_description', 5);
function mct_translate_meta_description() {
    // Skip in admin
    if (is_admin()) {
        return;
    }
    
    global $post;
    
    if (!$post) {
        return;
    }
    
    $target_lang = mct_get_current_lang();
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    
    // Don't translate if same language
    if ($target_lang === $default_lang) {
        return;
    }
    
    // Get excerpt or generate description
    $description = '';
    
    if (has_excerpt($post)) {
        $description = get_the_excerpt($post);
    } else {
        $description = wp_trim_words(strip_shortcodes($post->post_content), 20, '...');
    }
    
    if ($description) {
        $translated_description = mct_translate_text($description, $target_lang, $default_lang);
        echo '<meta name="description" content="' . esc_attr($translated_description) . '" />' . "\n";
    }
}

/**
 * Translate Open Graph tags
 */
add_action('wp_head', 'mct_translate_og_tags', 10);
function mct_translate_og_tags() {
    // Skip in admin
    if (is_admin()) {
        return;
    }
    
    global $post;
    
    if (!$post) {
        return;
    }
    
    $target_lang = mct_get_current_lang();
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    $mode = $options['translation_mode'] ?? 'php';
    
    // Don't translate if same language
    if ($target_lang === $default_lang) {
        return;
    }
    
    // Get post title and description
    $title = get_the_title($post);
    $description = '';
    
    if (has_excerpt($post)) {
        $description = get_the_excerpt($post);
    } else {
        $description = wp_trim_words(strip_shortcodes($post->post_content), 20, '...');
    }
    
    // Translate
    $translated_title = mct_translate_text($title, $target_lang, $default_lang);
    $translated_description = mct_translate_text($description, $target_lang, $default_lang);
    
    // Output OG tags
    $og_url = mct_build_translated_url(get_permalink($post), $target_lang, $mode);
    
    echo '<meta property="og:title" content="' . esc_attr($translated_title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($translated_description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($og_url) . '" />' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($target_lang) . '_' . esc_attr(strtoupper($target_lang)) . '" />' . "\n";
    
    // Add alternate locales
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    foreach ($languages as $lang) {
        if ($lang !== $target_lang) {
            echo '<meta property="og:locale:alternate" content="' . esc_attr($lang) . '_' . esc_attr(strtoupper($lang)) . '" />' . "\n";
        }
    }
    
    // Add og:image if available
    if (has_post_thumbnail($post)) {
        $thumbnail_url = get_the_post_thumbnail_url($post, 'large');
        echo '<meta property="og:image" content="' . esc_url($thumbnail_url) . '" />' . "\n";
    }
}

/**
 * Translate JSON-LD structured data
 */
add_action('wp_footer', 'mct_translate_json_ld', 99);
function mct_translate_json_ld() {
    // Skip in admin
    if (is_admin()) {
        return;
    }
    
    global $post;
    
    if (!$post || !is_singular()) {
        return;
    }
    
    $target_lang = mct_get_current_lang();
    $options = get_option('mct_settings');
    $default_lang = $options['default_language'] ?? 'en';
    $mode = $options['translation_mode'] ?? 'php';
    
    // Don't translate if same language
    if ($target_lang === $default_lang) {
        return;
    }
    
    // Build schema.org structured data
    $title = get_the_title($post);
    $description = '';
    
    if (has_excerpt($post)) {
        $description = get_the_excerpt($post);
    } else {
        $description = wp_trim_words(strip_shortcodes($post->post_content), 30, '...');
    }
    
    // Translate
    $translated_title = mct_translate_text($title, $target_lang, $default_lang);
    $translated_description = mct_translate_text($description, $target_lang, $default_lang);
    
    $url = mct_build_translated_url(get_permalink($post), $target_lang, $mode);
    
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $translated_title,
        'description' => $translated_description,
        'url' => $url,
        'datePublished' => get_the_date('c', $post),
        'dateModified' => get_the_modified_date('c', $post),
        'author' => array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author)
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url()
        ),
        'inLanguage' => $target_lang
    );
    
    // Add image if available
    if (has_post_thumbnail($post)) {
        $schema['image'] = get_the_post_thumbnail_url($post, 'large');
    }
    
    echo '<script type="application/ld+json">' . "\n";
    echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    echo "\n" . '</script>' . "\n";
}

/**
 * Add language switcher function
 * Can be used in themes
 */
function mct_language_switcher($args = array()) {
    $defaults = array(
        'show_flags' => false,
        'show_names' => true,
        'format' => 'list', // 'list' or 'dropdown'
        'class' => 'mct-language-switcher'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    $options = get_option('mct_settings');
    $languages = array_map('trim', explode(',', $options['active_languages'] ?? 'en'));
    $current_lang = mct_get_current_lang();
    $mode = $options['translation_mode'] ?? 'php';
    
    global $post;
    
    if (!$post) {
        return;
    }
    
    $current_url = get_permalink($post->ID);
    
    // Language names
    $lang_names = array(
        'en' => 'English',
        'fr' => 'Français',
        'es' => 'Español',
        'de' => 'Deutsch',
        'it' => 'Italiano',
        'pt' => 'Português',
        'ru' => 'Русский',
        'zh' => '中文',
        'ja' => '日本語',
        'ko' => '한국어'
    );
    
    if ($args['format'] === 'dropdown') {
        ?>
        <select class="<?php echo esc_attr($args['class']); ?>" onchange="window.location.href=this.value">
            <?php foreach ($languages as $lang): ?>
                <?php
                $lang_url = mct_build_translated_url($current_url, $lang, $mode);
                $selected = ($lang === $current_lang) ? 'selected' : '';
                $display_name = $lang_names[$lang] ?? strtoupper($lang);
                ?>
                <option value="<?php echo esc_url($lang_url); ?>" <?php echo $selected; ?>>
                    <?php echo esc_html($display_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php
    } else {
        ?>
        <ul class="<?php echo esc_attr($args['class']); ?>">
            <?php foreach ($languages as $lang): ?>
                <?php
                $lang_url = mct_build_translated_url($current_url, $lang, $mode);
                $active = ($lang === $current_lang) ? 'active' : '';
                $display_name = $lang_names[$lang] ?? strtoupper($lang);
                ?>
                <li class="<?php echo esc_attr($active); ?>">
                    <a href="<?php echo esc_url($lang_url); ?>" hreflang="<?php echo esc_attr($lang); ?>">
                        <?php if ($args['show_names']): ?>
                            <?php echo esc_html($display_name); ?>
                        <?php else: ?>
                            <?php echo esc_html(strtoupper($lang)); ?>
                        <?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
}

/**
 * Shortcode for language switcher
 */
add_shortcode('mct_language_switcher', 'mct_language_switcher_shortcode');
function mct_language_switcher_shortcode($atts) {
    $atts = shortcode_atts(array(
        'format' => 'list',
        'show_names' => 'true'
    ), $atts);
    
    ob_start();
    mct_language_switcher(array(
        'format' => $atts['format'],
        'show_names' => ($atts['show_names'] === 'true')
    ));
    return ob_get_clean();
}
