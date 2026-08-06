<?php
// login.php
session_start();
require_once 'db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Debug: Check if values are received
    // echo "Email: $email, Password: $password"; // Uncomment to test
    
    if (empty($email) || empty($password)) {
        header('Location: login.html?error=Please fill in all fields');
        exit();
    }
    
    try {
        // Check if staff exists
        $stmt = $conn->prepare("SELECT * FROM staff WHERE email = ?");
        $stmt->execute([$email]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Check if user found
        // var_dump($staff); // Uncomment to test
        
        if ($staff && password_verify($password, $staff['password'])) {
            // Login successful
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['first_name'] = $staff['first_name'];
            $_SESSION['last_name'] = $staff['last_name'];
            $_SESSION['email'] = $staff['email'];
            $_SESSION['logged_in'] = true;
            
            // Redirect to dashboard
            header('Location: index.php');
            exit();
        } else {
            // Invalid credentials
            header('Location: login.html?error=Invalid email or password');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: login.html?error=Database error: ' . $e->getMessage());
        exit();
    }
} else {
    // If not POST, redirect to login page
    header('Location: login.html');
    exit();
}
?>