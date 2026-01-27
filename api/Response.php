<?php
/**
 * Response Class
 * Standardized JSON response handler for API
 */

class Response {
    /**
     * Send success response
     * @param mixed $data Response data
     * @param int $code HTTP status code (default: 200)
     * @param string $message Optional success message
     */
    public static function success($data = [], $code = 200, $message = '') {
        http_response_code($code);

        $response = [
            'success' => true,
            'data' => $data
        ];

        if (!empty($message)) {
            $response['message'] = $message;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send error response
     * @param string $message Error message
     * @param int $code HTTP status code (default: 400)
     * @param array $details Optional error details
     */
    public static function error($message, $code = 400, $details = []) {
        http_response_code($code);

        $response = [
            'success' => false,
            'error' => [
                'message' => $message,
                'code' => $code
            ]
        ];

        if (!empty($details)) {
            $response['error']['details'] = $details;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Send validation error response
     * @param array $errors Array of validation errors
     */
    public static function validationError($errors) {
        self::error('Validation failed', 422, $errors);
    }

    /**
     * Send unauthorized response
     * @param string $message Optional message
     */
    public static function unauthorized($message = 'Unauthorized') {
        self::error($message, 401);
    }

    /**
     * Send forbidden response
     * @param string $message Optional message
     * @param bool $upgradeRequired Flag to indicate upgrade needed
     */
    public static function forbidden($message = 'Forbidden', $upgradeRequired = false) {
        $details = [];
        if ($upgradeRequired) {
            $details['upgrade_required'] = true;
        }
        self::error($message, 403, $details);
    }

    /**
     * Send not found response
     * @param string $message Optional message
     */
    public static function notFound($message = 'Resource not found') {
        self::error($message, 404);
    }

    /**
     * Send created response (for POST requests)
     * @param mixed $data Created resource data
     * @param string $message Optional message
     */
    public static function created($data = [], $message = 'Resource created successfully') {
        self::success($data, 201, $message);
    }

    /**
     * Send no content response (for DELETE requests)
     */
    public static function noContent() {
        http_response_code(204);
        exit;
    }
}
