<?php
// PHP/login.php
session_start();

// SQL Server connection
$server = 'Ndila-L';
$database = 'marlani_staff';
$username = 'root';
$password = '';

try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    $errors = [];
    
    // Validate inputs
    if (empty($email) || empty($password)) {
        $errors[] = "Please fill in all fields";
    }
    
    if (empty($errors)) {
        // Check if staff exists
        $stmt = $conn->prepare("SELECT * FROM staff WHERE email = ?");
        $stmt->execute([$email]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($staff && password_verify($password, $staff['password'])) {
            // Login successful
            $_SESSION['staff_id'] = $staff['id'];
            $_SESSION['staff_code'] = $staff['staff_code'];
            $_SESSION['first_name'] = $staff['first_name'];
            $_SESSION['last_name'] = $staff['last_name'];
            $_SESSION['email'] = $staff['email'];
            $_SESSION['logged_in'] = true;
            
            // Remember me (optional)
            if ($remember) {
                // Set cookie for 30 days
                setcookie('staff_email', $email, time() + (86400 * 30), '/');
            }
            
            // Redirect to dashboard
            header('Location: index.html');
            exit();
        } else {
            // Invalid credentials
            header('Location: login.html?error=1');
            exit();
        }
    } else {
        // Errors
        $_SESSION['login_errors'] = $errors;
        header('Location: login.html');
        exit();
    }
}
?>