<?php
/**
 * Subscription Middleware
 * Validates that user has active subscription to access protected endpoints
 */

class SubscriptionMiddleware
{
    /**
     * Check if user has an active subscription
     *
     * @param int $userId User ID to check
     * @param bool $allowFreeTrial Whether to allow access during free trial
     * @return array Returns subscription status info
     * @throws Exception If subscription check fails
     */
    public static function checkSubscription($userId, $allowFreeTrial = true)
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Get user creation date for free trial calculation
        $stmt = $conn->prepare("
            SELECT created_at
            FROM " . DB_TABLE_PREFIX . "users
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception('User not found');
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

        if ($subscription) {
            $status = $subscription['status'];
            $periodEnd = $subscription['current_period_end'];

            // Check if subscription is active
            $isActiveStatus = in_array($status, ['active', 'trialing', 'past_due']);

            // Check if period hasn't expired
            $isNotExpired = true;
            if ($periodEnd) {
                $periodEndTime = strtotime($periodEnd);
                $currentTime = time();
                $isNotExpired = $periodEndTime > $currentTime;
            }

            // For past_due, still allow access (grace period)
            if ($status === 'past_due') {
                $hasActiveSubscription = true;
            } else {
                $hasActiveSubscription = $isActiveStatus && $isNotExpired;
            }
        }

        // Check free trial status
        $accountCreatedAt = strtotime($user['created_at']);
        $freeTrialEndDate = $accountCreatedAt + (FREE_TRIAL_DAYS * 24 * 60 * 60);
        $isInFreeTrial = time() < $freeTrialEndDate;

        // Determine final access
        $hasAccess = $hasActiveSubscription || ($allowFreeTrial && $isInFreeTrial);

        return [
            'has_access' => $hasAccess,
            'has_active_subscription' => $hasActiveSubscription,
            'is_in_free_trial' => $isInFreeTrial,
            'subscription' => $subscription,
            'reason' => !$hasAccess ? 'subscription_required' : null
        ];
    }

    /**
     * Require active subscription or throw error
     *
     * @param int $userId User ID to check
     * @param bool $allowFreeTrial Whether to allow access during free trial
     * @return array Subscription status info if valid
     */
    public static function requireSubscription($userId, $allowFreeTrial = true)
    {
        $status = self::checkSubscription($userId, $allowFreeTrial);

        if (!$status['has_access']) {
            Response::error('Active subscription required. Please upgrade your plan.', 402);
        }

        return $status;
    }

    /**
     * Check transaction limit for free tier users
     *
     * @param int $userId User ID to check
     * @return array Returns limit info with can_add flag
     * @throws Exception If limit check fails
     */
    public static function checkTransactionLimit($userId)
    {
        $db = Database::getInstance();
        $conn = $db->getConnection();

        // Check if user has active subscription
        $subscriptionStatus = self::checkSubscription($userId, true);

        // If user has paid subscription, no limits
        if ($subscriptionStatus['has_active_subscription']) {
            return [
                'can_add' => true,
                'limit_reached' => false,
                'current' => null,
                'max' => null,
                'reason' => 'paid_subscription'
            ];
        }

        // Get transaction count
        $stmt = $conn->prepare("
            SELECT COUNT(*) as transaction_count
            FROM " . DB_TABLE_PREFIX . "transactions
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $transactionCount = (int) $result['transaction_count'];

        // If in free trial, allow up to limit
        if ($subscriptionStatus['is_in_free_trial']) {
            $canAdd = $transactionCount < FREE_MAX_TRANSACTIONS;
            return [
                'can_add' => $canAdd,
                'limit_reached' => !$canAdd,
                'current' => $transactionCount,
                'max' => FREE_MAX_TRANSACTIONS,
                'reason' => $canAdd ? 'free_trial' : 'limit_reached'
            ];
        }

        // Free tier without trial - check limit
        $canAdd = $transactionCount < FREE_MAX_TRANSACTIONS;

        return [
            'can_add' => $canAdd,
            'limit_reached' => !$canAdd,
            'current' => $transactionCount,
            'max' => FREE_MAX_TRANSACTIONS,
            'reason' => $canAdd ? 'free_tier' : 'limit_reached'
        ];
    }

    /**
     * Require transaction limit not reached or throw error
     *
     * @param int $userId User ID to check
     * @return array Limit info if valid
     */
    public static function requireTransactionLimit($userId)
    {
        $limitInfo = self::checkTransactionLimit($userId);

        if (!$limitInfo['can_add']) {
            Response::error(
                'Transaction limit reached. You have used ' . $limitInfo['current'] . ' of ' . $limitInfo['max'] . ' free transactions. Please upgrade to add more.',
                402
            );
        }

        return $limitInfo;
    }
}
