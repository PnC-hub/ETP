<?php
/**
 * RiduciSpese Local Testing Configuration
 * Override database settings for local testing
 */

// Database Configuration - LOCAL
define('DB_HOST', 'localhost');
define('DB_NAME', 'etp_local_test');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('DB_TABLE_PREFIX', 'etp_');

// JWT Configuration
define('JWT_SECRET', 'etp_jwt_secret_local_test_2025');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRY_DAYS', 30);

// Stripe Configuration - TEST MODE
define('STRIPE_SECRET_KEY_TEST', 'sk_test_51xxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
define('STRIPE_SECRET_KEY_LIVE', '');
define('STRIPE_WEBHOOK_SECRET', '');
define('STRIPE_PRICE_ID_MONTHLY', 'price_xxxxxxxxxxxxx');
define('STRIPE_PRICE_ID_YEARLY', 'price_xxxxxxxxxxxxx');

// Environment
define('ENVIRONMENT', 'development');

// Use test keys in development
define('STRIPE_SECRET_KEY', ENVIRONMENT === 'production' ? STRIPE_SECRET_KEY_LIVE : STRIPE_SECRET_KEY_TEST);

// Application Settings
define('APP_NAME', 'RiduciSpese');
define('APP_URL', 'http://localhost:8000');
define('API_URL', 'http://localhost:8000/api');

// Paywall Limits
define('FREE_MAX_TRANSACTIONS', 50);
define('FREE_TRIAL_DAYS', 60);

// Rate Limiting
define('RATE_LIMIT_MAX_ATTEMPTS', 5);
define('RATE_LIMIT_WINDOW_MINUTES', 1);

// Timezone
date_default_timezone_set('Europe/Rome');

// Error Reporting - Always enabled for local testing
error_reporting(E_ALL);
ini_set('display_errors', 1);
