<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

include('db_connect.php');

// Handle delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = :id");
    $stmt->execute(['id' => $id]);
    header("Location: manage_notifications.php");
    exit;
}

// Fetch notifications
$stmt = $conn->query("
    SELECT notification_id, notification_user_id, notification_title, notification_message, notification_created_at 
    FROM notifications ORDER BY notification_id DESC
");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Notifications</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-5">
    <h3 class="mb-4">Manage Notifications</h3>
    <table class="table table-bordered table-hover">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>User ID</th>
                <th>Title</th>
                <th>Message</th>
                <th>Created</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($notifications as $n): ?>
            <tr>
                <td><?= $n['notification_id'] ?></td>
                <td><?= $n['notification_user_id'] ?></td>
                <td><?= htmlspecialchars($n['notification_title']) ?></td>
                <td><?= htmlspecialchars($n['notification_message']) ?></td>
                <td><?= $n['notification_created_at'] ?></td>
                <td><a href="?delete=<?= $n['notification_id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this notification?')">Delete</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
</div>

</body>
</html>
