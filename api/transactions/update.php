<?php
/**
 * Update Transaction Endpoint
 * Updates an existing transaction for the authenticated user
 * PUT /api/transactions/update
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';
require_once __DIR__ . '/../JWTMiddleware.php';
require_once __DIR__ . '/../SubscriptionMiddleware.php';
require_once __DIR__ . '/../config.php';

// Set headers
header('Content-Type: application/json');

// Only allow PUT requests
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Method not allowed. Use PUT.', 405);
}

try {
    // Verify JWT token
    $userData = JWTMiddleware::verify();
    $userId = $userData['user_id'];

    // Check subscription status (allow free trial)
    SubscriptionMiddleware::checkSubscription($userId, true);

    // Get and decode JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Response::error('Invalid JSON format', 400);
    }

    // Validate transaction ID
    if (!isset($data['id']) || !is_numeric($data['id'])) {
        Response::error('Missing or invalid transaction ID', 400);
    }

    $transactionId = (int) $data['id'];

    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if transaction exists and belongs to user
    $stmt = $conn->prepare("
        SELECT id
        FROM " . DB_TABLE_PREFIX . "transactions
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$transactionId, $userId]);
    $existingTransaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existingTransaction) {
        Response::error('Transaction not found or does not belong to you', 404);
    }

    // Build update query dynamically based on provided fields
    $updateFields = [];
    $params = [];

    // Validate and add type if provided
    if (isset($data['type'])) {
        $type = trim($data['type']);
        if (!in_array($type, ['income', 'expense'])) {
            Response::error('Invalid type. Must be "income" or "expense".', 400);
        }
        $updateFields[] = 'type = ?';
        $params[] = $type;
    }

    // Validate and add category if provided
    if (isset($data['category'])) {
        $category = trim($data['category']);
        if (strlen($category) < 1 || strlen($category) > 50) {
            Response::error('Category must be between 1 and 50 characters', 400);
        }
        $updateFields[] = 'category = ?';
        $params[] = $category;
    }

    // Validate and add amount if provided
    if (isset($data['amount'])) {
        $amount = $data['amount'];
        if (!is_numeric($amount) || $amount <= 0) {
            Response::error('Amount must be a positive number', 400);
        }
        $amount = round($amount, 2);
        $updateFields[] = 'amount = ?';
        $params[] = $amount;
    }

    // Validate and add description if provided
    if (isset($data['description'])) {
        $description = $data['description'] !== null ? trim($data['description']) : null;
        if ($description !== null && strlen($description) > 500) {
            Response::error('Description must be less than 500 characters', 400);
        }
        $updateFields[] = 'description = ?';
        $params[] = $description;
    }

    // Validate and add date if provided
    if (isset($data['date'])) {
        $date = trim($data['date']);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            Response::error('Invalid date format. Use YYYY-MM-DD.', 400);
        }
        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateTime || $dateTime->format('Y-m-d') !== $date) {
            Response::error('Invalid date. Please provide a valid date.', 400);
        }
        $updateFields[] = 'date = ?';
        $params[] = $date;
    }

    // Check if there are fields to update
    if (empty($updateFields)) {
        Response::error('No fields to update. Provide at least one field (type, category, amount, description, date).', 400);
    }

    // Add transaction ID and user ID to params
    $params[] = $transactionId;
    $params[] = $userId;

    // Build and execute update query
    $updateQuery = "
        UPDATE " . DB_TABLE_PREFIX . "transactions
        SET " . implode(', ', $updateFields) . "
        WHERE id = ? AND user_id = ?
    ";

    $stmt = $conn->prepare($updateQuery);
    $stmt->execute($params);

    // Fetch updated transaction
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
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$transactionId, $userId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    // Format amount for response
    $transaction['amount'] = (float) $transaction['amount'];

    Response::success([
        'message' => 'Transaction updated successfully',
        'transaction' => $transaction
    ]);

} catch (Exception $e) {
    Response::error('Failed to update transaction: ' . $e->getMessage(), 500);
}
