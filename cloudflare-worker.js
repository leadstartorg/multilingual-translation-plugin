/**
 * Cloudflare Worker for Multilang Cloud Translate
 * Handles edge-level caching, subdomain routing, and IP-based language detection
 * 
 * @version 1.0.0
 * @author Jessica Kafor
 */

// Configuration
const WP_BASE_URL = 'https://www.example.com'; // Your WordPress site URL (without subdomain)
const DEFAULT_LANG = 'en';
const CACHE_TTL = 3600; // Cache duration in seconds (1 hour)
const IPINFO_TOKEN = ''; // Your IPinfo.io token (set in Workers environment variables)

// Language subdomain mapping
const LANG_SUBDOMAINS = {
    'en': 'en',
    'fr': 'fr',
    'es': 'es',
    'de': 'de',
    'it': 'it',
    'pt': 'pt',
    'ru': 'ru',
    'zh': 'zh',
    'ja': 'ja',
    'ko': 'ko'
};

// Paths to exclude from translation/caching
const EXCLUDE_PATHS = [
    '/wp-admin',
    '/wp-login.php',
    '/wp-json',
    '/wp-cron.php',
    '/xmlrpc.php',
    '/.well-known'
];

/**
 * Main request handler
 */
addEventListener('fetch', event => {
    event.respondWith(handleRequest(event));
});

/**
 * Handle incoming request
 */
async function handleRequest(event) {
    const request = event.request;
    const url = new URL(request.url);
    
    // Check if path should be excluded
    if (shouldExcludePath(url.pathname)) {
        return fetch(request);
    }
    
    // Determine target language
    let targetLang = url.searchParams.get('lang');
    
    if (!targetLang) {
        targetLang = getLangFromSubdomain(url.hostname);
    }
    
    // If no language detected, try IP-based detection
    if (!targetLang) {
        targetLang = await detectLangFromIP(request);
        
        // Redirect to subdomain with detected language
        if (targetLang && targetLang !== DEFAULT_LANG) {
            const baseDomain = getBaseDomain(url.hostname);
            const redirectUrl = `${url.protocol}//${targetLang}.${baseDomain}${url.pathname}${url.search}`;
            return Response.redirect(redirectUrl, 302);
        }
        
        targetLang = DEFAULT_LANG;
    }
    
    // Check edge cache
    const cache = caches.default;
    let cachedResponse = await cache.match(request);
    
    if (cachedResponse) {
        // Add cache hit header
        cachedResponse = new Response(cachedResponse.body, cachedResponse);
        cachedResponse.headers.set('X-Cache', 'HIT');
        return cachedResponse;
    }
    
    // Fetch from WordPress origin
    const wpUrl = `${WP_BASE_URL}${url.pathname}${url.search}`;
    
    const wpRequest = new Request(wpUrl, {
        method: request.method,
        headers: new Headers({
            'X-MCT-Target-Lang': targetLang,
            'X-Forwarded-For': request.headers.get('cf-connecting-ip') || '',
            'User-Agent': request.headers.get('user-agent') || '',
            'Accept': request.headers.get('accept') || '*/*',
            'Accept-Language': request.headers.get('accept-language') || ''
        })
    });
    
    let response = await fetch(wpRequest);
    
    // Only process HTML responses
    const contentType = response.headers.get('content-type') || '';
    
    if (contentType.includes('text/html')) {
        // Clone response to modify
        let html = await response.text();
        
        // Rewrite internal links to use correct subdomain
        html = rewriteInternalLinks(html, targetLang, url.hostname);
        
        // Create new response with modified HTML
        response = new Response(html, {
            status: response.status,
            statusText: response.statusText,
            headers: response.headers
        });
        
        // Add caching headers
        response.headers.set('Cache-Control', `public, max-age=${CACHE_TTL}`);
        response.headers.set('X-Cache', 'MISS');
        response.headers.set('X-MCT-Language', targetLang);
        
        // Cache the response
        event.waitUntil(cache.put(request, response.clone()));
    }
    
    return response;
}

