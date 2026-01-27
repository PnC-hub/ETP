<?php
/**
 * User Registration Endpoint
 * POST /api/auth/register
 * Creates a new user account with password hashing
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
if (!isset($input['email']) || !isset($input['password']) || !isset($input['name'])) {
    Response::error('Missing required fields: email, password, name', 400);
    exit;
}

$email = trim($input['email']);
$password = $input['password'];
$name = trim($input['name']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
    exit;
}

// Validate email length
if (strlen($email) > 255) {
    Response::error('Email is too long (max 255 characters)', 400);
    exit;
}

// Validate name
if (empty($name)) {
    Response::error('Name cannot be empty', 400);
    exit;
}

if (strlen($name) > 255) {
    Response::error('Name is too long (max 255 characters)', 400);
    exit;
}

// Validate password strength
if (strlen($password) < 8) {
    Response::error('Password must be at least 8 characters long', 400);
    exit;
}

if (strlen($password) > 255) {
    Response::error('Password is too long (max 255 characters)', 400);
    exit;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if email already exists
    $checkStmt = $conn->prepare("
        SELECT id FROM " . DB_TABLE_PREFIX . "users
        WHERE email = :email
    ");
    $checkStmt->execute(['email' => $email]);

    if ($checkStmt->fetch()) {
        Response::error('Email already registered', 409);
        exit;
    }

    // Hash password using bcrypt
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Insert new user
    $insertStmt = $conn->prepare("
        INSERT INTO " . DB_TABLE_PREFIX . "users
        (email, password_hash, name, created_at, updated_at)
        VALUES (:email, :password_hash, :name, NOW(), NOW())
    ");

    $insertStmt->execute([
        'email' => $email,
        'password_hash' => $passwordHash,
        'name' => $name
    ]);

    $userId = $conn->lastInsertId();

    // Generate JWT token
    $token = JWTMiddleware::generateToken($userId, $email);

    // Return success with user data and token
    Response::success([
        'user' => [
            'id' => (int)$userId,
            'email' => $email,
            'name' => $name
        ],
        'token' => $token,
        'message' => 'Registration successful'
    ], 201);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    Response::error('Database error during registration', 500);

} catch (Exception $e) {
    error_log("Registration error: " . $e->getMessage());
    Response::error('Server error during registration', 500);
}
