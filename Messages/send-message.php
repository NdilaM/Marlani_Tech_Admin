<?php
// Messages/send-message.php
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

$me   = (int)$_SESSION['staff_id'];
$to   = (int)($_POST['recipient_id'] ?? 0);
$body = trim($_POST['body'] ?? '');

if ($to > 0 && $to !== $me && $body !== '') {
    $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, body) VALUES (?, ?, ?)");
    $stmt->execute([$me, $to, $body]);
}

header('Location: message-view.php?with=' . $to);
exit();