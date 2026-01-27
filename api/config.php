<?php
/**
 * ETP Configuration File
 * Contains database credentials, JWT settings, and API keys
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'geniusmile_production');
define('DB_USER', 'geniusmile');
define('DB_PASS', 'dI20mgnkINkQ4iRBOoQHl0gh');
define('DB_CHARSET', 'utf8mb4');
define('DB_TABLE_PREFIX', 'afts5498_etp_');

// JWT Configuration
define('JWT_SECRET', 'etp_jwt_secret_2025_xK9mP3nQ7rT2wY5v_unique_for_etp');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY_DAYS', 30);

// Stripe Configuration (will be populated later)
define('STRIPE_SECRET_KEY_TEST', ''); // To be set in Story #10
define('STRIPE_SECRET_KEY_LIVE', ''); // To be set in Story #10
define('STRIPE_WEBHOOK_SECRET', ''); // To be set in Story #12
define('STRIPE_PRICE_ID_MONTHLY', ''); // To be set in Story #10
define('STRIPE_PRICE_ID_YEARLY', ''); // To be set in Story #10

// Environment
define('ENVIRONMENT', 'development'); // change to 'production' when live

// Use test keys in development
define('STRIPE_SECRET_KEY', ENVIRONMENT === 'production' ? STRIPE_SECRET_KEY_LIVE : STRIPE_SECRET_KEY_TEST);

// Application Settings
define('APP_NAME', 'Expense Tracker Pro');
define('APP_URL', 'https://etp.geniusmile.com');
define('API_URL', 'https://etp.geniusmile.com/api');

// Paywall Limits
define('FREE_MAX_TRANSACTIONS', 50);
define('FREE_TRIAL_DAYS', 60);

// Rate Limiting (for future use)
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW_MINUTES', 1);

// Timezone
date_default_timezone_set('Europe/Rome');

// Error Reporting (disable in production)
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}
