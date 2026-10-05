<?php
// login.php
session_start();
require_once 'db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    header('Location: login.html?error=1');
    exit();
}

try {
    $stmt = $conn->prepare("SELECT * FROM staff WHERE email = ?");
    $stmt->execute([$email]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($staff && password_verify($password, $staff['password'])) {
        // Prevent session fixation
        session_regenerate_id(true);

        $_SESSION['staff_id']   = $staff['id'];
        $_SESSION['first_name'] = $staff['first_name'];
        $_SESSION['last_name']  = $staff['last_name'];
        $_SESSION['email']      = $staff['email'];
        $_SESSION['logged_in']  = true;
        $_SESSION['staff_type'] = $staff['staff_type'];

        header('Location: index.php');
        exit();
    }

    header('Location: login.html?error=1');
    exit();

} catch (PDOException $e) {
    error_log('Login DB error: ' . $e->getMessage());
    header('Location: login.html?error=1');
    exit();
}
?>