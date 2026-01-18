<?php
session_start();
include('db_connect.php');

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    die("Admin not logged in.");
}

// Get user ID
if (!isset($_GET['id'])) {
    die("User ID not provided.");
}
$user_id = intval($_GET['id']);

// Fetch existing user data
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Handle POST (update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['user_full_name']);
    $email = trim($_POST['user_email']);
    $phone = trim($_POST['user_phone']);
    $is_active = isset($_POST['user_is_active']) ? true : false;

    $update = $conn->prepare("
        UPDATE users 
        SET user_full_name = :full_name,
            user_email = :email,
            user_phone = :phone,
            user_is_active = :is_active
        WHERE user_id = :id
    ");
    $update->execute([
        'full_name' => $full_name,
        'email' => $email,
        'phone' => $phone,
        'is_active' => $is_active,
        'id' => $user_id
    ]);

    header("Location: manage_users.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Edit User | Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2>Edit User</h2>
    <form method="post">
        <div class="mb-3">
            <label>Full Name</label>
            <input type="text" name="user_full_name" class="form-control" value="<?= htmlspecialchars($user['user_full_name']); ?>" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="user_email" class="form-control" value="<?= htmlspecialchars($user['user_email']); ?>" required>
        </div>
        <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="user_phone" class="form-control" value="<?= htmlspecialchars($user['user_phone']); ?>">
        </div>
        <div class="form-check mb-3">
            <input type="checkbox" name="user_is_active" class="form-check-input" id="is_active" <?= $user['user_is_active'] ? 'checked' : ''; ?>>
            <label class="form-check-label" for="is_active">Active</label>
        </div>
        <button type="submit" class="btn btn-primary">Update User</button>
        <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
