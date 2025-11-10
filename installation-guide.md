# Multilang Cloud Translate - Complete Installation Guide

## Table of Contents
1. [Prerequisites](#prerequisites)
2. [Google Cloud Setup](#google-cloud-setup)
3. [Plugin Installation](#plugin-installation)
4. [WordPress Configuration](#wordpress-configuration)
5. [Cloudflare Workers Setup (Optional)](#cloudflare-workers-setup)
6. [IPinfo.io Setup (Optional)](#ipinfoio-setup)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

Before installing the plugin, ensure you have:

- **WordPress** 5.8 or higher
- **PHP** 7.4 or higher
- **Composer** installed on your server
- **Google Cloud account** with billing enabled
- **SSH/Terminal access** to your server (for Composer)
- **(Optional)** Cloudflare account for Workers mode
- **(Optional)** IPinfo.io account for IP-based detection

---

## Google Cloud Setup

### Step 1: Create a Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Click **Create Project**
3. Enter project name (e.g., "My Website Translations")
4. Note your **Project ID** (you'll need this later)

### Step 2: Enable Required APIs

1. Go to **APIs & Services** > **Library**
2. Search for and enable:
   - **Cloud Translation API**
   - **Cloud Storage API**

### Step 3: Create a Service Account

1. Go to **IAM & Admin** > **Service Accounts**
2. Click **Create Service Account**
3. Enter name: `wordpress-translator`
4. Click **Create and Continue**
5. Add these roles:
   - **Cloud Translation API User**
   - **Storage Object Admin**
6. Click **Done**

### Step 4: Create and Download JSON Key

1. Click on the service account you just created
2. Go to **Keys** tab
3. Click **Add Key** > **Create new key**
4. Choose **JSON** format
5. Click **Create**
6. Save the downloaded file as `google-credentials.json`

### Step 5: Create a Google Cloud Storage Bucket

1. Go to **Cloud Storage** > **Buckets**
2. Click **Create Bucket**
3. Enter name (e.g., `your-domain-translations`)
4. Choose **Region** closest to your users
5. Choose **Standard** storage class
6. Set **Access Control**: Uniform
7. Click **Create**

### Step 6: Set Bucket Permissions

1. Click on your bucket
2. Go to **Permissions** tab
3. Click **Grant Access**
4. Add your service account email
5. Assign role: **Storage Object Admin**
6. Click **Save**

### Step 7: Get API Key (Optional but Recommended)

1. Go to **APIs & Services** > **Credentials**
2. Click **Create Credentials** > **API Key**
3. Copy and save the API key
4. Click **Restrict Key**
5. Under **API restrictions**, select:
   - Cloud Translation API
6. Click **Save**

---

## Plugin Installation

### Method 1: Manual Installation with Composer

1. **Download the plugin** from GitHub or your source
2. **Upload to WordPress**:
   ```bash
   cd /path/to/wordpress/wp-content/plugins/
   # Upload multilang-cloud-translate folder here
   ```

3. **Install Composer dependencies**:
   ```bash
   cd multilang-cloud-translate
   composer install
   ```

4. **Upload Google credentials**:
   ```bash
   cp /path/to/google-credentials.json /path/to/wordpress/wp-content/
   chmod 600 /path/to/wordpress/wp-content/google-credentials.json
   ```

5. **Activate the plugin**:
   - Go to WordPress Admin > Plugins
   - Find "Multilang Cloud Translate"
   - Click **Activate**

### Method 2: Using WP-CLI

```bash
cd /path/to/wordpress
wp plugin install /path/to/multilang-cloud-translate.zip --activate
cd wp-content/plugins/multilang-cloud-translate
composer install
cp /path/to/google-credentials.json /path/to/wordpress/wp-content/
```

---

## WordPress Configuration

### Step 1: Access Plugin Settings

1. Log in to WordPress Admin
2. Go to **Settings** > **Multilang Translate**

### Step 2: Configure Google Cloud Settings

Enter the following information:

- **Google API Key**: Your API key from Step 7 of Google Cloud Setup
- **Google Project ID**: Your project ID from Step 1
- **GCS Bucket Name**: Your bucket name from Step 5 (e.g., `your-domain-translations`)

### Step 3: Configure Language Settings

- **Active Languages**: Enter comma-separated language codes
  - Example: `en,fr,es,de,it,pt`
  - Available codes: en, fr, es, de, it, pt, ru, zh, ja, ko, ar, nl, pl, etc.

- **Default Language**: Your content's original language
  - Example: `en`

### Step 4: Choose Translation Mode

**Option A: PHP Rewrites (Recommended for Beginners)**
- Select: **PHP Rewrites**
- URLs will use query parameters: `example.com?lang=fr`
- Works on any hosting provider
- No additional setup required

**Option B: Cloudflare Workers (Advanced)**
- Select: **Cloudflare Workers**
- URLs will use subdomains: `fr.example.com`
- Requires Cloudflare Workers setup (see next section)
- Better performance with edge caching

### Step 5: Configure Auto-Detection (Optional)

- **IPinfo.io Token**: Enter your token if you have one
- **Enable IP-based Redirect**: Check to enable automatic redirection based on visitor location
- **Enable Auto Redirect**: Check to enable browser language detection

### Step 6: Save Settings

Click **Save Settings** button

---

## Cloudflare Workers Setup (Optional)

### Prerequisites
- Cloudflare account
- Your domain managed by Cloudflare
- Cloudflare Workers subscription ($5/month for unlimited requests)

### Step 1: Set Up DNS

1. Go to Cloudflare Dashboard
2. Select your domain
3. Go to **DNS** tab
4. Add wildcard record:
   - Type: `A` or `CNAME`
   - Name: `*` (asterisk)
   - Target: Your server IP or domain
   - Proxy status: **Proxied** (orange cloud)

### Step 2: Create Worker

1. Go to **Workers & Pages**
2. Click **Create Worker**
3. Name it: `multilang-translate`
4. Click **Deploy**

### Step 3: Configure Worker

1. Click **Quick Edit**
2. Copy the entire content of `cloudflare-worker.js`
3. Paste into the editor
4. Update these variables:
   ```javascript
   const WP_BASE_URL = 'https://www.example.com'; // Your WordPress URL
   const DEFAULT_LANG = 'en';
   const CACHE_TTL = 3600;
   ```
5. Click **Save and Deploy**

### Step 4: Add Environment Variables

1. Go to Worker **Settings** > **Variables**
2. Add:
   - **Name**: `IPINFO_TOKEN`
   - **Value**: Your IPinfo.io token
   - Check **Encrypt**
3. Click **Save**

### Step 5: Create Worker Route

1. Go back to your domain's **Workers Routes**
2. Click **Add Route**
3. Configure:
   - Route: `*example.com/*`
   - Worker: `multilang-translate`
4. Click **Save**

### Step 6: Test Subdomain

1. Visit `fr.example.com`
2. Check if content is translated
3. Check browser console for errors

---

## IPinfo.io Setup (Optional)

### Step 1: Create Account

1. Go to [IPinfo.io](https://ipinfo.io/)
2. Click **Sign Up**
3. Choose free plan (50,000 requests/month)

### Step 2: Get API Token

1. Log in to dashboard
2. Go to **Token** section
3. Copy your access token

### Step 3: Add to WordPress

1. Go to WordPress Admin > Settings > Multilang Translate
2. Paste token in **IPinfo.io Token** field
3. Check **Enable IP-based Redirect**
4. Click **Save Settings**

---

## Testing

### Test 1: Manual Translation

1. Visit your homepage
2. Add `?lang=fr` to URL
3. Verify content is translated

### Test 2: Language Switcher

Add to your theme:
```php
<?php if (function_exists('mct_language_switcher')) {
    mct_language_switcher();
} ?>
```

Or use shortcode:
```
[mct_language_switcher]
```

### Test 3: Check SEO Tags

1. View page source
2. Look for `<link rel="alternate" hreflang="fr" ...>`
3. Verify canonical tags are present

### Test 4: Check Logs

1. Go to Settings > Multilang Translate
2. Scroll to **Translation Logs**
3. Verify translations are being logged

### Test 5: Cache Test

1. Visit a page with `?lang=fr`
2. Reload the page
3. Translation should be instant (served from cache)

---

## Troubleshooting

### Problem: "Google credentials file not found"

**Solution**:
```bash
# Check if file exists
ls -la /path/to/wordpress/wp-content/google-credentials.json

# If missing, upload it
scp google-credentials.json user@server:/path/to/wp-content/

# Set permissions
chmod 600 /path/to/wordpress/wp-content/google-credentials.json
```

### Problem: "Composer dependencies not installed"

**Solution**:
```bash
cd /path/to/wordpress/wp-content/plugins/multilang-cloud-translate
composer install --no-dev --optimize-autoloader
```

### Problem: Translations not appearing

**Checklist**:
1. Check if API key is valid
2. Check if GCS bucket exists
3. View translation logs for errors
4. Check PHP error logs: `/var/log/php-error.log`
5. Enable WordPress debug:
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

### Problem: Cloudflare Worker not routing

**Solution**:
1. Check DNS settings (wildcard `*` record)
2. Verify Worker route matches domain
3. Check Worker logs in Cloudflare dashboard
4. Test direct WordPress URL (bypass Cloudflare)

### Problem: High translation costs

**Solution**:
1. Check cache hit rate in Translation Analytics
2. Clear and regenerate cache: Settings > Clear Translation Cache
3. Use batch translation for all content first
4. Verify caching is working (check GCS bucket)

### Problem: Yoast SEO conflicts

**Solution**:
The plugin is designed to work with Yoast. If issues occur:
1. Deactivate plugin
2. Update Yoast to latest version
3. Reactivate plugin
4. Clear all caches

---

## Next Steps

After successful installation:

1. **Pre-generate translations** for all content:
   ```bash
   wp mct translate-all fr
   wp mct translate-all es
   ```

2. **Set up Google Analytics** (optional):
   - Go to Settings > Multilang Translate
   - Enter GA4 Measurement ID
   - Enable analytics

3. **Customize language switcher** in your theme

4. **Submit multilingual sitemap** to Google Search Console:
   - `https://example.com/sitemap.xml`
   - `https://example.com/sitemap-fr.xml`
   - `https://example.com/sitemap-es.xml`

5. **Monitor translation logs** regularly

6. **Set up automated backups** of GCS bucket

---

## Support

For issues and questions:
- Documentation: https://example.com/docs
- GitHub Issues: https://github.com/username/multilang-cloud-translate/issues
- Support Forum: https://example.com/support

---

## License

GPL v2 or later
