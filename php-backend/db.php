<?php
/**
 * Goshen Dental Care - Database Connector
 * Uses PDO for secure parameterized queries & clean error containment.
 */

// Configure Response Headers for API use
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle Options Preflight Requests gracefully
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database Credentials setup (Standard default configurations, override as needed)
$db_host = '127.0.0.1';
$db_name = 'goshen_dental';
$db_user = 'root';
$db_pass = ''; // Leave blank for default local environments like XAMPP/WAMP

try {
    $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // Return structured, helpful response in case configuration is not completed yet
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database connection failed. Please ensure MySQL is running and your configurations in db.php are correct.',
        'technical_details' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit();
}