/**
 * Check if path should be excluded from processing
 */
function shouldExcludePath(pathname) {
    return EXCLUDE_PATHS.some(excluded => pathname.startsWith(excluded));
}

/**
 * Extract language from subdomain
 */
function getLangFromSubdomain(hostname) {
    const parts = hostname.split('.');
    
    // Need at least subdomain.domain.tld
    if (parts.length < 3) {
        return null;
    }
    
    const subdomain = parts[0].toLowerCase();
    
    return LANG_SUBDOMAINS[subdomain] || null;
}

/**
 * Get base domain (example.com from fr.example.com)
 */
function getBaseDomain(hostname) {
    const parts = hostname.split('.');
    
    // Return last two parts (domain.tld)
    return parts.slice(-2).join('.');
}

/**
 * Detect language from IP address using IPinfo.io
 */
async function detectLangFromIP(request) {
    const ip = request.headers.get('cf-connecting-ip');
    
    if (!ip || !IPINFO_TOKEN) {
        return null;
    }
    
    try {
        const response = await fetch(`https://ipinfo.io/${ip}?token=${IPINFO_TOKEN}`, {
            cf: {
                cacheTtl: 86400, // Cache IP info for 24 hours
                cacheEverything: true
            }
        });
        
        if (!response.ok) {
            return null;
        }
        
        const data = await response.json();
        const country = (data.country || '').toUpperCase();
        
        // Map country to language
        const countryLangMap = {
            'US': 'en', 'GB': 'en', 'CA': 'en', 'AU': 'en', 'NZ': 'en', 'IE': 'en',
            'FR': 'fr', 'BE': 'fr', 'CH': 'fr',
            'ES': 'es', 'MX': 'es', 'AR': 'es', 'CL': 'es', 'CO': 'es', 'PE': 'es', 'VE': 'es',
            'DE': 'de', 'AT': 'de',
            'IT': 'it',
            'PT': 'pt', 'BR': 'pt',
            'RU': 'ru', 'UA': 'ru',
            'CN': 'zh', 'TW': 'zh', 'HK': 'zh',
            'JP': 'ja',
            'KR': 'ko',
            'IN': 'en', 'PH': 'en', 'SG': 'en', 'ZA': 'en'
        };
        
        return countryLangMap[country] || null;
        
    } catch (error) {
        console.error('IP detection error:', error);
        return null;
    }
}

/**
 * Rewrite internal links to use correct language subdomain
 */
function rewriteInternalLinks(html, lang, currentHost) {
    const baseDomain = getBaseDomain(currentHost);
    
    // Rewrite absolute URLs
    html = html.replace(
        new RegExp(`href="https?://(www\\.)?${baseDomain.replace('.', '\\.')}([^"]*)"`, 'g'),
        (match, www, path) => {
            if (lang === DEFAULT_LANG) {
                return `href="https://${baseDomain}${path}"`;
            }
            return `href="https://${lang}.${baseDomain}${path}"`;
        }
    );
    
    // Rewrite relative URLs
    html = html.replace(
        /href="(\/[^"]*)"/g,
        (match, path) => {
            // Skip admin, login, and API paths
            if (shouldExcludePath(path)) {
                return match;
            }
            
            if (lang === DEFAULT_LANG) {
                return `href="https://${baseDomain}${path}"`;
            }
            return `href="https://${lang}.${baseDomain}${path}"`;
        }
    );
    
    return html;
}

/**
 * Handle OPTIONS requests (CORS)
 */
function handleOptions(request) {
    const headers = new Headers({
        'Access-Control-Allow-Origin': '*',
        'Access-Control-Allow-Methods': 'GET, HEAD, POST, OPTIONS',
        'Access-Control-Allow-Headers': 'Content-Type, X-MCT-Target-Lang',
        'Access-Control-Max-Age': '86400'
    });
    
    return new Response(null, {
        status: 204,
        headers: headers
    });
}
