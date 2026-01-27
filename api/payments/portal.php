<?php
/**
 * Create Stripe Customer Portal Session
 * POST /api/payments/portal
 * Creates a Stripe Customer Portal session for subscription management
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';
require_once __DIR__ . '/../JWTMiddleware.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Verify JWT token
$user = JWTMiddleware::verifyToken();
if (!$user) {
    Response::error('Unauthorized', 401);
}

// Get user's Stripe customer ID
$db = Database::getInstance();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT stripe_customer_id FROM " . DB_TABLE_PREFIX . "subscriptions WHERE user_id = ?");
$stmt->execute([$user['id']]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$subscription || empty($subscription['stripe_customer_id'])) {
    Response::error('No active subscription found', 404);
}

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Create Customer Portal Session
    $portalSession = \Stripe\BillingPortal\Session::create([
        'customer' => $subscription['stripe_customer_id'],
        'return_url' => APP_URL,
    ]);

    Response::success([
        'url' => $portalSession->url
    ], 'Portal session created successfully');

} catch (\Stripe\Exception\ApiErrorException $e) {
    Response::error('Stripe error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error('Failed to create portal session: ' . $e->getMessage(), 500);
}
