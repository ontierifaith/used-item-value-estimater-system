<?php
session_start();
include('db_connect.php');

if(!isset($_SESSION['user_id']) || !isset($_POST['notification_id'])) exit;

$user_id = $_SESSION['user_id'];
$notif_id = intval($_POST['notification_id']);

$stmt = $conn->prepare("
    UPDATE notifications SET notification_is_read=TRUE
    WHERE notification_id=:id AND notification_user_id=:user_id
");
$stmt->execute([':id'=>$notif_id, ':user_id'=>$user_id]);

header('Location: dashboard.php');
