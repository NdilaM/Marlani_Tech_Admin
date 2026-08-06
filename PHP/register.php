<?php
// PHP/register.php

// SQL Server connection
$server = 'Ndila-L';  // server name
$database = 'marlani_staff';
$username = 'root';
$password = '';

try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $staff_code = $_POST['staff_code'];
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validate staff code (check if it exists in staff_codes)
    $stmt = $conn->prepare("SELECT * FROM staff_codes WHERE code = ?");
    $stmt->execute([$staff_code]);
    if ($stmt->rowCount() == 0) {
        $errors[] = "Invalid staff code";
    }
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM staff WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $errors[] = "Email already registered";
    }
    
    // Check passwords match
    if ($password !== $confirm) {
        $errors[] = "Passwords don't match";
    }
    
    // If no errors, register
    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO staff (staff_code, first_name, last_name, email, password) 
                                VALUES (?, ?, ?, ?, ?)");
        
        if ($stmt->execute([$staff_code, $first_name, $last_name, $email, $hashed])) {
            header('Location: login.html?success=1');
            exit();
        } else {
            $errors[] = "Registration failed";
        }
    }
    
    // If errors, go back
    if (!empty($errors)) {
        session_start();
        $_SESSION['errors'] = $errors;
        header('Location: staff-register.html');
        exit();
    }
}
?>