<?php
/**
 * Create Transaction Endpoint
 * Creates a new transaction for the authenticated user
 * POST /api/transactions/create
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';
require_once __DIR__ . '/../JWTMiddleware.php';
require_once __DIR__ . '/../SubscriptionMiddleware.php';
require_once __DIR__ . '/../config.php';

// Set headers
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed. Use POST.', 405);
}

try {
    // Verify JWT token
    $userData = JWTMiddleware::verify();
    $userId = $userData['user_id'];

    // Check transaction limit for free tier users
    SubscriptionMiddleware::requireTransactionLimit($userId);

    // Get and decode JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error('Invalid JSON format', 400);
    }

    // Validate required fields
    $requiredFields = ['type', 'amount', 'date', 'category'];
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || trim($data[$field]) === '') {
            Response::error("Missing required field: {$field}", 400);
        }
    }

    // Extract and validate data
    $type = trim($data['type']);
    $category = trim($data['category']);
    $amount = $data['amount'];
    $description = isset($data['description']) ? trim($data['description']) : null;
    $date = trim($data['date']);

    // Validate type
    if (!in_array($type, ['income', 'expense'])) {
        Response::error('Invalid type. Must be "income" or "expense".', 400);
    }

    // Validate amount
    if (!is_numeric($amount) || $amount <= 0) {
        Response::error('Amount must be a positive number', 400);
    }

    // Ensure amount has max 2 decimal places
    $amount = round($amount, 2);

    // Validate date format (YYYY-MM-DD)
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        Response::error('Invalid date format. Use YYYY-MM-DD.', 400);
    }

    // Validate date is valid
    $dateTime = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
        Response::error('Invalid date. Please provide a valid date.', 400);
    }

    // Validate category is not empty and reasonable length
    if (strlen($category) < 1 || strlen($category) > 50) {
        Response::error('Category must be between 1 and 50 characters', 400);
    }

    // Validate description length (optional)
    if ($description !== null && strlen($description) > 500) {
        Response::error('Description must be less than 500 characters', 400);
    }

    // Insert into database
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $stmt = $conn->prepare("
        INSERT INTO " . DB_TABLE_PREFIX . "transactions
        (user_id, type, category, amount, description, date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $type,
        $category,
        $amount,
        $description,
        $date
    ]);

    $transactionId = $conn->lastInsertId();

    // Fetch the created transaction to return
    $stmt = $conn->prepare("
        SELECT
            id,
            user_id,
            type,
            category,
            amount,
            description,
            date,
            created_at,
            updated_at
        FROM " . DB_TABLE_PREFIX . "transactions
        WHERE id = ?
    ");

    $stmt->execute([$transactionId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format amount for response
    $transaction['amount'] = (float) $transaction['amount'];

    Response::success([
        'message' => 'Transaction created successfully',
        'transaction' => $transaction
    ], 201);

} catch (Exception $e) {
    Response::error('Failed to create transaction: ' . $e->getMessage(), 500);
}
