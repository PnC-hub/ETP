-- Expense Tracker Pro - Initial Database Schema
-- Version: 1.0.0
-- Created: 2026-01-27
-- Prefix: afts5498_etp_

-- =============================================
-- USERS TABLE
-- Stores user accounts with secure authentication
-- =============================================
CREATE TABLE IF NOT EXISTS afts5498_etp_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SUBSCRIPTIONS TABLE
-- Manages Stripe subscription status per user
-- =============================================
CREATE TABLE IF NOT EXISTS afts5498_etp_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    stripe_customer_id VARCHAR(100) DEFAULT NULL,
    stripe_subscription_id VARCHAR(100) DEFAULT NULL,
    plan VARCHAR(50) NOT NULL DEFAULT 'free',
    status VARCHAR(50) NOT NULL DEFAULT 'inactive',
    current_period_end TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES afts5498_etp_users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_stripe_customer (stripe_customer_id),
    UNIQUE KEY unique_stripe_subscription (stripe_subscription_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_stripe_customer (stripe_customer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- TRANSACTIONS TABLE
-- Stores all income and expense transactions
-- =============================================
CREATE TABLE IF NOT EXISTS afts5498_etp_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    category VARCHAR(50) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    description TEXT DEFAULT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES afts5498_etp_users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_date (date),
    INDEX idx_type (type),
    INDEX idx_category (category),
    INDEX idx_user_date (user_id, date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- INITIAL DATA
-- Insert default categories and sample data
-- =============================================

-- Note: Categories are stored in application code, not in DB
-- Common categories include:
-- Income: Salary, Freelance, Investments, Other
-- Expense: Food, Transport, Shopping, Bills, Entertainment, Health, Other

-- =============================================
-- SAMPLE TEST USER (for development only)
-- Password: Test1234!
-- =============================================
INSERT INTO afts5498_etp_users (email, password_hash, name)
VALUES (
    'test@etp.com',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Test User'
) ON DUPLICATE KEY UPDATE email=email;

-- =============================================
-- VERIFICATION QUERIES
-- Use these to verify the schema was created correctly
-- =============================================
-- SELECT COUNT(*) FROM afts5498_etp_users;
-- SELECT COUNT(*) FROM afts5498_etp_subscriptions;
-- SELECT COUNT(*) FROM afts5498_etp_transactions;
-- SHOW CREATE TABLE afts5498_etp_users;
-- SHOW CREATE TABLE afts5498_etp_subscriptions;
-- SHOW CREATE TABLE afts5498_etp_transactions;
