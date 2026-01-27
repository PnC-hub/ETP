<?php
/**
 * Export Transactions to CSV Endpoint
 * Exports all transactions for the authenticated user to CSV format
 * GET /api/transactions/export
 *
 * Query Parameters (same as read.php):
 * - type: Filter by type (income|expense)
 * - category: Filter by category
 * - date_from: Filter from date (YYYY-MM-DD)
 * - date_to: Filter to date (YYYY-MM-DD)
 * - search: Search in description
 * - sort: Sort field (date|amount|created_at) (default: date)
 * - order: Sort order (asc|desc) (default: desc)
 */

require_once __DIR__ . '/../Database.php';
require_once __DIR__ . '/../Response.php';
require_once __DIR__ . '/../JWTMiddleware.php';
require_once __DIR__ . '/../SubscriptionMiddleware.php';
require_once __DIR__ . '/../config.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed. Use GET.', 405);
}

try {
    // Verify JWT token
    $userData = JWTMiddleware::verify();
    $userId = $userData['user_id'];

    // Check subscription status (allow free trial)
    $subscriptionStatus = SubscriptionMiddleware::checkSubscription($userId, true);

    // Get sort parameters
    $allowedSortFields = ['date', 'amount', 'created_at'];
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields) ? $_GET['sort'] : 'date';
    $order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

    // Build WHERE clause with filters (same as read.php)
    $where = ['user_id = ?'];
    $params = [$userId];

    // Filter by type
    if (isset($_GET['type']) && in_array($_GET['type'], ['income', 'expense'])) {
        $where[] = 'type = ?';
        $params[] = $_GET['type'];
    }

    // Filter by category
    if (isset($_GET['category']) && trim($_GET['category']) !== '') {
        $where[] = 'category = ?';
        $params[] = trim($_GET['category']);
    }

    // Filter by date range
    if (isset($_GET['date_from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'])) {
        $where[] = 'date >= ?';
        $params[] = $_GET['date_from'];
    }

    if (isset($_GET['date_to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'])) {
        $where[] = 'date <= ?';
        $params[] = $_GET['date_to'];
    }

    // Search in description
    if (isset($_GET['search']) && trim($_GET['search']) !== '') {
        $where[] = 'description LIKE ?';
        $params[] = '%' . trim($_GET['search']) . '%';
    }

    $whereClause = implode(' AND ', $where);

    // Get database connection
    $db = Database::getInstance();
    $conn = $db->getConnection();

    // Fetch all transactions matching filters (no pagination for export)
    $stmt = $conn->prepare("
        SELECT
            id,
            type,
            category,
            amount,
            description,
            date,
            created_at
        FROM " . DB_TABLE_PREFIX . "transactions
        WHERE {$whereClause}
        ORDER BY {$sort} {$order}
    ");

    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Generate filename with current date
    $filename = 'transactions_export_' . date('Y-m-d_His') . '.csv';

    // Set CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    // Open output stream
    $output = fopen('php://output', 'w');

    // Add BOM for proper Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write CSV header row
    $headers = [
        'ID',
        'Type',
        'Category',
        'Amount',
        'Description',
        'Date',
        'Created At'
    ];
    fputcsv($output, $headers);

    // Write data rows
    foreach ($transactions as $transaction) {
        $row = [
            $transaction['id'],
            ucfirst($transaction['type']),
            $transaction['category'],
            number_format((float)$transaction['amount'], 2, '.', ''),
            $transaction['description'],
            $transaction['date'],
            $transaction['created_at']
        ];
        fputcsv($output, $row);
    }

    // Close output stream
    fclose($output);

    // Log export action
    error_log("CSV Export: User {$userId} exported " . count($transactions) . " transactions");

    exit; // Stop script execution after CSV output

} catch (Exception $e) {
    // Reset headers in case of error
    header_remove();
    header('Content-Type: application/json');
    Response::error('Failed to export transactions: ' . $e->getMessage(), 500);
}
