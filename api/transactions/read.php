<?php
/**
 * Read Transactions Endpoint
 * Retrieves transactions for the authenticated user with filtering and pagination
 * GET /api/transactions/read
 *
 * Query Parameters:
 * - page: Page number (default: 1)
 * - limit: Items per page (default: 50, max: 100)
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

// Set headers
header('Content-Type: application/json');

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

    // Get pagination parameters
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $limit = isset($_GET['limit']) ? min(100, max(1, (int)$_GET['limit'])) : 50;
    $offset = ($page - 1) * $limit;

    // Get sort parameters
    $allowedSortFields = ['date', 'amount', 'created_at'];
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortFields) ? $_GET['sort'] : 'date';
    $order = isset($_GET['order']) && strtolower($_GET['order']) === 'asc' ? 'ASC' : 'DESC';

    // Build WHERE clause with filters
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

    // Get total count for pagination
    $countStmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM " . DB_TABLE_PREFIX . "transactions
        WHERE {$whereClause}
    ");
    $countStmt->execute($params);
    $totalCount = (int) $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Calculate pagination info
    $totalPages = ceil($totalCount / $limit);

    // Fetch transactions
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
        WHERE {$whereClause}
        ORDER BY {$sort} {$order}
        LIMIT ? OFFSET ?
    ");

    // Add limit and offset to params
    $params[] = $limit;
    $params[] = $offset;

    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format amounts
    foreach ($transactions as &$transaction) {
        $transaction['amount'] = (float) $transaction['amount'];
    }

    // Calculate summary statistics (for current filters, not just current page)
    $summaryStmt = $conn->prepare("
        SELECT
            SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as total_expenses,
            SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END) as net_balance
        FROM " . DB_TABLE_PREFIX . "transactions
        WHERE {$whereClause}
    ");

    $summaryParams = array_slice($params, 0, count($params) - 2); // Remove limit and offset
    $summaryStmt->execute($summaryParams);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

    Response::success([
        'transactions' => $transactions,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total_items' => $totalCount,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1
        ],
        'summary' => [
            'total_income' => (float) ($summary['total_income'] ?? 0),
            'total_expenses' => (float) ($summary['total_expenses'] ?? 0),
            'net_balance' => (float) ($summary['net_balance'] ?? 0),
            'count' => $totalCount
        ],
        'subscription_status' => [
            'has_access' => $subscriptionStatus['has_access'],
            'is_in_free_trial' => $subscriptionStatus['is_in_free_trial'],
            'has_active_subscription' => $subscriptionStatus['has_active_subscription']
        ]
    ]);

} catch (Exception $e) {
    Response::error('Failed to fetch transactions: ' . $e->getMessage(), 500);
}
