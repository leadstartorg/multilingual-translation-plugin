# Developer Guide - Multilang Cloud Translate

Advanced customization, hooks, filters, and API reference for WordPress developers.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Core Functions](#core-functions)
3. [Hooks & Filters](#hooks--filters)
4. [REST API](#rest-api)
5. [WP-CLI Commands](#wp-cli-commands)
6. [Custom Implementations](#custom-implementations)
7. [Extending the Plugin](#extending-the-plugin)

---

## Architecture Overview

### Components

```
┌─────────────────────────────────────────┐
│         Cloudflare Worker (Optional)    │
│    - Edge caching                       │
│    - Subdomain routing                  │
│    - IP detection                       │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│         WordPress Plugin                │
│    ┌─────────────────────────────────┐  │
│    │  Translation API (Google Cloud) │  │
│    └─────────────────────────────────┘  │
│    ┌─────────────────────────────────┐  │
│    │  Cache Handler (GCS)            │  │
│    └─────────────────────────────────┘  │
│    ┌─────────────────────────────────┐  │
│    │  SEO Tags & Metadata            │  │
│    └─────────────────────────────────┘  │
│    ┌─────────────────────────────────┐  │
│    │  Logging & Analytics            │  │
│    └─────────────────────────────────┘  │
└─────────────────────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│     Google Cloud Services               │
│  - Translation API v3                   │
│  - Cloud Storage (GCS)                  │
└─────────────────────────────────────────┘
```

---

## Core Functions

### Translation Functions

#### `mct_translate($text, $targetLang, $sourceLang = 'en')`

Translate text with automatic caching.

```php
$translated = mct_translate('Hello World', 'fr', 'en');
// Returns: "Bonjour le monde"
```

#### `mct_translate_array($texts, $targetLang, $sourceLang = 'en')`

Translate multiple strings.

```php
$texts = ['Hello', 'World', 'Welcome'];
$translated = mct_translate_array($texts, 'fr');
// Returns: ['Bonjour', 'Monde', 'Bienvenue']
```

#### `mct_translate_post_title($post, $targetLang)`

Translate post title.

```php
$translated_title = mct_translate_post_title(get_post(123), 'fr');
```

#### `mct_translate_post_excerpt($post, $targetLang)`

Translate post excerpt.

```php
$translated_excerpt = mct_translate_post_excerpt(get_post(123), 'fr');
```

### Language Functions

#### `mct_get_current_lang()`

Get currently active language.

```php
$lang = mct_get_current_lang();
// Returns: 'fr' or 'en' or other active language
```

#### `mct_get_available_languages()`

Get all active languages.

```php
$languages = mct_get_available_languages();
// Returns: ['en', 'fr', 'es', 'de']
```

#### `mct_get_language_name($code)`

Get language name from code.

```php
$name = mct_get_language_name('fr');
// Returns: "Français"
```

#### `mct_is_language_active($lang)`

Check if language is active.

```php
if (mct_is_language_active('fr')) {
    // French is active
}
```

### URL Functions

#### `mct_build_translated_url($url, $lang, $mode)`

Build translated URL for a given language.

```php
$url = mct_build_translated_url('https://example.com/about/', 'fr', 'php');
// Returns: "https://example.com/about/?lang=fr"

$url = mct_build_translated_url('https://example.com/about/', 'fr', 'cloudflare');
// Returns: "https://fr.example.com/about/"
```

#### `mct_get_alternate_links($url)`

Get all language versions of a URL.

```php
$links = mct_get_alternate_links('https://example.com/about/');
// Returns:
// [
//     'en' => 'https://example.com/about/',
//     'fr' => 'https://fr.example.com/about/',
//     'es' => 'https://es.example.com/about/'
// ]
```

### Cache Functions

#### `mct_get_translation_from_gcs($cacheKey, $targetLang)`

Retrieve translation from cache.

```php
$cached = mct_get_translation_from_gcs('abc123', 'fr');
```

#### `mct_save_translation_to_gcs($cacheKey, $targetLang, $content)`

Save translation to cache.

```php
mct_save_translation_to_gcs('abc123', 'fr', 'Bonjour');
```

#### `mct_clear_all_cache()`

Clear all cached translations.

```php
$deleted_count = mct_clear_all_cache();
```

#### `mct_clear_language_cache($targetLang)`

Clear cache for specific language.

```php
$deleted_count = mct_clear_language_cache('fr');
```

---

## Hooks & Filters

### Actions

#### `mct_before_translation`

Fires before text is translated.

```php
add_action('mct_before_translation', function($text, $targetLang, $sourceLang) {
    error_log("Translating to {$targetLang}: " . substr($text, 0, 50));
}, 10, 3);
```

#### `mct_after_translation`

Fires after text is translated.

```php
add_action('mct_after_translation', function($original, $translated, $targetLang) {
    // Log successful translation
    error_log("Translation complete: {$targetLang}");
}, 10, 3);
```

#### `mct_cache_cleared`

Fires when translation cache is cleared.

```php
add_action('mct_cache_cleared', function($count) {
    error_log("Cleared {$count} translations from cache");
});
```

### Filters

#### `mct_translate_text`

Filter translated text before returning.

```php
add_filter('mct_translate_text', function($translated, $original, $targetLang) {
    // Custom post-processing
    $translated = str_replace('{{SITE_NAME}}', get_bloginfo('name'), $translated);
    return $translated;
}, 10, 3);
```

#### `mct_active_languages`

Filter active languages list.

```php
add_filter('mct_active_languages', function($languages) {
    // Add custom language dynamically
    $languages[] = 'pt';
    return $languages;
});
```

#### `mct_translation_mode`

Filter translation mode.

```php
add_filter('mct_translation_mode', function($mode) {
    // Force PHP mode for certain conditions
    if (is_user_logged_in()) {
        return 'php';
    }
    return $mode;
});
```

#### `mct_should_translate_content`

Control whether content should be translated.

```php
add_filter('mct_should_translate_content', function($should_translate, $post) {
    // Don't translate specific post types
    if ($post->post_type === 'product') {
        return false;
    }
    return $should_translate;
}, 10, 2);
```

#### `mct_google_translate_params`

Modify Google Translation API parameters.

```php
add_filter('mct_google_translate_params', function($params) {
    // Add custom glossary
    $params['glossaryConfig'] = [
        'glossary' => 'projects/MY_PROJECT/locations/global/glossaries/MY_GLOSSARY'
    ];
    return $params;
});
```

---

## REST API

### Endpoints

#### Get Sitemap for Language

**Endpoint**: `/wp-json/mct/v1/sitemap/{lang}`

**Method**: GET

**Example**:
```bash
curl https://example.com/wp-json/mct/v1/sitemap/fr
```

**Response**:
```json
[
    {
        "loc": "https://fr.example.com/about/",
        "lastmod": "2025-01-10T12:00:00+00:00",
        "changefreq": "weekly",
        "priority": 0.8
    }
]
```

#### Translate Text (AJAX)

**Endpoint**: `/wp-admin/admin-ajax.php`

**Action**: `mct_preview_translation`

**Method**: POST

**Parameters**:
- `text`: Text to translate
- `target_lang`: Target language code
- `nonce`: Security nonce

**Example**:
```javascript
jQuery.ajax({
    url: ajaxurl,
    method: 'POST',
    data: {
        action: 'mct_preview_translation',
        text: 'Hello World',
        target_lang: 'fr',
        nonce: mct_nonce
    },
    success: function(response) {
        console.log(response.data.translated);
    }
});
```

---

## WP-CLI Commands

### Translate All Content

```bash
# Translate all posts/pages to French
wp mct translate-all fr

# Translate to multiple languages
for lang in fr es de; do
    wp mct translate-all $lang
done
```

### Clear Cache

```bash
# Clear all translation cache
wp mct clear-cache

# Clear specific language
wp mct clear-cache --lang=fr
```

### Get Statistics

```bash
# Get translation statistics
wp mct stats

# Get statistics for specific language
wp mct stats --lang=fr
```

### Export Logs

```bash
# Export translation logs to CSV
wp mct export-logs --file=translations.csv --days=30
```

---

## Custom Implementations

### Custom Language Switcher

```php
function my_custom_language_switcher() {
    $languages = mct_get_available_languages();
    $current_lang = mct_get_current_lang();
    $mode = get_option('mct_settings')['translation_mode'] ?? 'php';
    
    echo '<div class="my-lang-switcher">';
    
    foreach ($languages as $lang) {
        $url = mct_build_translated_url(get_permalink(), $lang, $mode);
        $name = mct_get_language_name($lang);
        $active = ($lang === $current_lang) ? 'active' : '';
        
        echo sprintf(
            '<a href="%s" class="lang-link %s" hreflang="%s">%s</a>',
            esc_url($url),
            $active,
            esc_attr($lang),
            esc_html($name)
        );
    }
    
    echo '</div>';
}
```

### Translate Custom Field

```php
function translate_custom_field($post_id, $field_name, $target_lang) {
    $value = get_post_meta($post_id, $field_name, true);
    
    if (empty($value)) {
        return '';
    }
    
    $source_lang = mct_get_default_language();
    $translated = mct_translate($value, $target_lang, $source_lang);
    
    // Optionally cache translated meta
    $cache_key = "meta_{$post_id}_{$field_name}_{$target_lang}";
    set_transient($cache_key, $translated, DAY_IN_SECONDS);
    
    return $translated;
}

// Usage
$translated_subtitle = translate_custom_field(123, 'subtitle', 'fr');
```

### Pre-Generate Translations

```php
function pre_generate_translations() {
    $languages = ['fr', 'es', 'de'];
    
    $posts = get_posts([
        'post_type' => ['post', 'page'],
        'post_status' => 'publish',
        'posts_per_page' => -1
    ]);
    
    foreach ($posts as $post) {
        foreach ($languages as $lang) {
            // Translate title
            mct_translate($post->post_title, $lang);
            
            // Translate content
            mct_translate($post->post_content, $lang);
            
            // Small delay to avoid rate limits
            usleep(100000); // 0.1 seconds
        }
    }
}

// Run via WP-Cron
add_action('my_daily_translation_job', 'pre_generate_translations');
```

### Custom Translation Memory

```php
class Custom_Translation_Memory {
    private $cache_group = 'mct_custom_memory';
    
    public function get($text, $target_lang) {
        $key = md5($text . $target_lang);
        return wp_cache_get($key, $this->cache_group);
    }
    
    public function set($text, $target_lang, $translation) {
        $key = md5($text . $target_lang);
        wp_cache_set($key, $translation, $this->cache_group, WEEK_IN_SECONDS);
    }
    
    public function translate_with_memory($text, $target_lang) {
        // Check memory first
        $cached = $this->get($text, $target_lang);
        
        if ($cached) {
            return $cached;
        }
        
        // Translate via API
        $translated = mct_translate($text, $target_lang);
        
        // Store in memory
        $this->set($text, $target_lang, $translated);
        
        return $translated;
    }
}

$memory = new Custom_Translation_Memory();
$translated = $memory->translate_with_memory('Hello', 'fr');
```

---

## Extending the Plugin

### Add Custom Translation Provider

```php
add_filter('mct_translation_provider', function($provider, $text, $target_lang) {
    // Use alternative provider (e.g., DeepL)
    if (defined('USE_DEEPL') && USE_DEEPL) {
        return my_deepl_translate($text, $target_lang);
    }
    
    return $provider;
}, 10, 3);

function my_deepl_translate($text, $target_lang) {
    $api_key = 'YOUR_DEEPL_KEY';
    
    $response = wp_remote_post('https://api-free.deepl.com/v2/translate', [
        'body' => [
            'auth_key' => $api_key,
            'text' => $text,
            'target_lang' => strtoupper($target_lang)
        ]
    ]);
    
    $data = json_decode(wp_remote_retrieve_body($response), true);
    
    return $data['translations'][0]['text'] ?? $text;
}
```

### Add Custom Cache Layer

```php
class Custom_Cache_Layer {
    public function __construct() {
        add_filter('mct_get_cache', [$this, 'get_cache'], 10, 2);
        add_action('mct_set_cache', [$this, 'set_cache'], 10, 3);
    }
    
    public function get_cache($value, $key) {
        // Check Redis first
        if (class_exists('Redis')) {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $cached = $redis->get("mct:$key");
            
            if ($cached) {
                return $cached;
            }
        }
        
        return $value;
    }
    
    public function set_cache($key, $value, $ttl) {
        // Store in Redis
        if (class_exists('Redis')) {
            $redis = new Redis();
            $redis->connect('127.0.0.1', 6379);
            $redis->setex("mct:$key", $ttl, $value);
        }
    }
}

new Custom_Cache_Layer();
```

### Add Analytics Events

```php
add_action('mct_after_translation', function($original, $translated, $target_lang) {
    // Send to custom analytics
    if (function_exists('my_track_event')) {
        my_track_event('translation', [
            'language' => $target_lang,
            'char_count' => strlen($original),
            'page' => get_permalink()
        ]);
    }
}, 10, 3);
```

---

## Performance Optimization

### Object Caching

```php
// Use WordPress object cache for frequently accessed data
function mct_get_cached_translation($key, $lang) {
    $cache_key = "translation_{$key}_{$lang}";
    $cached = wp_cache_get($cache_key, 'mct_translations');
    
    if ($cached !== false) {
        return $cached;
    }
    
    $translation = mct_get_translation_from_gcs($key, $lang);
    
    if ($translation) {
        wp_cache_set($cache_key, $translation, 'mct_translations', HOUR_IN_SECONDS);
    }
    
    return $translation;
}
```

### Batch Processing

```php
function batch_translate_posts($post_ids, $target_lang, $batch_size = 10) {
    $batches = array_chunk($post_ids, $batch_size);
    
    foreach ($batches as $batch) {
        $texts = [];
        
        foreach ($batch as $post_id) {
            $post = get_post($post_id);
            $texts[] = $post->post_content;
        }
        
        // Batch translate
        $translated = mct_batch_translate_texts($texts, $target_lang);
        
        // Store results
        foreach ($batch as $index => $post_id) {
            $cache_key = md5(get_permalink($post_id) . '_' . $target_lang);
            mct_save_translation_to_gcs($cache_key, $target_lang, $translated[$index]);
        }
    }
}
```

---

## Testing

### Unit Test Example

```php
class Test_MCT_Translation extends WP_UnitTestCase {
    public function test_translate_simple_text() {
        $translated = mct_translate('Hello', 'fr');
        $this->assertEquals('Bonjour', $translated);
    }
    
    public function test_get_current_lang() {
        $_GET['lang'] = 'fr';
        $lang = mct_get_current_lang();
        $this->assertEquals('fr', $lang);
    }
    
    public function test_build_translated_url() {
        $url = mct_build_translated_url('https://example.com/about/', 'fr', 'php');
        $this->assertStringContainsString('lang=fr', $url);
    }
}
```

---

## Security Considerations

### Validate Language Codes

```php
function validate_language_code($lang) {
    $allowed_languages = mct_get_available_languages();
    
    if (!in_array($lang, $allowed_languages)) {
        return mct_get_default_language();
    }
    
    return $lang;
}
```

### Sanitize Translations

```php
add_filter('mct_translate_text', function($translated) {
    // Sanitize HTML
    return wp_kses_post($translated);
});
```

---

## License

GPL v2 or later

## Support

- GitHub: https://github.com/username/multilang-cloud-translate
- Documentation: https://example.com/docs
- Issues: https://github.com/username/multilang-cloud-translate/issues
