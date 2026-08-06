<?php
// register.php
require_once 'db.php';  // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $company_code_input = trim($_POST['company_code'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    $errors = [];
    
    // Validate inputs
    if (empty($first_name)) $errors[] = "First name is required";
    if (empty($last_name)) $errors[] = "Last name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (empty($company_code_input)) $errors[] = "Company code is required";
    if (empty($password)) $errors[] = "Password is required";
    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters";
    if ($password !== $confirm_password) $errors[] = "Passwords do not match";
    
    // Validate company code from database
    if (!empty($company_code_input)) {
        $stmt = $conn->prepare("SELECT setting_value FROM company_settings WHERE setting_key = 'company_code'");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            if ($company_code_input !== $result['setting_value']) {
                $errors[] = "Invalid company code. Please contact HR.";
            }
        } else {
            $errors[] = "Company code not configured.";
        }
    }
    
    // Check if email exists
    if (!empty($email)) {
        $stmt = $conn->prepare("SELECT * FROM staff WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $errors[] = "Email already registered";
        }
    }
    
    // Register user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO staff (first_name, last_name, email, password, company_code) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$first_name, $last_name, $email, $hashed_password, $company_code_input]);
            
            header('Location: register.html?success=1');
            exit();
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
    
    // Return errors
    if (!empty($errors)) {
        $_SESSION['registration_errors'] = $errors;
        $_SESSION['form_data'] = $_POST;
        header('Location: register.html');
        exit();
    }
} else {
    header('Location: register.html');
    exit();
}
?>