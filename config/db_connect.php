<?php
$host = getenv('MYSQL_HOST') ?: 'localhost';
$dbname = getenv('MYSQL_DATABASE') ?: 'hotel_discovery';
$username = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    $pdo->exec("USE `$dbname`");

    return $pdo; // Return the PDO instance

} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Could not connect to the database. Please try again later.");
}