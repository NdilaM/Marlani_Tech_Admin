<?php

include 'db.php';

$first = $_POST['first_name'];
$last = $_POST['last_name'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm = $_POST['confirm_password'];

if($password != $confirm){
    die("Passwords do not match");
}

// Check if email exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
$stmt->bind_param("s",$email);
$stmt->execute();
$stmt->store_result();

if($stmt->num_rows > 0){
    die("Email already exists");
}

$stmt->close();

// Hash password
$hash = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$stmt = $conn->prepare("INSERT INTO users(first_name,last_name,email,password)
VALUES(?,?,?,?)");

$stmt->bind_param("ssss",$first,$last,$email,$hash);

if($stmt->execute()){
    header("Location: login.html");
}else{
    echo "Registration failed";
}

$stmt->close();
$conn->close();

?>