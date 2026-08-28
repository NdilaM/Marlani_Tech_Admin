<?php
// db.php
try {
    $host = 'localhost';
    $dbname = 'marlani_admin';
    $username = 'root';      // Default XAMPP username
    $password = '';          // Default XAMPP password (empty)
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Uncomment this to verify connection
    // echo "Connected successfully";
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>