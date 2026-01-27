<?php
/**
 * Delete Transaction Endpoint
 * Deletes a transaction for the authenticated user
 * DELETE /api/transactions/delete
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';
require_once __DIR__ . '/../JWTMiddleware.php';
require_once __DIR__ . '/../SubscriptionMiddleware.php';
require_once __DIR__ . '/../config.php';

// Set headers
header('Content-Type: application/json');

// Only allow DELETE requests
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Method not allowed. Use DELETE.', 405);
}

try {
    // Verify JWT token
    $userData = JWTMiddleware::verify();
    $userId = $userData['user_id'];

    // Check subscription status (allow free trial)
    SubscriptionMiddleware::checkSubscription($userId, true);

    // Get transaction ID from query parameter or JSON body
    $transactionId = null;

    // Try to get from query parameter first
    if (isset($_GET['id']) && is_numeric($_GET['id'])) {
        $transactionId = (int) $_GET['id'];
    } else {
        // Try to get from JSON body
        $input = file_get_contents('php://input');
        if (!empty($input)) {
            $data = json_decode($input, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($data['id']) && is_numeric($data['id'])) {
                $transactionId = (int) $data['id'];
            }
        }
    }

    // Validate transaction ID
    if ($transactionId === null) {
        Response::error('Missing or invalid transaction ID. Provide id in query parameter or JSON body.', 400);
    }

    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Check if transaction exists and belongs to user
    $stmt = $conn->prepare("
        SELECT id, type, category, amount, description, date
        FROM " . DB_TABLE_PREFIX . "transactions
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$transactionId, $userId]);
    $transaction = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$transaction) {
        Response::error('Transaction not found or does not belong to you', 404);
    }

    // Delete the transaction
    $stmt = $conn->prepare("
        DELETE FROM " . DB_TABLE_PREFIX . "transactions
        WHERE id = ? AND user_id = ?
    ");

    $stmt->execute([$transactionId, $userId]);

    // Check if deletion was successful
    if ($stmt->rowCount() === 0) {
        Response::error('Failed to delete transaction', 500);
    }

    Response::success([
        'message' => 'Transaction deleted successfully',
        'deleted_transaction' => [
            'id' => $transactionId,
            'type' => $transaction['type'],
            'category' => $transaction['category'],
            'amount' => (float) $transaction['amount'],
            'description' => $transaction['description'],
            'date' => $transaction['date']
        ]
    ]);

} catch (Exception $e) {
    Response::error('Failed to delete transaction: ' . $e->getMessage(), 500);
}
