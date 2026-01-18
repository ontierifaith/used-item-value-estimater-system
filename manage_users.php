<?php
session_start();
include('db_connect.php');

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    die("Admin not logged in.");
}

// Fetch all users
$stmt = $conn->prepare("
    SELECT user_id, user_full_name, user_email, user_phone, user_created_at, user_is_active
    FROM users
    ORDER BY user_id DESC
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Users | Admin Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap + Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
body { background-color: #eef1f5; font-family: 'Poppins', sans-serif; margin:0; padding:0; }
.wrapper { display: flex; width: 100%; min-height: 100vh; }
#sidebar { width: 260px; background: #1c1f24; color: #fff; position: sticky; top: 0; height: 100vh; box-shadow: 3px 0px 10px rgba(0,0,0,0.15); }
.sidebar-header { padding: 25px; text-align: center; background: #16181c; }
.sidebar-header h3 { font-weight: 700; color: #0d6efd; margin: 0; font-size: 1.5rem; }
.sidebar-links a { display: block; padding: 15px 22px; margin: 3px 0; color: #adb5bd; text-decoration: none; font-size: 1.05rem; transition: 0.25s; border-left: 4px solid transparent; }
.sidebar-links a:hover, .sidebar-links .active { background: rgba(255,255,255,0.08); color: #fff; border-left: 4px solid #0d6efd; }
.sidebar-links a i { margin-right: 10px; }
#content { width: 100%; padding: 35px; }
.header-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 6px 18px rgba(0,0,0,0.08); display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.header-card h2 { font-size: 1.6rem; font-weight: 700; margin: 0; }
.data-card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 14px rgba(0,0,0,0.08); }
table thead th { background: #0d6efd; color: white; text-align: center; border: none; }
table tbody tr:hover { background: #f1f7ff; }
table td, table th { vertical-align: middle; text-align: center; }
.action-btn { margin: 0 2px; }
</style>
</head>
<body>

<div class="wrapper">

    <!-- Sidebar -->
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-users me-2"></i> UsedItem</h3>
        </div>
        <div class="sidebar-links">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="manage_ai_estimates.php"><i class="fas fa-chart-line"></i> System Logs</a>
            <a href="#" class="active"><i class="fas fa-file-alt"></i> Manage Reports</a>
            <a href="ai_performance_dashboard.php"><i class="fas fa-robot"></i> AI Model</a>
    
        </div>
    </nav>

    <!-- Main Content -->
    <div id="content">
        <div class="header-card">
            <h2><i class="fas fa-users-cog text-primary me-2"></i> Manage Users</h2>
            <a href="admin_dashboard.php" class="btn btn-primary"><i class="fas fa-arrow-left me-1"></i> Back to Dashboard</a>
        </div>

        <div class="data-card">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Active</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?= htmlspecialchars($u['user_id']); ?></td>
                                <td><?= htmlspecialchars($u['user_full_name']); ?></td>
                                <td><?= htmlspecialchars($u['user_email']); ?></td>
                                <td><?= htmlspecialchars($u['user_phone']); ?></td>
                                <td><?= $u['user_is_active'] ? 'Yes' : 'No'; ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?= $u['user_id']; ?>" class="btn btn-sm btn-warning action-btn"><i class="fas fa-edit"></i></a>
                                    <a href="delete_user.php?id=<?= $u['user_id']; ?>" class="btn btn-sm btn-danger action-btn" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted"><i class="fas fa-info-circle me-2"></i> No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>
