<?php
declare(strict_types=1);

$dbHost = env_value('DB_HOST', '127.0.0.1');
$dbPort = env_value('DB_PORT', '3306');
$dbName = env_value('DB_DATABASE', '');
$dbUser = env_value('DB_USERNAME', '');
$dbPass = env_value('DB_PASSWORD', '');

$dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());

    if (!headers_sent()) {
        http_response_code(500);
    }

    die('Er is tijdelijk een technisch probleem. Probeer het later opnieuw.');
}
