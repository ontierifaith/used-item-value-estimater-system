<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id'])) {
    die("<h3 style='color:red;text-align:center;margin-top:20px;'>⚠️ User not logged in.</h3>");
}

$user_id = $_SESSION['user_id'];

// Validate notification ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("<h3 style='color:red;text-align:center;margin-top:20px;'>⚠️ Invalid notification ID.</h3>");
}

$notification_id = $_GET['id'];

// Try to fetch the notification for the user first
$stmt = $conn->prepare("
    SELECT notification_id, notification_title, notification_message, notification_is_read, notification_created_at 
    FROM notifications 
    WHERE notification_id = :nid AND (notification_user_id = :uid OR notification_user_id IS NULL)
");
$stmt->execute([':nid' => $notification_id, ':uid' => $user_id]);
$notification = $stmt->fetch(PDO::FETCH_ASSOC);

// If still not found, fetch without user filter (system-wide fallback)
if (!$notification) {
    $stmt2 = $conn->prepare("
        SELECT notification_id, notification_title, notification_message, notification_is_read, notification_created_at 
        FROM notifications 
        WHERE notification_id = :nid
    ");
    $stmt2->execute([':nid' => $notification_id]);
    $notification = $stmt2->fetch(PDO::FETCH_ASSOC);
}

if (!$notification) {
    die("<h3 style='color:red;text-align:center;margin-top:20px;'>⚠️ Notification not found or access denied.</h3>");
}

// Mark as read if unread
if (!$notification['notification_is_read']) {
    $update = $conn->prepare("
        UPDATE notifications 
        SET notification_is_read = TRUE 
        WHERE notification_id = :nid
    ");
    $update->execute([':nid' => $notification_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>View Notification</title>
<style>
    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #f9fafc;
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
    }
    .container {
        background: #fff;
        width: 500px;
        max-width: 95%;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
    }
    h2 {
        color: #333;
        margin-bottom: 15px;
    }
    .message-box {
        text-align: left;
        background: #f1f6ff;
        padding: 15px;
        border-left: 4px solid #007bff;
        border-radius: 5px;
        margin-bottom: 20px;
    }
    .message-box p {
        color: #444;
        font-size: 15px;
        line-height: 1.5;
    }
    .date {
        color: #777;
        font-size: 13px;
        margin-top: 10px;
    }
    .buttons {
        display: flex;
        justify-content: space-around;
        margin-top: 20px;
    }
    .btn {
        padding: 10px 20px;
        text-decoration: none;
        color: white;
        border-radius: 5px;
        font-weight: bold;
        transition: background 0.3s ease;
    }
    .btn-dashboard {
        background-color: #007bff;
    }
    .btn-dashboard:hover {
        background-color: #0056b3;
    }
    .btn-notifications {
        background-color: #28a745;
    }
    .btn-notifications:hover {
        background-color: #1e7e34;
    }
</style>
</head>
<body>

<div class="container">
    <h2><?= htmlspecialchars($notification['notification_title'] ?? 'No Title') ?></h2>
    <div class="message-box">
        <p><?= nl2br(htmlspecialchars($notification['notification_message'] ?? 'No message available')) ?></p>
        <div class="date">🕒 <?= htmlspecialchars($notification['notification_created_at'] ?? '') ?></div>
    </div>

    <div class="buttons">
        <a href="notifications.php" class="btn btn-notifications">← Back to Notifications</a>
        <a href="dashboard.php" class="btn btn-dashboard">🏠 Back to Dashboard</a>
    </div>
</div>

</body>
</html>
