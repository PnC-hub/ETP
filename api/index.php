<?php
/**
 * ETP API Router
 * Main entry point for all API requests
 */

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS Headers - Allow frontend to communicate with API
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Set JSON content type for all responses
header('Content-Type: application/json; charset=UTF-8');

// Load core dependencies
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Response.php';

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove /api/ prefix from URI
$uri = str_replace('/api/', '', $uri);
$uri = trim($uri, '/');

// Split URI into segments
$segments = explode('/', $uri);

// Route to appropriate controller
try {
    // Extract resource and action
    $resource = $segments[0] ?? '';
    $action = $segments[1] ?? '';
    $id = $segments[2] ?? null;

    // Route mapping
    switch ($resource) {
        case 'auth':
            // Authentication endpoints
            if ($action === 'register' && $method === 'POST') {
                require_once __DIR__ . '/auth/register.php';
            } elseif ($action === 'login' && $method === 'POST') {
                require_once __DIR__ . '/auth/login.php';
            } else {
                Response::error('Endpoint not found', 404);
            }
            break;

        case 'transactions':
            // Transaction endpoints (protected)
            require_once __DIR__ . '/JWTMiddleware.php';
            $user_id = JWTMiddleware::authenticate();

            if ($method === 'POST' && empty($action)) {
                require_once __DIR__ . '/transactions/create.php';
            } elseif ($method === 'GET' && empty($action)) {
                require_once __DIR__ . '/transactions/read.php';
            } elseif ($method === 'PUT' && !empty($action)) {
                $id = $action;
                require_once __DIR__ . '/transactions/update.php';
            } elseif ($method === 'DELETE' && !empty($action)) {
                $id = $action;
                require_once __DIR__ . '/transactions/delete.php';
            } elseif ($action === 'export' && $method === 'GET') {
                require_once __DIR__ . '/transactions/export.php';
            } elseif ($action === 'import' && $method === 'POST') {
                require_once __DIR__ . '/transactions/import.php';
            } else {
                Response::error('Endpoint not found', 404);
            }
            break;

        case 'user':
            // User endpoints (protected)
            require_once __DIR__ . '/JWTMiddleware.php';
            $user_id = JWTMiddleware::authenticate();

            if ($action === 'status' && $method === 'GET') {
                require_once __DIR__ . '/user/status.php';
            } else {
                Response::error('Endpoint not found', 404);
            }
            break;

        case 'payments':
            // Payment endpoints (protected)
            require_once __DIR__ . '/JWTMiddleware.php';
            $user_id = JWTMiddleware::authenticate();

            if ($action === 'create-checkout' && $method === 'POST') {
                require_once __DIR__ . '/payments/create-checkout.php';
            } elseif ($action === 'portal' && $method === 'GET') {
                require_once __DIR__ . '/payments/portal.php';
            } else {
                Response::error('Endpoint not found', 404);
            }
            break;

        case 'webhooks':
            // Webhook endpoints (public but verified)
            if ($action === 'stripe' && $method === 'POST') {
                require_once __DIR__ . '/webhooks/stripe.php';
            } else {
                Response::error('Endpoint not found', 404);
            }
            break;

        case 'health':
            // Health check endpoint
            Response::success([
                'status' => 'ok',
                'timestamp' => date('Y-m-d H:i:s'),
                'version' => '2.0'
            ]);
            break;

        default:
            Response::error('Resource not found', 404);
            break;
    }

} catch (Exception $e) {
    Response::error($e->getMessage(), 500);
}
