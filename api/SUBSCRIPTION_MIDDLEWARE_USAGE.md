# Subscription Middleware Usage Guide

## Overview

The `SubscriptionMiddleware` class provides methods to check subscription status and enforce access control for premium features.

## Available Methods

### 1. `checkSubscription($userId, $allowFreeTrial = true)`

Checks if a user has an active subscription.

**Parameters:**
- `$userId` (int): User ID to check
- `$allowFreeTrial` (bool): Whether to allow access during free trial period (default: true)

**Returns:**
```php
[
    'has_access' => bool,              // Whether user has access
    'has_active_subscription' => bool, // Whether user has paid subscription
    'is_in_free_trial' => bool,        // Whether user is in free trial
    'subscription' => array|null,      // Subscription details
    'reason' => string|null            // Reason if access denied
]
```

**Example:**
```php
require_once __DIR__ . '/../SubscriptionMiddleware.php';

$status = SubscriptionMiddleware::checkSubscription($userId);
if ($status['has_access']) {
    // User has access
} else {
    // Show upgrade prompt
}
```

### 2. `requireSubscription($userId, $allowFreeTrial = true)`

Enforces that user has active subscription or throws 402 error.

**Parameters:**
- `$userId` (int): User ID to check
- `$allowFreeTrial` (bool): Whether to allow access during free trial period (default: true)

**Returns:** Subscription status array if valid, otherwise sends error response and exits

**Example:**
```php
require_once __DIR__ . '/../SubscriptionMiddleware.php';

// This will exit with 402 error if user doesn't have access
$status = SubscriptionMiddleware::requireSubscription($userId);

// Code here only runs if user has valid subscription
```

### 3. `checkTransactionLimit($userId)`

Checks if user can add more transactions based on free tier limits.

**Returns:**
```php
[
    'can_add' => bool,         // Whether user can add transactions
    'limit_reached' => bool,   // Whether limit is reached
    'current' => int|null,     // Current transaction count (null if paid)
    'max' => int|null,         // Maximum allowed (null if paid)
    'reason' => string         // 'paid_subscription', 'free_trial', 'free_tier', or 'limit_reached'
]
```

**Example:**
```php
$limitInfo = SubscriptionMiddleware::checkTransactionLimit($userId);
if ($limitInfo['can_add']) {
    // Allow adding transaction
} else {
    // Show upgrade prompt: "You've used {$limitInfo['current']} of {$limitInfo['max']} free transactions"
}
```

### 4. `requireTransactionLimit($userId)`

Enforces that user hasn't exceeded transaction limit or throws 402 error.

**Example:**
```php
// In transactions/create.php
require_once __DIR__ . '/../SubscriptionMiddleware.php';

$userId = JWTMiddleware::authenticate();
SubscriptionMiddleware::requireTransactionLimit($userId);

// Code here only runs if user can add transactions
```

## Usage in Endpoints

### Example 1: Protect Premium Feature

```php
<?php
// api/reports/generate.php
require_once __DIR__ . '/../JWTMiddleware.php';
require_once __DIR__ . '/../SubscriptionMiddleware.php';

$userId = JWTMiddleware::authenticate();

// Require paid subscription (no free trial)
SubscriptionMiddleware::requireSubscription($userId, false);

// Generate premium report
// ...
```

### Example 2: Check Transaction Limit Before Creating

```php
<?php
// api/transactions/create.php
require_once __DIR__ . '/../JWTMiddleware.php';
require_once __DIR__ . '/../SubscriptionMiddleware.php';

$userId = JWTMiddleware::authenticate();

// Check if user can add more transactions
SubscriptionMiddleware::requireTransactionLimit($userId);

// Create transaction
// ...
```

### Example 3: Show Different UI Based on Subscription

```php
<?php
// api/user/dashboard.php
$status = SubscriptionMiddleware::checkSubscription($userId);

$response = [
    'data' => $dashboardData,
    'subscription_status' => $status,
    'show_upgrade_prompt' => !$status['has_active_subscription']
];

Response::success($response);
```

## Subscription Status Values

The subscription `status` field in the database can have these values:

- `active` - Subscription is active and paid
- `trialing` - In trial period (if Stripe trial enabled)
- `past_due` - Payment failed, but we give 7 days grace period
- `canceled` - Subscription was canceled
- `incomplete` - Initial payment failed
- `incomplete_expired` - Initial payment failed and expired
- `unpaid` - Multiple payment failures

## Access Logic

**User has access if:**
1. Subscription status is `active`, `trialing`, or `past_due` AND period hasn't expired
2. OR user is in free trial period (60 days from account creation)

**For transaction creation specifically:**
- Paid users: Unlimited transactions
- Free trial users: Up to 50 transactions
- Free tier users (after trial): Up to 50 transactions total

## Configuration

These constants in `config.php` control the subscription behavior:

```php
define('FREE_MAX_TRANSACTIONS', 50);  // Free tier transaction limit
define('FREE_TRIAL_DAYS', 60);        // Free trial duration in days
```

## Error Responses

When access is denied, the middleware returns:

```json
{
    "success": false,
    "error": "Active subscription required. Please upgrade your plan.",
    "code": 402
}
```

For transaction limits:

```json
{
    "success": false,
    "error": "Transaction limit reached. You have used 50 of 50 free transactions. Please upgrade to add more.",
    "code": 402
}
```

## Testing

To test subscription middleware:

1. Create a test user
2. Add subscription record with different statuses
3. Test API endpoints with various subscription states
4. Verify correct access control and error messages

```sql
-- Grant active subscription to test user
INSERT INTO afts5498_etp_subscriptions (user_id, plan, status, current_period_end)
VALUES (1, 'monthly', 'active', DATE_ADD(NOW(), INTERVAL 30 DAY));

-- Test past_due status
UPDATE afts5498_etp_subscriptions SET status = 'past_due' WHERE user_id = 1;

-- Test canceled status
UPDATE afts5498_etp_subscriptions SET status = 'canceled' WHERE user_id = 1;
```
