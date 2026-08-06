<?php
// db.php - Database Connection
session_start();

// Database settings
$host = 'localhost';
$database = 'marlani_admin';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $conn = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Optional: Set timezone
    $conn->exec("SET time_zone = '+02:00'");
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>