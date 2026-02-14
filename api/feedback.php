<?php
require_once 'config.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (empty($input['type']) || empty($input['description'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type and description are required']);
    exit;
}

// Get user ID from token (optional)
$user_id = null;
$headers = getallheaders();
if (isset($headers['Authorization'])) {
    $token = str_replace('Bearer ', '', $headers['Authorization']);
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        $user_id = $decoded->user_id ?? null;
    } catch (Exception $e) {
        // Token invalid, but we allow anonymous feedback
    }
}

// Insert feedback into database
try {
    $pdo = getDBConnection();

    // Create feedback table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "feedback (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            type VARCHAR(50) NOT NULL,
            description TEXT NOT NULL,
            user_email VARCHAR(255) NULL,
            timestamp DATETIME NOT NULL,
            status VARCHAR(20) DEFAULT 'new',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_status (status),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $stmt = $pdo->prepare("
        INSERT INTO " . DB_PREFIX . "feedback
        (user_id, type, description, user_email, timestamp, status)
        VALUES (?, ?, ?, ?, ?, 'new')
    ");

    $stmt->execute([
        $user_id,
        $input['type'],
        $input['description'],
        $input['user_email'] ?? null,
        $input['timestamp'] ?? date('Y-m-d H:i:s')
    ]);

    // Send notification email (optional)
    if (defined('FEEDBACK_EMAIL') && FEEDBACK_EMAIL) {
        $subject = "🐛 Nuovo Feedback RiduciSpese: " . $input['type'];
        $message = "Nuovo feedback ricevuto:\n\n";
        $message .= "Tipo: " . $input['type'] . "\n";
        $message .= "Utente ID: " . ($user_id ?? 'Anonimo') . "\n";
        $message .= "Email: " . ($input['user_email'] ?? 'N/A') . "\n";
        $message .= "Timestamp: " . ($input['timestamp'] ?? date('Y-m-d H:i:s')) . "\n\n";
        $message .= "Descrizione:\n" . $input['description'];

        mail(FEEDBACK_EMAIL, $subject, $message);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Feedback inviato con successo'
    ]);

} catch (PDOException $e) {
    error_log("Feedback error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Errore durante il salvataggio del feedback'
    ]);
}
