<?php
/**
 * Create Stripe Checkout Session
 * POST /api/payments/create-checkout
 * Creates a Stripe Checkout Session for subscription purchase
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

// Get request body
$input = json_decode(file_get_contents('php://input'), true);

// Validate plan parameter
$plan = $input['plan'] ?? 'monthly';
if (!in_array($plan, ['monthly', 'yearly'])) {
    Response::error('Invalid plan. Must be "monthly" or "yearly"', 400);
}

// Get the correct price ID
$priceId = $plan === 'monthly' ? STRIPE_PRICE_ID_MONTHLY : STRIPE_PRICE_ID_YEARLY;

if (empty($priceId) || $priceId === 'price_xxxxxxxxxxxxx') {
    Response::error('Stripe price ID not configured. Please set up Stripe products and update config.php', 500);
}

// Initialize Stripe
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

try {
    // Create or retrieve Stripe customer
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if user already has a Stripe customer ID
    $stmt = $conn->prepare("SELECT stripe_customer_id FROM " . DB_TABLE_PREFIX . "subscriptions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $subscription = $stmt->fetch(PDO::FETCH_ASSOC);

    $customerId = null;
    if ($subscription && !empty($subscription['stripe_customer_id'])) {
        $customerId = $subscription['stripe_customer_id'];
    } else {
        // Get user email from users table
        $stmt = $conn->prepare("SELECT email, name FROM " . DB_TABLE_PREFIX . "users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$userData) {
            Response::error('User not found', 404);
        }

        // Create Stripe customer
        $customer = \Stripe\Customer::create([
            'email' => $userData['email'],
            'name' => $userData['name'],
            'metadata' => [
                'user_id' => $user['id']
            ]
        ]);
        $customerId = $customer->id;
    }

    // Create Checkout Session
    $checkoutSession = \Stripe\Checkout\Session::create([
        'customer' => $customerId,
        'mode' => 'subscription',
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price' => $priceId,
            'quantity' => 1,
        ]],
        'success_url' => APP_URL . '?session_id={CHECKOUT_SESSION_ID}&success=true',
        'cancel_url' => APP_URL . '?canceled=true',
        'metadata' => [
            'user_id' => $user['id'],
            'plan' => $plan
        ],
        'subscription_data' => [
            'metadata' => [
                'user_id' => $user['id']
            ]
        ]
    ]);

    Response::success([
        'sessionId' => $checkoutSession->id,
        'url' => $checkoutSession->url
    ], 'Checkout session created successfully');

} catch (\Stripe\Exception\ApiErrorException $e) {
    Response::error('Stripe error: ' . $e->getMessage(), 500);
} catch (Exception $e) {
    Response::error('Failed to create checkout session: ' . $e->getMessage(), 500);
}
