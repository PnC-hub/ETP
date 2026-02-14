<?php
/**
 * RiduciSpese API Router
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
            // These endpoints handle their own JWT verification

            if ($action === 'create' && $method === 'POST') {
                require_once __DIR__ . '/transactions/create.php';
            } elseif ($action === 'read' && $method === 'GET') {
                require_once __DIR__ . '/transactions/read.php';
            } elseif ($action === 'update' && $method === 'PUT') {
                require_once __DIR__ . '/transactions/update.php';
            } elseif ($action === 'delete' && $method === 'DELETE') {
                require_once __DIR__ . '/transactions/delete.php';
            } elseif ($action === 'export' && $method === 'GET') {
                require_once __DIR__ . '/transactions/export.php';
            } elseif ($action === 'import' && $method === 'POST') {
                require_once __DIR__ . '/transactions/import.php';
            } else {
                Response::error('Endpoint not found. Available: create (POST), read (GET), update (PUT), delete (DELETE), export (GET)', 404);
            }
            break;

        case 'user':
            // User endpoints (protected)
            require_once __DIR__ . '/JWTMiddleware.php';
            $userData = JWTMiddleware::verify();

            if ($action === 'status' && $method === 'GET') {
                require_once __DIR__ . '/user/status.php';
            } else {
                Response::error('Endpoint not found', 404);
            }
            break;

        case 'payments':
            // Payment endpoints (protected)
            require_once __DIR__ . '/JWTMiddleware.php';
            $userData = JWTMiddleware::verify();

            if ($action === 'create-checkout' && $method === 'POST') {
                require_once __DIR__ . '/payments/create-checkout.php';
            } elseif ($action === 'portal' && $method === 'POST') {
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

        case 'feedback':
            // Feedback endpoint (accepts both authenticated and anonymous feedback)
            if ($method === 'POST') {
                require_once __DIR__ . '/feedback.php';
            } else {
                Response::error('Method not allowed. Use POST', 405);
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
