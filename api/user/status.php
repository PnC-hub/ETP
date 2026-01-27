<?php
/**
 * User Status Endpoint
 * GET /api/user/status
 * Returns user information and subscription status
 * Requires JWT authentication
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';
require_once __DIR__ . '/../JWTMiddleware.php';

header('Content-Type: application/json');

// Only allow GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Authenticate user
$userId = JWTMiddleware::authenticate();
if (!$userId) {
    Response::error('Unauthorized', 401);
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Get user information
    $stmt = $conn->prepare("
        SELECT id, email, name, created_at
        FROM " . DB_TABLE_PREFIX . "users
        WHERE id = ?
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        Response::error('User not found', 404);
    }

    // Get subscription information
    $stmt = $conn->prepare("
        SELECT
            id,
            stripe_customer_id,
            stripe_subscription_id,
            plan,
            status,
            current_period_end,
            created_at,
            updated_at
        FROM " . DB_TABLE_PREFIX . "subscriptions
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    // Determine if user has active subscription
    $hasActiveSubscription = false;
    $subscriptionDetails = null;

    if ($subscription) {
        $status = $subscription['status'];
        $periodEnd = $subscription['current_period_end'];

        // Check if subscription is active
        // Active statuses: 'active', 'trialing'
        // Grace period statuses: 'past_due' (allow access for a few days)
        $isActiveStatus = in_array($status, ['active', 'trialing', 'past_due']);

        // Check if period hasn't expired (for active/trialing)
        $isNotExpired = true;
        if ($periodEnd) {
            $periodEndTime = strtotime($periodEnd);
            $currentTime = time();
            $isNotExpired = $periodEndTime > $currentTime;
        }

        // For past_due, give 7 days grace period
        if ($status === 'past_due') {
            $hasActiveSubscription = true; // Still allow access during grace period
        } else {
            $hasActiveSubscription = $isActiveStatus && $isNotExpired;
        }

        $subscriptionDetails = [
            'plan' => $subscription['plan'],
            'status' => $status,
            'current_period_end' => $periodEnd,
            'stripe_customer_id' => $subscription['stripe_customer_id'],
            'is_active' => $hasActiveSubscription
        ];
    }

    // Get transaction count for free tier limits
    $stmt = $conn->prepare("
        SELECT COUNT(*) as transaction_count
        FROM " . DB_TABLE_PREFIX . "transactions
        WHERE user_id = ?
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $transactionCount = (int) $result['transaction_count'];

    // Check if free trial is still valid (based on account creation date)
    $accountCreatedAt = strtotime($user['created_at']);
    $freeTrialEndDate = $accountCreatedAt + (FREE_TRIAL_DAYS * 24 * 60 * 60);
    $isInFreeTrial = time() < $freeTrialEndDate;
    $daysLeftInTrial = $isInFreeTrial ? ceil(($freeTrialEndDate - time()) / (24 * 60 * 60)) : 0;

    // Determine access level
    $canAddTransactions = true;
    $limitReached = false;
    $limitInfo = null;

    if (!$hasActiveSubscription) {
        // Free tier restrictions
        if ($transactionCount >= FREE_MAX_TRANSACTIONS && !$isInFreeTrial) {
            $canAddTransactions = false;
            $limitReached = true;
            $limitInfo = [
                'type' => 'transaction_limit',
                'message' => 'You have reached the free tier limit of ' . FREE_MAX_TRANSACTIONS . ' transactions. Please upgrade to continue.',
                'current' => $transactionCount,
                'max' => FREE_MAX_TRANSACTIONS
            ];
        } elseif ($isInFreeTrial) {
            $limitInfo = [
                'type' => 'free_trial',
                'message' => 'You are in free trial period',
                'days_left' => $daysLeftInTrial,
                'transactions_used' => $transactionCount,
                'transaction_limit' => FREE_MAX_TRANSACTIONS
            ];
        } else {
            $limitInfo = [
                'type' => 'free_tier',
                'message' => 'You are using the free tier',
                'transactions_used' => $transactionCount,
                'transaction_limit' => FREE_MAX_TRANSACTIONS,
                'remaining' => FREE_MAX_TRANSACTIONS - $transactionCount
            ];
        }
    }

    // Prepare response
    $response = [
        'success' => true,
        'user' => [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'created_at' => $user['created_at']
        ],
        'subscription' => $subscriptionDetails,
        'access' => [
            'has_active_subscription' => $hasActiveSubscription,
            'can_add_transactions' => $canAddTransactions,
            'limit_reached' => $limitReached,
            'is_in_free_trial' => $isInFreeTrial
        ],
        'limits' => $limitInfo
    ];

    Response::success($response);

} catch (PDOException $e) {
    error_log('Database error in user/status: ' . $e->getMessage());
    Response::error('Database error', 500);
} catch (Exception $e) {
    error_log('Error in user/status: ' . $e->getMessage());
    Response::error('Internal server error', 500);
}
