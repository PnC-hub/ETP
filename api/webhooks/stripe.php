<?php
/**
 * Stripe Webhook Endpoint
 * POST /api/payments/webhook
 * Handles Stripe webhook events (checkout.session.completed, subscription updates, etc.)
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';

header('Content-Type: application/json');

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get the raw POST body
$payload = @file_get_contents('php://input');
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

$event = null;

// Verify webhook signature if webhook secret is configured
if (!empty(STRIPE_WEBHOOK_SECRET) && STRIPE_WEBHOOK_SECRET !== '') {
    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload,
            $sigHeader,
            STRIPE_WEBHOOK_SECRET
        );
    } catch (\UnexpectedValueException $e) {
        // Invalid payload
        error_log('Webhook error: Invalid payload');
        http_response_code(400);
        exit;
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // Invalid signature
        error_log('Webhook error: Invalid signature');
        http_response_code(400);
        exit;
    }
} else {
    // No webhook secret configured - parse event directly (NOT RECOMMENDED FOR PRODUCTION)
    try {
        $event = json_decode($payload, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            exit;
        }
    } catch (Exception $e) {
        http_response_code(400);
        exit;
    }
}

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Handle the event
try {
    switch ($event['type']) {
        case 'checkout.session.completed':
            $session = $event['data']['object'];

            // Get user_id from metadata
            $userId = $session['metadata']['user_id'] ?? null;
            if (!$userId) {
                error_log('Webhook error: No user_id in session metadata');
                break;
            }

            // Get subscription details
            $subscriptionId = $session['subscription'] ?? null;
            $customerId = $session['customer'] ?? null;

            if ($subscriptionId && $customerId) {
                // Retrieve subscription to get details
                $subscription = \Stripe\Subscription::retrieve($subscriptionId);

                $plan = $session['metadata']['plan'] ?? 'monthly';
                $status = $subscription->status;
                $currentPeriodEnd = date('Y-m-d H:i:s', $subscription->current_period_end);

                // Check if subscription record exists
                $stmt = $conn->prepare("SELECT id FROM " . DB_TABLE_PREFIX . "subscriptions WHERE user_id = ?");
                $stmt->execute([$userId]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    // Update existing subscription
                    $stmt = $conn->prepare("
                        UPDATE " . DB_TABLE_PREFIX . "subscriptions
                        SET stripe_customer_id = ?,
                            stripe_subscription_id = ?,
                            plan = ?,
                            status = ?,
                            current_period_end = ?,
                            updated_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([
                        $customerId,
                        $subscriptionId,
                        $plan,
                        $status,
                        $currentPeriodEnd,
                        $userId
                    ]);
                } else {
                    // Create new subscription record
                    $stmt = $conn->prepare("
                        INSERT INTO " . DB_TABLE_PREFIX . "subscriptions
                        (user_id, stripe_customer_id, stripe_subscription_id, plan, status, current_period_end, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    $stmt->execute([
                        $userId,
                        $customerId,
                        $subscriptionId,
                        $plan,
                        $status,
                        $currentPeriodEnd
                    ]);
                }

                error_log("Subscription created/updated for user {$userId}");
            }
            break;

        case 'customer.subscription.updated':
            $subscription = $event['data']['object'];

            $subscriptionId = $subscription['id'];
            $status = $subscription['status'];
            $currentPeriodEnd = date('Y-m-d H:i:s', $subscription['current_period_end']);

            // Update subscription status
            $stmt = $conn->prepare("
                UPDATE " . DB_TABLE_PREFIX . "subscriptions
                SET status = ?,
                    current_period_end = ?,
                    updated_at = NOW()
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([
                $status,
                $currentPeriodEnd,
                $subscriptionId
            ]);

            error_log("Subscription updated: {$subscriptionId} - Status: {$status}");
            break;

        case 'customer.subscription.deleted':
            $subscription = $event['data']['object'];

            $subscriptionId = $subscription['id'];

            // Mark subscription as canceled
            $stmt = $conn->prepare("
                UPDATE " . DB_TABLE_PREFIX . "subscriptions
                SET status = 'canceled',
                    updated_at = NOW()
                WHERE stripe_subscription_id = ?
            ");
            $stmt->execute([$subscriptionId]);

            error_log("Subscription canceled: {$subscriptionId}");
            break;

        case 'invoice.payment_succeeded':
            $invoice = $event['data']['object'];

            $subscriptionId = $invoice['subscription'] ?? null;
            if ($subscriptionId) {
                // Update last payment date
                $stmt = $conn->prepare("
                    UPDATE " . DB_TABLE_PREFIX . "subscriptions
                    SET updated_at = NOW()
                    WHERE stripe_subscription_id = ?
                ");
                $stmt->execute([$subscriptionId]);

                error_log("Payment succeeded for subscription: {$subscriptionId}");
            }
            break;

        case 'invoice.payment_failed':
            $invoice = $event['data']['object'];

            $subscriptionId = $invoice['subscription'] ?? null;
            if ($subscriptionId) {
                // Update status to past_due
                $stmt = $conn->prepare("
                    UPDATE " . DB_TABLE_PREFIX . "subscriptions
                    SET status = 'past_due',
                        updated_at = NOW()
                    WHERE stripe_subscription_id = ?
                ");
                $stmt->execute([$subscriptionId]);

                error_log("Payment failed for subscription: {$subscriptionId}");
            }
            break;

        default:
            // Unhandled event type
            error_log("Unhandled webhook event type: {$event['type']}");
    }

    // Return 200 to acknowledge receipt
    http_response_code(200);
    echo json_encode(['received' => true]);

} catch (Exception $e) {
    error_log('Webhook processing error: ' . $e->getMessage());
    http_response_code(500);
    exit;
}
