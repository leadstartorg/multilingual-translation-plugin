# Quick Start Guide - Multilang Cloud Translate

Get your WordPress site multilingual in 15 minutes!

## Prerequisites Checklist

- [ ] WordPress 5.8+ installed
- [ ] PHP 7.4+ available
- [ ] Composer installed
- [ ] Google Cloud account with billing enabled
- [ ] Terminal/SSH access to server

---

## 5-Minute Google Cloud Setup

### 1. Create Project & Enable APIs (2 minutes)

```bash
# Install gcloud CLI (if not installed)
# Visit: https://cloud.google.com/sdk/docs/install

# Login
gcloud auth login

# Create project
gcloud projects create my-translations-$(date +%s) --name="My Translations"
PROJECT_ID="my-translations-XXXXX"  # Use the ID from above command

# Set project
gcloud config set project $PROJECT_ID

# Enable APIs
gcloud services enable translate.googleapis.com storage.googleapis.com
```

### 2. Create Service Account (1 minute)

```bash
# Create service account
gcloud iam service-accounts create wordpress-translator \
    --display-name="WordPress Translator"

# Get service account email
SA_EMAIL=$(gcloud iam service-accounts list \
    --filter="displayName:WordPress Translator" \
    --format="value(email)")

# Grant roles
gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:$SA_EMAIL" \
    --role="roles/cloudtranslate.user"

gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:$SA_EMAIL" \
    --role="roles/storage.objectAdmin"

# Create and download key
gcloud iam service-accounts keys create google-credentials.json \
    --iam-account=$SA_EMAIL
```

### 3. Create Storage Bucket (1 minute)

```bash
# Create bucket (replace with your domain)
BUCKET_NAME="translations-$(date +%s)"
gsutil mb -c STANDARD -l US gs://$BUCKET_NAME

# Grant access to service account
gsutil iam ch serviceAccount:$SA_EMAIL:objectAdmin gs://$BUCKET_NAME

echo "✅ Setup complete!"
echo "Project ID: $PROJECT_ID"
echo "Bucket Name: $BUCKET_NAME"
echo "Credentials saved to: google-credentials.json"
```

### 4. Get API Key (1 minute)

Visit: https://console.cloud.google.com/apis/credentials
- Click **Create Credentials** > **API Key**
- Copy the key
- Click **Restrict Key**
- Under API restrictions, select: Cloud Translation API
- Save

---

## 5-Minute WordPress Setup

### 1. Upload Plugin (2 minutes)

```bash
# SSH into your server
ssh user@your-server.com

# Navigate to plugins directory
cd /var/www/html/wp-content/plugins

# Upload plugin (method 1: from local)
scp -r /local/path/to/multilang-cloud-translate user@server:/var/www/html/wp-content/plugins/

# OR download from GitHub (method 2)
git clone https://github.com/username/multilang-cloud-translate.git

# Install dependencies
cd multilang-cloud-translate
composer install --no-dev --optimize-autoloader
```

### 2. Upload Credentials (1 minute)

```bash
# Upload google-credentials.json
scp google-credentials.json user@server:/var/www/html/wp-content/

# Set proper permissions
cd /var/www/html/wp-content
chmod 600 google-credentials.json
chown www-data:www-data google-credentials.json
```

### 3. Activate & Configure (2 minutes)

1. **Activate Plugin**:
   - Go to WordPress Admin → Plugins
   - Find "Multilang Cloud Translate"
   - Click Activate

2. **Quick Configuration**:
   - Go to Settings → Multilang Translate
   - Enter:
     - **Google Project ID**: `YOUR_PROJECT_ID`
     - **Google API Key**: `YOUR_API_KEY`
     - **GCS Bucket**: `YOUR_BUCKET_NAME`
     - **Active Languages**: `en,fr,es,de`
     - **Default Language**: `en`
     - **Translation Mode**: PHP Rewrites
   - Click **Save Settings**

---

## Test It! (2 minutes)

### Quick Test

1. **Visit your homepage**
2. **Add `?lang=fr` to the URL**
3. **See translated content!**

Example:
```
http://example.com → http://example.com?lang=fr
```

### Add Language Switcher

Add this shortcode to any page/post:
```
[mct_language_switcher]
```

Or add to your theme template:
```php
<?php mct_language_switcher(); ?>
```

