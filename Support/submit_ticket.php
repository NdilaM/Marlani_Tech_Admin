<?php
// Support/submit-ticket.php
session_start();
require_once '../db.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: ../login.html');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create_ticket.php');
    exit();
}

$me          = (int)$_SESSION['staff_id'];
$staff_type  = strtolower($_SESSION['staff_type'] ?? 'staff');
$is_admin    = in_array($staff_type, ['admin', 'manager', 'superadmin'], true);

$subject     = trim($_POST['subject'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = $_POST['category'] ?? 'general';
$priority    = $_POST['priority'] ?? 'medium';

$allowed_priority = ['low','medium','high','urgent'];
$allowed_category = ['general','bug','hardware','software','access','other'];

if ($subject === '' || $description === '') {
    header('Location: create_ticket.php?error=1');
    exit();
}
if (!in_array($priority, $allowed_priority, true)) $priority = 'medium';
if (!in_array($category, $allowed_category, true)) $category = 'general';

$stmt = $conn->prepare("
    INSERT INTO support_tickets (staff_id, subject, description, category, priority, status)
    VALUES (?, ?, ?, ?, ?, 'open')
");
$stmt->execute([$me, $subject, $description, $category, $priority]);

/* Admins/managers go back to the list; regular staff stay on the form page */
if ($is_admin) {
    header('Location: support.php?success=1');
} else {
    header('Location: create_ticket.php?success=1');
}
exit();