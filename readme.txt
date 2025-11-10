=== Multilang Cloud Translate ===
Contributors: Jessica Kafor
Tags: translation, multilingual, google translate, cloudflare, i18n, localization
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional multilingual WordPress plugin with Google Cloud Translation API v3, Google Cloud Storage caching, and Cloudflare Workers support.

== Description ==

**Multilang Cloud Translate** is a comprehensive multilingual WordPress solution that combines:

* **Google Cloud Translation API v3** for high-quality, accurate translations
* **Google Cloud Storage** for efficient translation caching
* **Cloudflare Workers** support for edge-level caching and routing
* **IP-based language detection** using IPinfo.io
* **SEO-optimized** with hreflang, canonical tags, and sitemap integration
* **Yoast SEO compatible** with automatic multilingual metadata injection

= Key Features =

**Translation Engine:**
* Google Cloud Translation API v3 with glossary support
* Automatic content translation (posts, pages, widgets, menus)
* Translation memory to avoid duplicate API costs
* Batch translation support

**Caching & Performance:**
* Google Cloud Storage for persistent translation cache
* Edge-level caching with Cloudflare Workers
* Configurable cache TTL
* Automatic cache purge on content updates

**SEO Features:**
* Automatic hreflang tags for all languages
* Canonical URL management
* Multilingual sitemap generation
* Open Graph and Twitter Card translation
* JSON-LD structured data translation
* Yoast SEO and Rank Math compatible

**Language Detection:**
* IP-based detection via IPinfo.io
* Browser language detection fallback
* Manual language switcher
* Cookie-based preference storage

**Two Operating Modes:**
* **Cloudflare Workers Mode:** Subdomain routing (fr.example.com) with edge caching
* **PHP Rewrites Mode:** Query parameters (?lang=fr) for standard hosting

**Analytics & Logging:**
* Native WordPress translation logs
* Google Analytics 4 integration
* Translation statistics and reports
* CSV export functionality

**Developer Friendly:**
* WP-CLI commands
* REST API endpoints
* Template functions
* Comprehensive hooks and filters

= Supported Languages =

English, French, Spanish, German, Italian, Portuguese, Russian, Chinese, Japanese, Korean, Arabic, Dutch, Polish, Swedish, Norwegian, Danish, Finnish, Turkish, Greek, Hebrew, Hindi, Thai, Vietnamese, Indonesian, Malay, Czech, Slovak, Hungarian, Romanian, Bulgarian, Ukrainian, and 100+ more via Google Cloud Translation API.

= Requirements =

* PHP 7.4 or higher
* WordPress 5.8 or higher
* Google Cloud account with Translation API enabled
* Google Cloud Storage bucket
* Composer (for installing dependencies)
* Optional: Cloudflare account (for Workers mode)
* Optional: IPinfo.io account (for IP-based detection)

== Installation ==

= Automatic Installation =

1. Search for "Multilang Cloud Translate" in WordPress plugin directory
2. Click "Install Now"
3. Activate the plugin

= Manual Installation =

1. Download the plugin ZIP file
2. Upload to `/wp-content/plugins/` directory
3. Extract the ZIP file
4. Run `composer install` in the plugin directory
5. Place `google-credentials.json` in `/wp-content/` directory
6. Activate the plugin through WordPress admin

= Configuration =

1. Go to **Settings > Multilang Translate**
2. Enter your **Google Cloud Project ID**
3. Enter your **Google Cloud API Key**
4. Enter your **GCS Bucket Name**
5. Set **Active Languages** (comma-separated, e.g., en,fr,es,de)
6. Choose **Translation Mode** (Cloudflare Workers or PHP Rewrites)
7. Optional: Enter **IPinfo.io Token** for IP-based detection
8. Click **Save Settings**

= Google Cloud Setup =

1. Create a Google Cloud project
2. Enable Cloud Translation API
3. Create a service account with "Cloud Translation API User" role
4. Create a GCS bucket with "Storage Object Admin" permissions
5. Download service account JSON key
6. Save as `google-credentials.json` in `/wp-content/` directory

= Cloudflare Workers Setup (Optional) =

1. Copy the `cloudflare-worker.js` file from plugin directory
2. Deploy to Cloudflare Workers
3. Set environment variables (IPINFO_TOKEN, WP_BASE_URL)
4. Configure DNS with wildcard subdomain (*.example.com)
5. Enable Workers route for your domain

== Frequently Asked Questions ==

= How much does Google Cloud Translation cost? =

Google Cloud Translation API v3 charges per character translated. First 500,000 characters per month are free, then $20 per 1 million characters. The plugin's caching system minimizes costs by storing translations.

= Can I use this without Cloudflare? =

Yes! The plugin works in PHP Rewrites mode on any hosting provider. Cloudflare Workers are optional for enhanced performance.

= Does it work with Yoast SEO? =

Yes! The plugin is fully compatible with Yoast SEO and automatically translates meta titles, descriptions, and adds hreflang tags to Yoast's sitemap.

= How do I add a language switcher to my site? =

Use the shortcode `[mct_language_switcher]` or call the function `mct_language_switcher()` in your theme template.

= Can I translate custom post types? =

Yes! The plugin automatically translates all public post types.

= How do I clear the translation cache? =

Go to Settings > Multilang Translate and click the "Clear Translation Cache" button.

= Is there a limit on content length? =

Google Cloud Translation API can handle up to 30,000 characters per request. The plugin automatically chunks larger content.

== Screenshots ==

1. Admin settings page with all configuration options
2. Translation logs showing recent activity
3. Language switcher in action
4. Hreflang tags in page source
5. Multilingual sitemap with alternate links
6. Analytics dashboard with translation statistics

== Changelog ==

= 1.0.0 - 2025-01-10 =
* Initial release
* Google Cloud Translation API v3 integration
* Google Cloud Storage caching
* Cloudflare Workers support
* IP-based language detection
* SEO optimization with hreflang and canonical tags
* Multilingual sitemap generation
* Native WordPress logging
* Google Analytics 4 integration
* Yoast SEO compatibility
* REST API endpoints
* WP-CLI commands

== Upgrade Notice ==

= 1.0.0 =
Initial release of Multilang Cloud Translate plugin.

== Additional Info ==

For detailed documentation, visit: https://example.com/docs

For support, visit: https://example.com/support

GitHub repository: https://github.com/username/multilang-cloud-translate

== Credits ==

* Google Cloud Translation API
* Google Cloud Storage
* Cloudflare Workers
* IPinfo.io

== License ==

This plugin is licensed under GPL v2 or later.