---

## Common Languages Setup

### Popular Language Combinations

**European Languages**:
```
en,fr,de,es,it,pt,nl
```

**Asian Languages**:
```
en,zh,ja,ko,hi,th,vi
```

**Global Mix**:
```
en,es,fr,de,pt,ar,zh,ja,ru
```

**All Major Languages**:
```
en,es,fr,de,pt,it,ru,zh,ja,ko,ar,hi,tr,nl,pl,sv
```

---

## Quick Commands Reference

### WordPress CLI

```bash
# Translate all content to French
wp mct translate-all fr

# Translate all content to multiple languages
wp mct translate-all fr && wp mct translate-all es && wp mct translate-all de

# Clear translation cache
wp mct clear-cache

# Check plugin status
wp plugin status multilang-cloud-translate
```

### Server Management

```bash
# Check if credentials exist
ls -la /var/www/html/wp-content/google-credentials.json

# View error logs
tail -f /var/www/html/wp-content/debug.log

# Check composer dependencies
cd /var/www/html/wp-content/plugins/multilang-cloud-translate
composer show

# Update dependencies
composer update
```

---

## Cost Estimate

### Google Cloud Translation API Pricing

- **First 500,000 characters/month**: FREE
- **After that**: $20 per 1 million characters

### Example Costs

**Small blog** (10 pages, 5,000 words total):
- Per language: ~30,000 characters
- 3 languages: 90,000 characters
- **Cost: FREE** (within free tier)

**Medium website** (100 pages, 50,000 words):
- Per language: ~300,000 characters
- 3 languages: 900,000 characters
- **Cost: FREE** (within free tier)

**Large website** (1,000 pages, 500,000 words):
- Per language: ~3,000,000 characters
- 3 languages: 9,000,000 characters
- **Cost: ~$170/month**
- **With caching**: After initial translation, almost $0

### Google Cloud Storage Pricing

- **First 5 GB/month**: FREE
- **Storage**: $0.02 per GB/month
- Translation cache is typically < 1 GB
- **Cost: FREE for most sites**

---

## Optimization Tips

### 1. Pre-Translate All Content

```bash
# Translate everything at once (save API costs)
wp mct translate-all fr
wp mct translate-all es
wp mct translate-all de
```

### 2. Enable Caching

- ✅ GCS caching is enabled by default
- Cache never expires unless you clear it
- Updates to content auto-clear relevant cache

### 3. Monitor Usage

1. Check logs: Settings → Translation Analytics
2. View cache hit rate (should be >95% after initial translation)
3. Monitor Google Cloud costs in GCP Console

---

## Next Steps

### Essential
- [ ] Add language switcher to your theme
- [ ] Test all languages
- [ ] Submit sitemap to Google Search Console

### Recommended
- [ ] Set up IP-based auto-redirect
- [ ] Configure Google Analytics 4
- [ ] Pre-translate all content via WP-CLI

### Advanced
- [ ] Deploy Cloudflare Worker for subdomain routing
- [ ] Set up custom glossary for brand terms
- [ ] Configure automated backups

---

## Troubleshooting

### Plugin not activating?

```bash
# Check PHP version
php -v  # Must be 7.4+

# Check composer dependencies
cd wp-content/plugins/multilang-cloud-translate
composer install
```

### Translations not working?

1. Check Settings → Multilang Translate
2. Verify all fields are filled
3. Check Translation Logs for errors
4. Enable WordPress debug in `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

### High Google Cloud costs?

1. Check cache hit rate: Settings → Translation Analytics
2. Should be >95% after initial translation
3. If low, verify GCS bucket is accessible
4. Clear and regenerate cache

---

## Support & Resources

- **Full Documentation**: `INSTALLATION-GUIDE.md`
- **Developer Docs**: `DEVELOPER-GUIDE.md`
- **GitHub**: https://github.com/username/multilang-cloud-translate
- **Support**: https://example.com/support

---

## Success! 🎉

Your WordPress site is now multilingual!

Visit: `https://example.com?lang=fr` to see it in action.

Add `?lang=` with any configured language code to test:
- `?lang=en` - English
- `?lang=fr` - French
- `?lang=es` - Spanish
- `?lang=de` - German
- `?lang=it` - Italian

---

**Made with ❤️ by Jessica Kafor**
