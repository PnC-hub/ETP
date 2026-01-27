# ETP Deployment Guide

## Deployment Steps

### 1. Database Setup
Run the migration script on the production database:

```bash
mysql -h localhost -u geniusmile -p geniusmile_production < migrations/001_initial_schema.sql
```

Password: `dI20mgnkINkQ4iRBOoQHl0gh`

### 2. File Deployment
Files should be deployed to: `/var/www/vhosts/geniusmile.com/etp/`

Required files/directories:
- index.html (frontend)
- api/ (complete directory with all subdirectories)
- migrations/ (optional, for reference)
- vendor/ (PHP dependencies - Composer packages)
- composer.json, composer.lock

### 3. Apache Configuration
Ensure `.htaccess` files are present and mod_rewrite is enabled:

**Root .htaccess** (if needed):
```apache
RewriteEngine On
RewriteBase /

# API routing
RewriteRule ^api/(.*)$ api/index.php [L,QSA]
```

**api/.htaccess** (already in repo):
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [L,QSA]
```

### 4. Composer Dependencies
If vendor/ is not in git, run on server:
```bash
cd /var/www/vhosts/geniusmile.com/etp/
composer install --no-dev
```

### 5. File Permissions
```bash
chown -R www-data:www-data /var/www/vhosts/geniusmile.com/etp/
chmod -R 755 /var/www/vhosts/geniusmile.com/etp/
```

### 6. Test Deployment
```bash
# Test registration
curl -X POST https://etp.geniusmile.com/api/auth/register.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@etp.com","password":"Test1234!","name":"Test User"}'

# Test login
curl -X POST https://etp.geniusmile.com/api/auth/login.php \
  -H "Content-Type: application/json" \
  -d '{"email":"test@etp.com","password":"Test1234!"}'
```

## Auto-Deploy Setup (Optional)

If auto-deploy is configured, it should:
1. Pull from GitHub every minute
2. Run `composer install` if needed
3. Restart services if needed

Check crontab:
```bash
crontab -l | grep etp
```

## Stripe Configuration

Before going live:
1. Replace test keys in `api/config.php` with live keys
2. Create webhook endpoint in Stripe Dashboard
3. Update `STRIPE_WEBHOOK_SECRET` in config
4. Set `ENVIRONMENT` to 'production'

## Troubleshooting

### API returns 404
- Check if `api/` directory exists on server
- Verify `.htaccess` files are present
- Check Apache mod_rewrite is enabled

### Database connection errors
- Verify credentials in `api/config.php`
- Check database user has correct permissions
- Ensure database prefix matches (`afts5498_etp_`)

### JWT errors
- Verify `firebase/php-jwt` is installed via Composer
- Check `vendor/` directory exists

### CORS errors
- Verify CORS headers in `api/index.php`
- Check browser console for specific errors
