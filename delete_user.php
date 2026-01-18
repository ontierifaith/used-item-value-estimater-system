<?php
session_start();
include('db_connect.php');

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    die("Admin not logged in.");
}

// Check if user_id is provided
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}

$user_id = intval($_GET['id']);

// Delete user
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);

// Redirect back to manage_users.php
header("Location: manage_users.php");
exit;
?>
