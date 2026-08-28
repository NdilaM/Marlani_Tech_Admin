<?php
// register.php
session_start();

// Database connection
require_once 'db.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.html');
    exit();
}

// =====================================================
// GET FORM DATA
// =====================================================

$first_name = trim($_POST['first_name'] ?? '');
$last_name = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$company_code_input = trim($_POST['company_code'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';

$errors = [];

// =====================================================
// VALIDATE FORM INPUTS
// =====================================================

if (empty($first_name)) {
    $errors[] = "First name is required.";
}

if (empty($last_name)) {
    $errors[] = "Last name is required.";
}

if (empty($email)) {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if (empty($company_code_input)) {
    $errors[] = "Company code is required.";
}

if (empty($password)) {
    $errors[] = "Password is required.";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters.";
}

if (empty($confirm_password)) {
    $errors[] = "Please confirm your password.";
} elseif ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
}

// =====================================================
// VALIDATE COMPANY CODE
// =====================================================

if (!empty($company_code_input)) {
    try {
        $stmt = $conn->prepare("
            SELECT setting_value
            FROM company_settings
            WHERE setting_key = 'company_code'
            LIMIT 1
        ");
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            if ($company_code_input !== $result['setting_value']) {
                $errors[] = "Invalid company code. Please contact HR.";
            }
        } else {
            $errors[] = "Company code has not been configured.";
        }
    } catch (PDOException $e) {
        $errors[] = "Unable to verify company code.";
    }
}

// =====================================================
// CHECK IF EMAIL ALREADY EXISTS
// =====================================================

if (!empty($email)) {
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*)
            FROM staff
            WHERE email = ?
        ");
        
        $stmt->execute([$email]);
        $emailExists = $stmt->fetchColumn();
        
        if ($emailExists > 0) {
            $errors[] = "This email address is already registered.";
        }
    } catch (PDOException $e) {
        $errors[] = "Unable to check email address.";
    }
}

// =====================================================
// IF THERE ARE ERRORS
// =====================================================

if (!empty($errors)) {
    $_SESSION['registration_errors'] = $errors;
    $_SESSION['form_data'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'company_code' => $company_code_input
    ];
    
    header('Location: register.html?error=' . urlencode(implode(' | ', $errors)));
    exit();
}

// =====================================================
// REGISTER STAFF MEMBER
// =====================================================

try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO staff
        (first_name, last_name, email, password, company_code)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $first_name,
        $last_name,
        $email,
        $hashed_password,
        $company_code_input
    ]);
    
    unset($_SESSION['registration_errors']);
    unset($_SESSION['form_data']);
    
    header('Location: register.html?success=1');
    exit();
    
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    
    $_SESSION['registration_errors'] = [
        "Registration failed. Please try again."
    ];
    
    $_SESSION['form_data'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'company_code' => $company_code_input
    ];
    
    header('Location: register.html?error=' . urlencode("Registration failed. Please try again."));
    exit();
}
?>