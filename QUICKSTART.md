# RiduciSpese - Quick Start Guide

Get up and running in 5 minutes.

---

## 1. Prerequisites

- PHP 8.1+ installed
- MySQL 8.0+ installed
- Git installed
- Terminal access

---

## 2. Clone & Setup (2 minutes)

```bash
# Clone the repository
git clone https://github.com/yourusername/ETP.git
cd ETP

# Create database
mysql -u root -p << EOF
CREATE DATABASE riducispese_local;
USE riducispese_local;
SOURCE migrations/001_initial_schema.sql;
EOF
```

---

## 3. Configure (1 minute)

Edit `api/config.local.php` or create it:

```php
<?php
// Local configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'riducispese_local');
define('DB_USER', 'root');
define('DB_PASS', 'your_password');
define('DB_TABLE_PREFIX', 'etp_');

define('APP_NAME', 'RiduciSpese');
define('APP_URL', 'http://localhost:8000');
define('API_URL', 'http://localhost:8000/api');

define('ENVIRONMENT', 'development');
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

---

## 4. Run (1 minute)

```bash
# Start PHP built-in server
php -S localhost:8000
```

Open browser: **http://localhost:8000**

---

## 5. Test (1 minute)

1. Click **"Registrati"**
2. Create account:
   - Nome: Test User
   - Email: test@example.com
   - Password: test123
3. You should be redirected to Dashboard
4. Add a test transaction
5. Verify it appears in the list

---

## Done! 🎉

You now have RiduciSpese running locally.

---

## Common Issues

### Database Connection Failed
```bash
# Check MySQL is running
mysql --version
sudo systemctl status mysql  # Linux
brew services list | grep mysql  # Mac

# Test connection
mysql -u root -p
```

### Port 8000 Already in Use
```bash
# Use different port
php -S localhost:8080

# Update API_URL in config.local.php
define('APP_URL', 'http://localhost:8080');
```

### API Endpoints Return 404
```bash
# Check .htaccess is being read
# If using Apache, enable mod_rewrite:
sudo a2enmod rewrite
sudo systemctl restart apache2
```

### CORS Errors
```bash
# Make sure api/index.php has CORS headers
# Should be at top of file:
header('Access-Control-Allow-Origin: *');
```

---

## Next Steps

### Development Workflow

1. **Frontend Changes**: Edit `index.html`
   - Refresh browser to see changes
   - No build step needed

2. **API Changes**: Edit files in `api/`
   - PHP scripts reload automatically
   - Check error log: `tail -f /var/log/php/error.log`

3. **Database Changes**: Create migration file
   - Follow pattern in `migrations/001_initial_schema.sql`
   - Apply: `mysql -u root -p riducispese_local < migrations/002_new_migration.sql`

### Recommended Tools

- **VSCode Extensions**:
  - PHP Intelephense
  - MySQL
  - Live Server (optional, but PHP server is fine)

- **Database GUI**:
  - phpMyAdmin
  - MySQL Workbench
  - TablePlus

- **API Testing**:
  - Postman
  - Insomnia
  - curl (command line)

### Testing API Endpoints

```bash
# Health check
curl http://localhost:8000/api/health

# Register user
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"name":"Test","email":"test@test.com","password":"test123"}'

# Login
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@test.com","password":"test123"}'

# Get transactions (replace TOKEN)
curl http://localhost:8000/api/transactions/read \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Debug Mode

Enable detailed error messages:

```php
// In api/config.local.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');
```

### Hot Reload (Optional)

For faster development, use browser extension:
- Chrome: "Live Reload"
- Firefox: "Auto Reload"

Or simple Python server:
```bash
# Terminal 1: PHP API
php -S localhost:8000

# Terminal 2: Watch for changes (optional)
while true; do
  inotifywait -e modify index.html
  # Trigger browser reload
done
```

---

## Project Structure

```
ETP/
├── index.html              # Main SPA file
├── .htaccess              # Apache config
├── nginx.conf.example     # Nginx config template
│
├── api/                   # Backend API
│   ├── index.php         # API router
│   ├── config.php        # Production config
│   ├── config.local.php  # Local config (not in git)
│   ├── Database.php      # DB connection
│   ├── Response.php      # JSON responses
│   ├── JWTMiddleware.php # Auth middleware
│   │
│   ├── auth/             # Authentication
│   │   ├── register.php
│   │   └── login.php
│   │
│   ├── transactions/     # Transaction CRUD
│   │   ├── create.php
│   │   ├── read.php
│   │   ├── update.php
│   │   ├── delete.php
│   │   └── export.php
│   │
│   ├── user/            # User management
│   │   └── status.php
│   │
│   ├── payments/        # Stripe integration
│   │   ├── create-checkout.php
│   │   └── portal.php
│   │
│   └── webhooks/        # External webhooks
│       └── stripe.php
│
├── migrations/          # Database migrations
│   └── 001_initial_schema.sql
│
└── docs/               # Documentation
    ├── README.md
    ├── QUICKSTART.md (this file)
    ├── DEPLOY_CHECKLIST.md
    ├── DESIGN_SYSTEM.md
    └── CHANGES_SUMMARY.md
```

---

## Development Tips

### 1. Use Browser DevTools
- **Console**: Check for JavaScript errors
- **Network**: Inspect API requests/responses
- **Application > Local Storage**: View JWT token

### 2. Enable SQL Query Logging
```php
// In Database.php, add after query execution:
error_log("SQL: " . $sql);
error_log("Params: " . json_encode($params));
```

### 3. Frontend Debugging
```javascript
// Add to index.html <script> section
console.log('Current user:', currentUser);
console.log('All transactions:', allTransactions);
```

### 4. API Response Debugging
```php
// In any API endpoint, add:
error_log(json_encode($data));
```

### 5. Git Workflow
```bash
# Create feature branch
git checkout -b feature/new-feature

# Make changes, test locally
# Commit
git add .
git commit -m "Add new feature"

# Push and create PR
git push origin feature/new-feature
```

---

## Stripe Testing (Optional)

### Get Test API Keys
1. Go to https://dashboard.stripe.com/test/apikeys
2. Copy "Publishable key" and "Secret key"
3. Update in `api/config.local.php`

### Test Cards
- Success: `4242 4242 4242 4242`
- Decline: `4000 0000 0000 0002`
- Exp: Any future date (e.g., 12/34)
- CVC: Any 3 digits (e.g., 123)

### Webhook Testing
```bash
# Install Stripe CLI
# Mac: brew install stripe/stripe-cli/stripe
# Login
stripe login

# Forward webhooks to local
stripe listen --forward-to localhost:8000/api/webhooks/stripe

# Test webhook
stripe trigger payment_intent.succeeded
```

---

## Need Help?

1. **Check logs**:
   ```bash
   # PHP errors
   tail -f /var/log/php/error.log

   # MySQL errors
   tail -f /var/log/mysql/error.log
   ```

2. **Check API health**:
   ```bash
   curl http://localhost:8000/api/health
   ```

3. **Verify database**:
   ```bash
   mysql -u root -p
   USE riducispese_local;
   SHOW TABLES;
   SELECT * FROM etp_users;
   ```

4. **Reset database**:
   ```bash
   mysql -u root -p << EOF
   DROP DATABASE riducispese_local;
   CREATE DATABASE riducispese_local;
   USE riducispese_local;
   SOURCE migrations/001_initial_schema.sql;
   EOF
   ```

---

## Ready for Production?

See **DEPLOY_CHECKLIST.md** for complete deployment guide.

---

**Happy coding!** 🚀
