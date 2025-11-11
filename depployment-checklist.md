# Production Deployment Checklist

Use this checklist to ensure a smooth deployment to production.

## Pre-Deployment

### Google Cloud Setup
- [ ] Google Cloud project created
- [ ] Billing enabled on project
- [ ] Translation API enabled
- [ ] Cloud Storage API enabled
- [ ] Service account created with correct roles
- [ ] JSON credentials downloaded
- [ ] GCS bucket created in appropriate region
- [ ] Bucket permissions configured
- [ ] API key created and restricted
- [ ] Monthly budget alert configured ($50 recommended)

### Plugin Installation
- [ ] Plugin files uploaded to server
- [ ] Composer dependencies installed (`composer install --no-dev`)
- [ ] google-credentials.json uploaded to wp-content/
- [ ] File permissions set correctly (600 for credentials)
- [ ] Plugin activated in WordPress
- [ ] No PHP errors in logs

### Configuration
- [ ] All required settings filled in admin panel
- [ ] Active languages configured
- [ ] Default language set
- [ ] Translation mode selected
- [ ] Cache TTL configured
- [ ] Test translation successful (`?lang=fr`)
- [ ] Translation logs show activity

## Testing Phase

### Functionality Tests
- [ ] Manual translation works (`?lang=fr`)
- [ ] Content translates correctly
- [ ] Images remain intact
- [ ] Links work properly
- [ ] Forms still function
- [ ] Menu translates correctly
- [ ] Widgets translate properly
- [ ] Footer content translates

### SEO Tests
- [ ] Hreflang tags present in page source
- [ ] Canonical tags correct
- [ ] Sitemap includes all languages
- [ ] Sitemap accessible: `/sitemap.xml`
- [ ] Language-specific sitemaps work: `/sitemap-fr.xml`
- [ ] Meta descriptions translated
- [ ] Open Graph tags translated
- [ ] JSON-LD structured data translated
- [ ] Yoast SEO compatibility verified (if applicable)

### Performance Tests
- [ ] Initial translation completes
- [ ] Second visit uses cache (instant load)
- [ ] Cache hit rate >90% after warm-up
- [ ] Page load time acceptable (<3 seconds)
- [ ] GCS bucket shows cached files
- [ ] No memory issues
- [ ] No timeout errors

### Browser Tests
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Safari (iOS)
- [ ] Mobile Chrome (Android)

### Language Tests
Test each configured language:
- [ ] English (default)
- [ ] French
- [ ] Spanish
- [ ] German
- [ ] (Add others...)

## Cloudflare Workers (If Applicable)

- [ ] Cloudflare account active
- [ ] Domain DNS managed by Cloudflare
- [ ] Wildcard DNS record created (`*`)
- [ ] Worker script deployed
- [ ] Environment variables set
- [ ] Worker route configured
- [ ] Subdomains work (fr.example.com)
- [ ] Edge caching functional
- [ ] Cache headers correct
- [ ] IP detection working (if enabled)

## IPinfo.io (If Applicable)

- [ ] IPinfo.io account created
- [ ] API token obtained
- [ ] Token added to plugin settings
- [ ] IP-based redirect enabled
- [ ] Test from different countries/IPs
- [ ] Fallback to browser language works
- [ ] Cookie preferences respected

## Security

### Credentials
- [ ] google-credentials.json not publicly accessible
- [ ] File permissions: 600 or 640
- [ ] Not committed to version control
- [ ] Backup stored securely
- [ ] API keys restricted to specific APIs

### WordPress
- [ ] WordPress updated to latest version
- [ ] All plugins updated
- [ ] PHP version 7.4 or higher
- [ ] SSL certificate active (HTTPS)
- [ ] Security plugin installed (optional)
- [ ] Regular backups configured

## Performance Optimization

### Pre-Translation
- [ ] All content pre-translated:
  ```bash
  wp mct translate-all fr
  wp mct translate-all es
  wp mct translate-all de
  ```
- [ ] Cache warmed up
- [ ] Cache hit rate monitored

### Caching Strategy
- [ ] GCS caching enabled
- [ ] Cache TTL appropriate (3600s default)
- [ ] Object caching configured (optional)
- [ ] CDN caching rules set (if using CDN)
- [ ] Browser caching enabled

### Database
- [ ] Translation logs table optimized
- [ ] Old logs cleaned (keep 90 days)
- [ ] Database backed up
- [ ] Index performance verified

## Monitoring

### Google Cloud
- [ ] Budget alerts configured
- [ ] Usage monitoring active
- [ ] API quotas appropriate
- [ ] Storage usage monitored
- [ ] Cost breakdown understood

### WordPress
- [ ] Translation logs accessible
- [ ] Analytics working (if enabled)
- [ ] Error logging enabled
- [ ] Performance monitoring active
- [ ] Uptime monitoring configured

### Alerts
- [ ] Email alerts for errors configured
- [ ] Budget alerts set up
- [ ] Uptime alerts configured
- [ ] Performance degradation alerts

## Documentation

### Internal Documentation
- [ ] Server credentials documented
- [ ] Google Cloud project details recorded
- [ ] API keys and tokens securely stored
- [ ] Deployment process documented
- [ ] Rollback procedure documented
- [ ] Emergency contacts listed

### User Documentation
- [ ] Language switcher explained to content team
- [ ] Translation logs access documented
- [ ] Cache clearing procedure explained
- [ ] Support contact provided

## SEO & Marketing

### Search Engines
- [ ] Sitemaps submitted to Google Search Console
  - Main sitemap
  - Language-specific sitemaps
