<?php
/**
 * ETP API Testing Script
 * Tests all API endpoints sequentially
 */

// Configuration
$API_BASE = 'http://localhost/ETP/api';
$TEST_EMAIL = 'testuser_' . time() . '@etp.test';
$TEST_PASSWORD = 'TestPass123!';
$TEST_NAME = 'Test User ' . date('H:i:s');

// Color output for terminal
function colorOutput($message, $status = 'info') {
    $colors = [
        'success' => "\033[0;32m",
        'error' => "\033[0;31m",
        'info' => "\033[0;36m",
        'warning' => "\033[0;33m",
    ];
    $reset = "\033[0m";
    echo $colors[$status] . $message . $reset . PHP_EOL;
}

function testEndpoint($method, $endpoint, $data = null, $token = null) {
    global $API_BASE;

    $url = $API_BASE . $endpoint;
    $ch = curl_init($url);

    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'PUT') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    } elseif ($method === 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    return [
        'code' => $httpCode,
        'response' => $result,
        'raw' => $response
    ];
}

// Start testing
colorOutput("=== ETP API Test Suite ===\n", 'info');

// Test 1: User Registration
colorOutput("Test 1: User Registration", 'info');
$registerResult = testEndpoint('POST', '/auth/register.php', [
    'email' => $TEST_EMAIL,
    'password' => $TEST_PASSWORD,
    'name' => $TEST_NAME
]);

if ($registerResult['code'] === 201 && isset($registerResult['response']['data']['token'])) {
    colorOutput("✓ Registration successful", 'success');
    $authToken = $registerResult['response']['data']['token'];
} else {
    colorOutput("✗ Registration failed: " . json_encode($registerResult['response']), 'error');
    exit(1);
}

// Test 2: User Login
colorOutput("\nTest 2: User Login", 'info');
$loginResult = testEndpoint('POST', '/auth/login.php', [
    'email' => $TEST_EMAIL,
    'password' => $TEST_PASSWORD
]);

if ($loginResult['code'] === 200 && isset($loginResult['response']['data']['token'])) {
    colorOutput("✓ Login successful", 'success');
    $authToken = $loginResult['response']['data']['token'];
} else {
    colorOutput("✗ Login failed: " . json_encode($loginResult['response']), 'error');
    exit(1);
}

// Test 3: Get User Status
colorOutput("\nTest 3: Get User Status", 'info');
$statusResult = testEndpoint('GET', '/user/status.php', null, $authToken);

if ($statusResult['code'] === 200) {
    colorOutput("✓ User status retrieved", 'success');
    colorOutput("   Plan: " . ($statusResult['response']['data']['subscription']['plan'] ?? 'free'), 'info');
} else {
    colorOutput("✗ Status check failed: " . json_encode($statusResult['response']), 'error');
}

// Test 4: Create Transaction (Income)
colorOutput("\nTest 4: Create Transaction (Income)", 'info');
$createTxResult = testEndpoint('POST', '/transactions/create.php', [
    'type' => 'income',
    'category' => 'Salary',
    'amount' => 2500.00,
    'description' => 'Monthly salary',
    'date' => date('Y-m-d')
], $authToken);

if ($createTxResult['code'] === 201) {
    colorOutput("✓ Transaction created", 'success');
    $transactionId = $createTxResult['response']['data']['id'];
} else {
    colorOutput("✗ Transaction creation failed: " . json_encode($createTxResult['response']), 'error');
    $transactionId = null;
}

// Test 5: Create Transaction (Expense)
colorOutput("\nTest 5: Create Transaction (Expense)", 'info');
$createExpenseResult = testEndpoint('POST', '/transactions/create.php', [
    'type' => 'expense',
    'category' => 'Food',
    'amount' => 45.50,
    'description' => 'Grocery shopping',
    'date' => date('Y-m-d')
], $authToken);

if ($createExpenseResult['code'] === 201) {
    colorOutput("✓ Expense created", 'success');
} else {
    colorOutput("✗ Expense creation failed: " . json_encode($createExpenseResult['response']), 'error');
}

// Test 6: Read Transactions
colorOutput("\nTest 6: Read Transactions", 'info');
$readResult = testEndpoint('GET', '/transactions/read.php?page=1&limit=10', null, $authToken);

if ($readResult['code'] === 200 && isset($readResult['response']['data'])) {
    $count = count($readResult['response']['data']);
    colorOutput("✓ Transactions retrieved: {$count} transactions", 'success');
} else {
    colorOutput("✗ Read transactions failed: " . json_encode($readResult['response']), 'error');
}

// Test 7: Update Transaction
if ($transactionId) {
    colorOutput("\nTest 7: Update Transaction", 'info');
    $updateResult = testEndpoint('PUT', '/transactions/update.php', [
        'id' => $transactionId,
        'amount' => 2600.00,
        'description' => 'Monthly salary (updated)'
    ], $authToken);

    if ($updateResult['code'] === 200) {
        colorOutput("✓ Transaction updated", 'success');
    } else {
        colorOutput("✗ Update failed: " . json_encode($updateResult['response']), 'error');
    }
}

// Test 8: Export Transactions
colorOutput("\nTest 8: Export Transactions (CSV)", 'info');
$exportResult = testEndpoint('GET', '/transactions/export.php', null, $authToken);

if ($exportResult['code'] === 200 && strpos($exportResult['raw'], 'Date,Type,Category') !== false) {
    colorOutput("✓ Export successful (CSV generated)", 'success');
} else {
    colorOutput("✗ Export failed", 'error');
}

// Test 9: Delete Transaction
if ($transactionId) {
    colorOutput("\nTest 9: Delete Transaction", 'info');
    $deleteResult = testEndpoint('DELETE', '/transactions/delete.php?id=' . $transactionId, null, $authToken);

    if ($deleteResult['code'] === 200) {
        colorOutput("✓ Transaction deleted", 'success');
    } else {
        colorOutput("✗ Delete failed: " . json_encode($deleteResult['response']), 'error');
    }
}

// Test 10: Invalid Token
colorOutput("\nTest 10: Invalid Token Test", 'info');
$invalidResult = testEndpoint('GET', '/user/status.php', null, 'invalid_token_12345');

if ($invalidResult['code'] === 401) {
    colorOutput("✓ Invalid token correctly rejected", 'success');
} else {
    colorOutput("✗ Security check failed (invalid token accepted)", 'error');
}

// Summary
colorOutput("\n=== Test Suite Completed ===", 'info');
colorOutput("All core API endpoints have been tested.", 'success');
colorOutput("\nNote: Stripe checkout and webhook tests require manual testing", 'warning');
colorOutput("with actual Stripe test keys and webhook configuration.", 'warning');
