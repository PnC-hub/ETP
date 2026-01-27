<?php
/**
 * User Login Endpoint
 * POST /api/auth/login
 * Authenticates user and returns JWT token
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';
require_once __DIR__ . '/../JWTMiddleware.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate required fields
if (!isset($input['email']) || !isset($input['password'])) {
    Response::error('Missing required fields: email, password', 400);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Fetch user by email
    $stmt = $conn->prepare("
        SELECT id, email, password_hash, name
        FROM " . DB_TABLE_PREFIX . "users
        WHERE email = :email
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if user exists
    if (!$user) {
        Response::error('Invalid email or password', 401);
        exit;
    }

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        Response::error('Invalid email or password', 401);
        exit;
    }

    // Generate JWT token
    $token = JWTMiddleware::generateToken($user['id'], $user['email']);

    // Return success with user data and token
    Response::success([
        'user' => [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name']
        ],
        'token' => $token,
        'message' => 'Login successful'
    ], 200);

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    Response::error('Database error during login', 500);

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    Response::error('Server error during login', 500);
}
