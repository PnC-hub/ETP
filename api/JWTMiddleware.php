<?php
/**
 * JWT Middleware
 * Validates JWT tokens for protected routes
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Response.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWTMiddleware {

    /**
     * Verify JWT token from Authorization header
     * Returns user data if valid, or sends error response and exits
     *
     * @return array User data from token (user_id, email)
     */
    public static function verify() {
        // Check if Authorization header exists
        $headers = getallheaders();

        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            Response::error('Missing authorization token', 401);
            exit;
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'];

        // Extract token from "Bearer <token>" format
        $token = null;
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            Response::error('Invalid authorization header format. Use: Bearer <token>', 401);
            exit;
        }

        try {
            // Decode and verify the token
            $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));

            // Convert stdClass to array
            $userData = [
                'user_id' => $decoded->user_id,
                'email' => $decoded->email
            ];

            return $userData;

        } catch (ExpiredException $e) {
            Response::error('Token has expired', 401);
            exit;

        } catch (SignatureInvalidException $e) {
            Response::error('Invalid token signature', 401);
            exit;

        } catch (Exception $e) {
            Response::error('Invalid token: ' . $e->getMessage(), 401);
            exit;
        }
    }

    /**
     * Generate a new JWT token for a user
     *
     * @param int $userId User ID
     * @param string $email User email
     * @return string JWT token
     */
    public static function generateToken($userId, $email) {
        $issuedAt = time();
        $expirationTime = $issuedAt + (JWT_EXPIRY_DAYS * 24 * 60 * 60);

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'user_id' => $userId,
            'email' => $email
        ];

        return JWT::encode($payload, JWT_SECRET, JWT_ALGORITHM);
    }

    /**
     * Verify token and return user data, or null if invalid
     * Non-blocking version for optional authentication
     *
     * @return array|null User data or null
     */
    public static function verifyOptional() {
        $headers = getallheaders();

        if (!isset($headers['Authorization']) && !isset($headers['authorization'])) {
            return null;
        }

        $authHeader = $headers['Authorization'] ?? $headers['authorization'];

        $token = null;
        if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
            $token = $matches[1];
        } else {
            return null;
        }

        try {
            $decoded = JWT::decode($token, new Key(JWT_SECRET, JWT_ALGORITHM));

            return [
                'user_id' => $decoded->user_id,
                'email' => $decoded->email
            ];

        } catch (Exception $e) {
            return null;
        }
    }
}
