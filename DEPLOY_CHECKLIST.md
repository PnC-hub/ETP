# RiduciSpese - Deploy Checklist

## Pre-Deploy

### 1. Database Setup
- [ ] Create production database `geniusmile_production`
- [ ] Run migration: `SOURCE migrations/001_initial_schema.sql`
- [ ] Verify all tables created with prefix `afts5498_etp_`
- [ ] Test database connection

### 2. Configuration
- [ ] Update `api/config.php`:
  - [ ] Set `ENVIRONMENT` to `'production'`
  - [ ] Verify `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
  - [ ] Update `APP_URL` to `https://riducispese.it`
  - [ ] Update `API_URL` to `https://riducispese.it/api`
  - [ ] Set error display to OFF

### 3. Stripe Configuration
- [ ] Get live Stripe API keys from https://dashboard.stripe.com/apikeys
- [ ] Create products in Stripe:
  - [ ] Pro Monthly (€4.99/month recurring)
  - [ ] Pro Yearly (€39.99/year recurring)
  - [ ] Business (€9.99/month recurring)
- [ ] Get Price IDs and update in config.php
- [ ] Setup webhook endpoint: `https://riducispese.it/api/webhooks/stripe`
- [ ] Get webhook secret and update in config.php
- [ ] Test webhook with Stripe CLI

### 4. Domain & DNS
- [ ] Point `riducispese.it` to server IP
- [ ] Point `www.riducispese.it` to server IP
- [ ] Wait for DNS propagation (use `dig riducispese.it`)

### 5. SSL Certificate
- [ ] Install Certbot: `sudo apt-get install certbot python3-certbot-apache`
- [ ] Generate certificate: `sudo certbot --apache -d riducispese.it -d www.riducispese.it`
- [ ] Verify auto-renewal: `sudo certbot renew --dry-run`
- [ ] Enable HTTPS redirect in `.htaccess` (uncomment lines)

### 6. File Permissions
```bash
# Set correct ownership
sudo chown -R www-data:www-data /var/www/riducispese.it

# Set directory permissions
find /var/www/riducispese.it -type d -exec chmod 755 {} \;

# Set file permissions
find /var/www/riducispese.it -type f -exec chmod 644 {} \;

# Protect config files
chmod 600 /var/www/riducispese.it/api/config.php
```

### 7. Apache/Nginx Configuration

**Apache**:
- [ ] Enable mod_rewrite: `sudo a2enmod rewrite`
- [ ] Enable mod_headers: `sudo a2enmod headers`
- [ ] Restart Apache: `sudo systemctl restart apache2`

**Nginx**:
- [ ] Copy `nginx.conf.example` to `/etc/nginx/sites-available/riducispese.it`
- [ ] Create symlink: `sudo ln -s /etc/nginx/sites-available/riducispese.it /etc/nginx/sites-enabled/`
- [ ] Test config: `sudo nginx -t`
- [ ] Reload Nginx: `sudo systemctl reload nginx`

### 8. PHP Configuration
- [ ] Verify PHP version: `php -v` (should be 8.1+)
- [ ] Check required extensions:
  - [ ] `php -m | grep mysqli`
  - [ ] `php -m | grep json`
  - [ ] `php -m | grep curl`
- [ ] Set production php.ini settings:
  - [ ] `display_errors = Off`
  - [ ] `log_errors = On`
  - [ ] `error_log = /var/log/php/error.log`

## Deploy

### 9. Upload Files
```bash
# Using Git (recommended)
cd /var/www/riducispese.it
git clone https://github.com/yourusername/ETP.git .

# Or using rsync
rsync -avz --exclude='.git' /local/path/ETP/ user@server:/var/www/riducispese.it/
```

### 10. Test Deployment
- [ ] Visit `https://riducispese.it` - should see landing page
- [ ] Test registration: Create a test account
- [ ] Test login: Login with test account
- [ ] Test transaction creation
- [ ] Test transaction filters
- [ ] Test export CSV
- [ ] Test Stripe checkout flow (use test mode first!)

### 11. API Health Check
```bash
curl https://riducispese.it/api/health
# Should return: {"success":true,"status":"ok",...}
```

## Post-Deploy

### 12. Monitoring Setup
- [ ] Setup error logging: tail -f /var/log/php/error.log
- [ ] Setup uptime monitoring (UptimeRobot, Pingdom)
- [ ] Setup transaction monitoring in Stripe
- [ ] Configure Google Analytics (optional)

### 13. Backup Setup
```bash
# Database backup script
cat > /root/backup_riducispese.sh << 'EOF'
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u geniusmile -p'dI20mgnkINkQ4iRBOoQHl0gh' geniusmile_production > /backup/riducispese_$DATE.sql
# Keep only last 30 days
find /backup -name "riducispese_*.sql" -mtime +30 -delete
EOF

chmod +x /root/backup_riducispese.sh

# Add to crontab (daily at 2am)
(crontab -l 2>/dev/null; echo "0 2 * * * /root/backup_riducispese.sh") | crontab -
```

### 14. Security Hardening
- [ ] Disable directory listing (already in .htaccess)
- [ ] Hide PHP version: `expose_php = Off` in php.ini
- [ ] Setup fail2ban for brute force protection
- [ ] Configure firewall (UFW):
  ```bash
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  sudo ufw allow 22/tcp
  sudo ufw enable
  ```

### 15. Performance Optimization
- [ ] Enable Gzip compression (already in .htaccess)
- [ ] Configure browser caching (already in .htaccess)
- [ ] Enable OPcache in php.ini:
  ```ini
  opcache.enable=1
  opcache.memory_consumption=128
  opcache.max_accelerated_files=10000
  ```
- [ ] Consider CDN for static assets (optional)

### 16. Final Tests
- [ ] Test from different browsers (Chrome, Firefox, Safari)
- [ ] Test on mobile devices (iOS, Android)
- [ ] Test payment flow with real card (small amount)
- [ ] Test webhook delivery from Stripe
- [ ] Test email notifications (if implemented)
- [ ] Load testing with Apache Bench:
  ```bash
  ab -n 100 -c 10 https://riducispese.it/
  ```

## Rollback Plan

If something goes wrong:
1. Revert code: `git reset --hard <previous-commit>`
2. Restore database: `mysql -u geniusmile -p geniusmile_production < /backup/riducispese_YYYYMMDD.sql`
3. Clear cache: `sudo systemctl restart apache2` or `sudo systemctl restart nginx`

## Support Contacts

- Server: geniusfast @ 93.186.255.213
- Database: geniusmile_production
- Domain: riducispese.it
- Email: support@riducispese.it

---

**Last Updated**: 2025-01-30
