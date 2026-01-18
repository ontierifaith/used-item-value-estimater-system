<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id'])) die("Not logged in.");
if (!isset($_POST['notification_id'])) die("Invalid request.");

try {
    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = :id");
    $stmt->bindParam(':id', $_POST['notification_id'], PDO::PARAM_INT);
    $stmt->execute();
    header("Location: notifications.php");
} catch (PDOException $e) {
    die("Error deleting: " . $e->getMessage());
}
?>
