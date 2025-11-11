# Multilang Cloud Translate - WordPress Plugin

**Professional multilingual WordPress solution powered by Google Cloud Translation API v3, Cloud Storage, and Cloudflare Workers.**

[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-blue)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2-green)](https://www.gnu.org/licenses/gpl-2.0.html)

Transform your WordPress website into a multilingual powerhouse with automatic translation, edge caching, and SEO optimization.

---

## ✨ Features

### 🌍 Translation Engine
- **Google Cloud Translation API v3** - Enterprise-grade, accurate translations
- **100+ languages** supported
- **Glossary support** for consistent brand terminology
- **Translation memory** to minimize API costs
- **Batch translation** for bulk content processing

### ⚡ Performance & Caching
- **Google Cloud Storage** - Persistent translation cache
- **Cloudflare Workers** - Edge-level caching and routing
- **99%+ cache hit rate** after initial translation
- **Automatic cache invalidation** on content updates
- **Configurable TTL** for fine-tuned caching

### 🔍 SEO Optimization
- **Automatic hreflang tags** for all languages
- **Canonical URL management**
- **Multilingual sitemaps** with alternate links
- **Open Graph & Twitter Cards** translation
- **JSON-LD structured data** translation
- **Yoast SEO & Rank Math** compatible

### 🎯 Smart Language Detection
- **IP-based detection** via IPinfo.io
- **Browser language** fallback
- **Cookie-based** user preference
- **Manual language switcher**
- **Automatic redirection** (optional)

### 📊 Analytics & Monitoring
- **Native WordPress logs** (no external dependencies)
- **Google Analytics 4** integration
- **Translation statistics** and reports
- **Cache hit rate monitoring**
- **CSV export** functionality

### 🛠️ Developer Friendly
- **Comprehensive hooks & filters**
- **REST API endpoints**
- **WP-CLI commands**
- **Template functions**
- **Well-documented codebase**

---

## 📋 Requirements

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **Composer**: For dependency management
- **Google Cloud** account with billing enabled
- **Cloudflare** account (optional, for Workers mode)
- **IPinfo.io** account (optional, for IP detection)

---

## 🚀 Quick Start

### 1. Install Plugin

```bash
# Navigate to WordPress plugins directory
cd wp-content/plugins/

# Upload plugin
# Then install dependencies
cd multilang-cloud-translate
composer install --no-dev
```

### 2. Configure Google Cloud

1. Create Google Cloud project
2. Enable Translation API and Cloud Storage API
3. Create service account with required roles
4. Download credentials JSON
5. Create GCS bucket

**Detailed instructions**: See [INSTALLATION-GUIDE.md](INSTALLATION-GUIDE.md)

### 3. Activate & Configure

1. Activate plugin in WordPress
2. Go to **Settings** → **Multilang Translate**
3. Enter Google Cloud credentials
4. Set active languages
5. Save settings

### 4. Test

Visit: `https://example.com?lang=fr`

**See translated content!** 🎉

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [QUICK-START.md](QUICK-START.md) | Get started in 15 minutes |
| [INSTALLATION-GUIDE.md](INSTALLATION-GUIDE.md) | Complete setup instructions |
| [DEVELOPER-GUIDE.md](DEVELOPER-GUIDE.md) | API reference & customization |
| [DEPLOYMENT-CHECKLIST.md](DEPLOYMENT-CHECKLIST.md) | Production deployment guide |

---

## 🎨 Usage Examples

### Basic Translation

```php
// Translate text
$translated = mct_translate('Hello World', 'fr');
// Returns: "Bonjour le monde"

// Get current language
$lang = mct_get_current_lang();

// Check if language is active
if (mct_is_language_active('fr')) {
    // French is available
}
```

### Language Switcher

**Shortcode**:
```
[mct_language_switcher]
```

**PHP Function**:
```php
<?php mct_language_switcher(); ?>
```

**Custom Implementation**:
```php
<?php
$languages = mct_get_available_languages();
foreach ($languages as $lang) {
    $url = mct_build_translated_url(get_permalink(), $lang, 'php');
    echo '<a href="' . $url . '">' . strtoupper($lang) . '</a>';
}
?>
```

### WP-CLI Commands

```bash
# Translate all content to French
wp mct translate-all fr

# Clear translation cache
wp mct clear-cache

# Get translation statistics
wp mct stats
```

---

## 🏗️ Architecture

```
┌─────────────────────┐
│  Cloudflare Worker  │  ← Edge caching & routing (optional)
│  (Subdomain mode)   │
└──────────┬──────────┘
           │
           ▼
┌─────────────────────┐
│  WordPress Plugin   │
│  ┌───────────────┐  │
│  │ Translation   │  │  ← Google Cloud Translation API v3
│  │ Engine        │  │
│  └───────────────┘  │
│  ┌───────────────┐  │
│  │ Cache Layer   │  │  ← Google Cloud Storage
│  └───────────────┘  │
│  ┌───────────────┐  │
│  │ SEO Engine    │  │  ← Hreflang, sitemap, metadata
│  └───────────────┘  │
│  ┌───────────────┐  │
│  │ Analytics     │  │  ← Native logging + GA4
│  └───────────────┘  │
└─────────────────────┘
```

---

## 🌐 Translation Modes

### PHP Rewrites Mode (Default)
- **URLs**: `example.com?lang=fr`
- **Setup**: No additional configuration
- **Hosting**: Any WordPress hosting
- **Best for**: Quick setup, shared hosting

### Cloudflare Workers Mode (Advanced)
- **URLs**: `fr.example.com` (subdomains)
- **Setup**: Requires Cloudflare Worker deployment
- **Hosting**: Cloudflare proxied sites
- **Best for**: High-traffic sites, maximum performance

---

## 💰 Cost Estimate

### Google Cloud Translation API
- **Free tier**: 500,000 characters/month
- **Paid**: $20 per 1 million characters
- **With caching**: ~$0 after initial translation

### Example Costs

| Site Size | Languages | Initial Cost | Monthly (Cached) |
|-----------|-----------|--------------|------------------|
| Small (10 pages) | 3 | FREE | $0 |
| Medium (100 pages) | 5 | FREE | $0-5 |
| Large (1,000 pages) | 5 | $50-100 | $0-10 |

**Cache reduces costs by 95%+**

---

## 🔧 Configuration Options

### Required Settings
- **Google Project ID**: Your GCP project
- **Google API Key**: Translation API key
- **GCS Bucket**: Storage bucket name
- **Active Languages**: Comma-separated codes
- **Default Language**: Source language

### Optional Settings
- **Translation Mode**: PHP or Cloudflare
- **IPinfo Token**: For IP detection
- **Enable Auto-Redirect**: Automatic language switching
- **Cache TTL**: Cache duration
- **GA4 Measurement ID**: Analytics integration

---

## 🎯 Supported Languages

English, French, Spanish, German, Italian, Portuguese, Russian, Chinese, Japanese, Korean, Arabic, Dutch, Polish, Swedish, Norwegian, Danish, Finnish, Turkish, Greek, Hebrew, Hindi, Thai, Vietnamese, Indonesian, Malay, Czech, Slovak, Hungarian, Romanian, Bulgarian, Ukrainian, and **100+ more**.

[View full list →](https://cloud.google.com/translate/docs/languages)

---

## 🤝 Contributing

Contributions are welcome! Please read our contributing guidelines.

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Open a Pull Request

---

## 🐛 Bug Reports

Found a bug? Please open an issue with:
- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior

---

## 📝 Changelog

### Version 1.0.0 (2025-01-10)
- Initial release
- Google Cloud Translation API v3 integration
- Google Cloud Storage caching
- Cloudflare Workers support
- IP-based language detection
- SEO optimization
- Native WordPress logging
- Google Analytics 4 integration
- Yoast SEO compatibility
- REST API & WP-CLI commands

---

## 📄 License

This plugin is licensed under [GPL v2](https://www.gnu.org/licenses/gpl-2.0.html) or later.

---

## 👥 Credits

**Author**: Jessica Kafor

**Built with**:
- [Google Cloud Translation API](https://cloud.google.com/translate)
- [Google Cloud Storage](https://cloud.google.com/storage)
- [Cloudflare Workers](https://workers.cloudflare.com/)
- [IPinfo.io](https://ipinfo.io/)

---

## 📞 Support

- **Documentation**: Full documentation available in repository
- **Issues**: [GitHub Issues](https://github.com/username/multilang-cloud-translate/issues)
- **Discussions**: [GitHub Discussions](https://github.com/username/multilang-cloud-translate/discussions)
- **Email**: support@example.com

---

## 🌟 Show Your Support

If this plugin helped you, please:
- ⭐ Star this repository
- 🐦 Tweet about it
- 📝 Write a blog post
- 💬 Share with others

---

## 📸 Screenshots

### Admin Settings
![Admin Settings](screenshots/admin-settings.png)

### Translation Logs
![Translation Logs](screenshots/translation-logs.png)

### Language Switcher
![Language Switcher](screenshots/language-switcher.png)

---

## 🗺️ Roadmap

- [ ] Visual translation editor
- [ ] Import/export translations
- [ ] Machine learning optimization
- [ ] Advanced glossary management
- [ ] Multi-currency support
- [ ] RTL language improvements
- [ ] Translation approval workflow
- [ ] AI-powered quality checks

---

**Made with ❤️ for the WordPress community**

[⬆ Back to top](#multilang-cloud-translate---wordpress-plugin)