- [ ] Sitemaps submitted to Bing Webmaster Tools
- [ ] robots.txt updated with sitemap links
- [ ] International targeting set in Search Console

### Analytics
- [ ] Google Analytics 4 configured
- [ ] Language dimension tracking
- [ ] Custom events for translations (if enabled)
- [ ] Goals/conversions updated for each language
- [ ] Analytics verified and tracking

### Marketing
- [ ] Marketing team notified of new languages
- [ ] Social media profiles updated
- [ ] Email campaigns updated for multilingual
- [ ] Language targeting in ad campaigns updated

## Content Strategy

### Editorial
- [ ] Content team trained on multilingual site
- [ ] Translation review process established
- [ ] Brand terminology documented
- [ ] Style guide updated for multilingual
- [ ] Content calendar updated

### Quality Control
- [ ] Sample translations reviewed by native speakers
- [ ] Brand terminology consistent
- [ ] Cultural sensitivity checked
- [ ] Technical accuracy verified
- [ ] Call-to-action buttons appropriate

## Legal & Compliance

### Privacy
- [ ] Privacy policy updated for multilingual
- [ ] Cookie policy updated
- [ ] GDPR compliance verified (EU)
- [ ] CCPA compliance verified (California)
- [ ] Data processing agreement with Google reviewed

### Terms & Conditions
- [ ] Terms translated (if legally required)
- [ ] Legal disclaimers translated
- [ ] Copyright notices updated
- [ ] Accessibility statements translated

## Launch Day

### Final Checks (1 hour before)
- [ ] Full site backup completed
- [ ] Database backup completed
- [ ] Google credentials backup verified
- [ ] Rollback plan ready
- [ ] Team on standby
- [ ] Monitoring dashboards open

### Go-Live
- [ ] Plugin activated (if not already)
- [ ] Cache warmed up
- [ ] Test each language one final time
- [ ] Monitor error logs
- [ ] Monitor server performance
- [ ] Monitor Google Cloud costs

### First Hour
- [ ] Check all languages loading
- [ ] Monitor translation logs
- [ ] Check error logs
- [ ] Verify analytics tracking
- [ ] Test language switcher
- [ ] Verify SEO tags present

### First Day
- [ ] Monitor translation costs
- [ ] Check cache hit rate (should be >95%)
- [ ] Review analytics data
- [ ] Check for any error patterns
- [ ] Collect user feedback
- [ ] Verify all forms working

### First Week
- [ ] Weekly cost review
- [ ] Performance analysis
- [ ] User feedback review
- [ ] SEO ranking check
- [ ] Cache optimization
- [ ] Content quality review

## Post-Launch

### Week 1
- [ ] Daily monitoring
- [ ] Cost tracking
- [ ] Performance optimization
- [ ] User feedback collection
- [ ] Bug fixes as needed

### Month 1
- [ ] Monthly cost analysis
- [ ] Translation quality audit
- [ ] SEO performance review
- [ ] Analytics deep dive
- [ ] Content strategy adjustment
- [ ] Team training updates

### Ongoing
- [ ] Monthly cost review
- [ ] Quarterly translation audit
- [ ] Regular cache optimization
- [ ] Content updates translated
- [ ] Plugin updates applied
- [ ] Security patches applied

## Emergency Procedures

### If Site Goes Down
1. [ ] Deactivate plugin via FTP/database
2. [ ] Check error logs
3. [ ] Contact hosting support
4. [ ] Roll back to previous version
5. [ ] Document issue
6. [ ] Fix and test before reactivating

### If Translations Break
1. [ ] Clear translation cache
2. [ ] Check Google Cloud status
3. [ ] Verify API keys valid
4. [ ] Check credentials file accessible
5. [ ] Test connection to Google Cloud
6. [ ] Re-translate if needed

### If Costs Spike
1. [ ] Check cache hit rate
2. [ ] Review translation logs
3. [ ] Identify source of requests
4. [ ] Clear and regenerate cache
5. [ ] Contact support if suspicious activity

## Success Metrics

### Technical KPIs
- [ ] Cache hit rate >95%
- [ ] Page load time <3 seconds
- [ ] Translation accuracy >95%
- [ ] Zero critical errors
- [ ] Uptime >99.9%

### Business KPIs
- [ ] International traffic increase
- [ ] Engagement rate per language
- [ ] Conversion rate per language
- [ ] Bounce rate acceptable (<60%)
- [ ] ROI positive

## Sign-Off

- [ ] Technical lead approval
- [ ] Product owner approval
- [ ] Marketing lead approval
- [ ] Legal/compliance approval
- [ ] Executive approval (if required)

---

**Deployment Date**: _________________

**Deployed By**: _________________

**Verified By**: _________________

**Notes**:
_________________________________________________________________
_________________________________________________________________
_________________________________________________________________

---

## Quick Reference

### Important URLs
- Admin: `https://example.com/wp-admin`
- Settings: `Settings → Multilang Translate`
- Main sitemap: `https://example.com/sitemap.xml`
- French sitemap: `https://example.com/sitemap-fr.xml`
- Analytics: `https://analytics.google.com`
- Google Cloud Console: `https://console.cloud.google.com`

### Emergency Contacts
- Hosting support: _______________
- Google Cloud support: _______________
- Developer: _______________
- Project manager: _______________

### Key Credentials Location
- Google credentials: `/wp-content/google-credentials.json`
- API keys: Password manager/vault
- Cloudflare token: Password manager/vault
- IPinfo token: Password manager/vault

---

**Good luck with your deployment! 🚀**
