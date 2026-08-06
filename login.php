<?php
// login.php
require_once 'db.php';  // Include database connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        header('Location: login.html?error=1');
        exit();
    }
    
    $stmt = $conn->prepare("SELECT * FROM staff WHERE email = ?");
    $stmt->execute([$email]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff && password_verify($password, $staff['password'])) {
        $_SESSION['staff_id'] = $staff['id'];
        $_SESSION['first_name'] = $staff['first_name'];
        $_SESSION['last_name'] = $staff['last_name'];
        $_SESSION['email'] = $staff['email'];
        $_SESSION['logged_in'] = true;
        
        header('Location: index.php');
        exit();
    } else {
        header('Location: login.html?error=1');
        exit();
    }
} else {
    header('Location: login.html');
    exit();
}
?>