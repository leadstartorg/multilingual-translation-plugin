<?php
/**
 * Translation API Integration
 * Handles Google Cloud Translation API v3 calls and translation logic
 * 
 * @package MultilangCloudTranslate
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use Google\Cloud\Translate\V3\TranslationServiceClient;
use Google\Cloud\Translate\V3\TranslateTextRequest;

/**
 * Translate text using Google Cloud Translation API v3
 * 
 * @param string $text Text to translate
 * @param string $targetLang Target language code (e.g., 'fr', 'es')
 * @param string $sourceLang Source language code (default: 'en')
 * @return string Translated text or original text if translation fails
 */
function mct_translate_text($text, $targetLang, $sourceLang = 'en') {
    // Load plugin settings
    $options = get_option('mct_settings');
    $projectId = $options['google_project_id'] ?? '';
    $apiKey = $options['google_api_key'] ?? '';
    
    // Validate settings
    if (empty($projectId) || empty($apiKey)) {
        error_log('Multilang Cloud Translate: Missing Google project ID or API key.');
        return $text;
    }
    
    // Don't translate if same language
    if ($targetLang === $sourceLang) {
        return $text;
    }
    
    // Don't translate empty text
    if (empty(trim($text))) {
        return $text;
    }
    
    // Initialize Google Cloud Translation client
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        error_log('Multilang Cloud Translate: Google credentials file not found at ' . $credentialsPath);
        return $text;
    }
    
    try {
        // Create Translation Service Client
        $translationClient = new TranslationServiceClient([
            'credentials' => json_decode(file_get_contents($credentialsPath), true)
        ]);
        
        // Format the parent resource name
        $parent = $translationClient->locationName($projectId, 'global');
        
        // Prepare translation request
        $response = $translationClient->translateText([
            'parent' => $parent,
            'contents' => [$text],
            'mimeType' => 'text/html', // Support HTML formatting
            'sourceLanguageCode' => $sourceLang,
            'targetLanguageCode' => $targetLang,
        ]);
        
        // Extract translated text
        $translations = $response->getTranslations();
        
        if (!empty($translations)) {
            $translatedText = $translations[0]->getTranslatedText();
            return $translatedText;
        }
        
        return $text;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Translation API error: ' . $e->getMessage());
        return $text;
    }
}

/**
 * Translate text with glossary support
 * 
 * @param string $text Text to translate
 * @param string $targetLang Target language code
 * @param string $sourceLang Source language code
 * @param string $glossaryId Optional glossary ID
 * @return string Translated text
 */
function mct_translate_text_with_glossary($text, $targetLang, $sourceLang = 'en', $glossaryId = null) {
    $options = get_option('mct_settings');
    $projectId = $options['google_project_id'] ?? '';
    
    if (empty($projectId)) {
        return $text;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return $text;
    }
    
    try {
        $translationClient = new TranslationServiceClient([
            'credentials' => json_decode(file_get_contents($credentialsPath), true)
        ]);
        
        $parent = $translationClient->locationName($projectId, 'global');
        
        $requestParams = [
            'parent' => $parent,
            'contents' => [$text],
            'mimeType' => 'text/html',
            'sourceLanguageCode' => $sourceLang,
            'targetLanguageCode' => $targetLang,
        ];
        
        // Add glossary if provided
        if ($glossaryId) {
            $glossaryPath = $translationClient->glossaryName($projectId, 'global', $glossaryId);
            $requestParams['glossaryConfig'] = [
                'glossary' => $glossaryPath
            ];
        }
        
        $response = $translationClient->translateText($requestParams);
        $translations = $response->getTranslations();
        
        if (!empty($translations)) {
            return $translations[0]->getTranslatedText();
        }
        
        return $text;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Glossary translation error: ' . $e->getMessage());
        return $text;
    }
}

/**
 * Batch translate multiple texts
 * Useful for pre-generating translations
 * 
 * @param array $texts Array of texts to translate
 * @param string $targetLang Target language code
 * @param string $sourceLang Source language code
 * @return array Array of translated texts
 */
function mct_batch_translate_texts($texts, $targetLang, $sourceLang = 'en') {
    if (empty($texts) || !is_array($texts)) {
        return array();
    }
    
    $options = get_option('mct_settings');
    $projectId = $options['google_project_id'] ?? '';
    
    if (empty($projectId)) {
        return $texts;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return $texts;
    }
    
    try {
        $translationClient = new TranslationServiceClient([
            'credentials' => json_decode(file_get_contents($credentialsPath), true)
        ]);
        
        $parent = $translationClient->locationName($projectId, 'global');
        
        // Google API supports up to 1024 texts per request
        $chunks = array_chunk($texts, 100);
        $allTranslations = array();
        
        foreach ($chunks as $chunk) {
            $response = $translationClient->translateText([
                'parent' => $parent,
                'contents' => $chunk,
                'mimeType' => 'text/html',
                'sourceLanguageCode' => $sourceLang,
                'targetLanguageCode' => $targetLang,
            ]);
            
            $translations = $response->getTranslations();
            
            foreach ($translations as $translation) {
                $allTranslations[] = $translation->getTranslatedText();
            }
        }
        
        return $allTranslations;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Batch translation error: ' . $e->getMessage());
        return $texts;
    }
}

/**
 * Detect language of text using Google Cloud Translation API
 * 
 * @param string $text Text to analyze
 * @return string|false Detected language code or false on failure
 */
function mct_detect_language($text) {
    $options = get_option('mct_settings');
    $projectId = $options['google_project_id'] ?? '';
    
    if (empty($projectId)) {
        return false;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return false;
    }
    
    try {
        $translationClient = new TranslationServiceClient([
            'credentials' => json_decode(file_get_contents($credentialsPath), true)
        ]);
        
        $parent = $translationClient->locationName($projectId, 'global');
        
        $response = $translationClient->detectLanguage([
            'parent' => $parent,
            'content' => $text,
        ]);
        
        $languages = $response->getLanguages();
        
        if (!empty($languages)) {
            // Return the language with highest confidence
            return $languages[0]->getLanguageCode();
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Language detection error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get supported languages from Google Cloud Translation API
 * 
 * @return array Array of language codes and names
 */
function mct_get_supported_languages() {
    $options = get_option('mct_settings');
    $projectId = $options['google_project_id'] ?? '';
    
    if (empty($projectId)) {
        return array();
    }
    
    // Check cache first (valid for 24 hours)
    $cached = get_transient('mct_supported_languages');
    if ($cached !== false) {
        return $cached;
    }
    
    $credentialsPath = WP_CONTENT_DIR . '/google-credentials.json';
    
    if (!file_exists($credentialsPath)) {
        return array();
    }
    
    try {
        $translationClient = new TranslationServiceClient([
            'credentials' => json_decode(file_get_contents($credentialsPath), true)
        ]);
        
        $parent = $translationClient->locationName($projectId, 'global');
        
        $response = $translationClient->getSupportedLanguages([
            'parent' => $parent,
            'displayLanguageCode' => 'en'
        ]);
        
        $languages = array();
        
        foreach ($response->getLanguages() as $language) {
            $languages[$language->getLanguageCode()] = $language->getDisplayName();
        }
        
        // Cache for 24 hours
        set_transient('mct_supported_languages', $languages, DAY_IN_SECONDS);
        
        return $languages;
        
    } catch (Exception $e) {
        error_log('Multilang Cloud Translate - Get supported languages error: ' . $e->getMessage());
        return array();
    }
}
