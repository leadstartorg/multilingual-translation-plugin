<?php
/**
 * Cache Handler
 * Manages translation caching with Google Cloud Storage
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use Google\Cloud\Storage\StorageClient;

/**
 * Get translation from Google Cloud Storage cache
 * 
 * @param string $cacheKey Unique cache key
 * @param string $targetLang Target language code
 * @return string|false Cached translation or false if not found
 */
function mct_get_translation_from_gcs($cacheKey, $targetLang) {
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    
    if (empty($bucketName)) {
        return false;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        error_log('Multilang Cloud Translate: Google credentials file not found.');
        return false;
    }
    
    try {
        $storage = new StorageClient([
            'keyFilePath' => $credentialsPath
        ]);
        
        $bucket = $storage->bucket($bucketName);
        $objectPath = "translations/{$targetLang}/{$cacheKey}.html";
        $object = $bucket->object($objectPath);
        
        if ($object->exists()) {
            $content = $object->downloadAsString();
            return $content;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - GCS get cache error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Save translation to Google Cloud Storage cache
 * 
 * @param string $cacheKey Unique cache key
 * @param string $targetLang Target language code
 * @param string $content Translated content to cache
 * @return bool True on success, false on failure
 */
function mct_save_translation_to_gcs($cacheKey, $targetLang, $content) {
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    $cacheTTL = $options['cache_ttl'] ?? 3600;
    
    if (empty($bucketName)) {
        return false;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        error_log('Multilang Cloud Translate: Google credentials file not found.');
        return false;
    }
    
    try {
        $storage = new StorageClient([
            'keyFilePath' => $credentialsPath
        ]);
        
        $bucket = $storage->bucket($bucketName);
        $objectPath = "translations/{$targetLang}/{$cacheKey}.html";
        
        $bucket->upload($content, [
            'name' => $objectPath,
            'metadata' => [
                'cacheControl' => 'public, max-age=' . $cacheTTL,
                'contentType' => 'text/html; charset=utf-8',
                'lang' => $targetLang,
                'cached_at' => current_time('mysql')
            ]
        ]);
        
        return true;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - GCS save cache error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete specific translation from cache
 * 
 * @param string $cacheKey Cache key to delete
 * @param string $targetLang Target language code
 * @return bool True on success, false on failure
 */
function mct_delete_translation_from_gcs($cacheKey, $targetLang) {
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    
    if (empty($bucketName)) {
        return false;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return false;
    }
    
    try {
        $storage = new StorageClient([
            'keyFilePath' => $credentialsPath
        ]);
        
        $bucket = $storage->bucket($bucketName);
        $objectPath = "translations/{$targetLang}/{$cacheKey}.html";
        $object = $bucket->object($objectPath);
        
        if ($object->exists()) {
            $object->delete();
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - GCS delete cache error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Clear all cached translations for a specific language
 * 
 * @param string $targetLang Language code to clear
 * @return int Number of files deleted
 */
function mct_clear_language_cache($targetLang) {
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    
    if (empty($bucketName)) {
        return 0;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return 0;
    }
    
    try {
        $storage = new StorageClient([
            'keyFilePath' => $credentialsPath
        ]);
        
        $bucket = $storage->bucket($bucketName);
        $objects = $bucket->objects(['prefix' => "translations/{$targetLang}/"]);
        
        $count = 0;
        foreach ($objects as $object) {
            $object->delete();
            $count++;
        }
        
        return $count;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Clear language cache error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Clear all translation caches
 * 
 * @return int Number of files deleted
 */
function mct_clear_all_cache() {
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    
    if (empty($bucketName)) {
        return 0;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return 0;
    }
    
    try {
        $storage = new StorageClient([
            'keyFilePath' => $credentialsPath
        ]);
        
        $bucket = $storage->bucket($bucketName);
        $objects = $bucket->objects(['prefix' => 'translations/']);
        
        $count = 0;
        foreach ($objects as $object) {
            $object->delete();
            $count++;
        }
        
        return $count;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Clear all cache error: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Hook into post save to clear related cache
 * Automatically purges cache when post is updated
 */
add_action('save_post', 'mct_clear_post_cache', 10, 3);
function mct_clear_post_cache($post_id, $post, $update) {
    // Skip autosave and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // Get post URL
    $url = get_permalink($post_id);
    
    // Get all active languages
    $options = get_option('mct_settings');
    $languages = explode(',', $options['active_languages'] ?? 'en');
    
    // Clear cache for this URL in all languages
    foreach ($languages as $lang) {
        $lang = trim($lang);
        
        // Generate cache key (same logic as in main plugin file)
        $cache_key = md5($url . '_' . $lang);
        
        // Delete from GCS
        mct_delete_translation_from_gcs($cache_key, $lang);
    }
}

/**
 * Get cache statistics
 * 
 * @return array Cache statistics
 */
function mct_get_cache_stats() {
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    
    if (empty($bucketName)) {
        return array(
            'total_files' => 0,
            'total_size' => 0,
            'languages' => array()
        );
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return array(
            'total_files' => 0,
            'total_size' => 0,
            'languages' => array()
        );
    }
    
    try {
        $storage = new StorageClient([
            'keyFilePath' => $credentialsPath
        ]);
        
        $bucket = $storage->bucket($bucketName);
        $objects = $bucket->objects(['prefix' => 'translations/']);
        
        $stats = array(
            'total_files' => 0,
            'total_size' => 0,
            'languages' => array()
        );
        
        foreach ($objects as $object) {
            $stats['total_files']++;
            $stats['total_size'] += $object->info()['size'] ?? 0;
            
            // Extract language from path
            $path = $object->name();
            $parts = explode('/', $path);
            
            if (count($parts) >= 2) {
                $lang = $parts[1];
                
                if (!isset($stats['languages'][$lang])) {
                    $stats['languages'][$lang] = array('count' => 0, 'size' => 0);
                }
                
                $stats['languages'][$lang]['count']++;
                $stats['languages'][$lang]['size'] += $object->info()['size'] ?? 0;
            }
        }
        
        return $stats;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Get cache stats error: ' . $e->getMessage());
        
        return array(
            'total_files' => 0,
            'total_size' => 0,
            'languages' => array()
        );
    }
}

/**
 * Check if GCS bucket is accessible and properly configured
 * 
 * @return array Status array with 'success' and 'message' keys
 */
function mct_test_gcs_connection() {
    $options = get_option('mct_settings');
    $bucketName = $options['gcs_bucket'] ?? '';
    
    if (empty($bucketName)) {
        return array(
            'success' => false,
            'message' => 'GCS bucket name not configured.'
        );
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return array(
            'success' => false,
            'message' => 'Google credentials file not found.'
        );
    }
    
    try {
        $storage = new StorageClient([
            'keyFilePath' => $credentialsPath
        ]);
        
        $bucket = $storage->bucket($bucketName);
        
        // Test write permission
        $testFile = 'test_' . time() . '.txt';
        $bucket->upload('Test content', [
            'name' => 'translations/' . $testFile
        ]);
        
        // Test read permission
        $object = $bucket->object('translations/' . $testFile);
        $content = $object->downloadAsString();
        
        // Clean up test file
        $object->delete();
        
        return array(
            'success' => true,
            'message' => 'GCS connection successful. Bucket is accessible.'
        );
        
    } catch (Exception $e) {
        return array(
            'success' => false,
            'message' => 'GCS connection failed: ' . $e->getMessage()
        );
    }
}
