<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Set timezone for greeting
date_default_timezone_set('Africa/Nairobi');
$hour = date('H');
$time_based_greeting = '';

if ($hour >= 5 && $hour < 12) {
    $time_based_greeting = 'Good Morning! ☀️ Start your estimations now.';
} elseif ($hour >= 12 && $hour < 18) {
    $time_based_greeting = 'Good Afternoon! ☕ Time for some valuation.';
} else {
    $time_based_greeting = 'Good Evening! 🌙 We\'re ready when you are.';
}

$username = htmlspecialchars($_SESSION['username'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - SnapIt</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body {
    font-family: 'Poppins', sans-serif;
    margin: 0;
    padding: 0;
    min-height: 100vh;
    background: linear-gradient(135deg, #f0f4f7 0%, #e8edf3 100%);
}

/* ---------- Header ---------- */
header {
    background: #ffffff; 
    padding: 15px 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    position: sticky;
    top: 0;
    z-index: 100;
}
header .logo {
    font-size: 24px;
    font-weight: 700;
    color: #007bff; 
}
header nav a {
    color: #555;
    text-decoration: none;
    margin-left: 15px;
    padding: 8px 12px;
    font-weight: 500;
    border-radius: 6px;
    transition: background 0.2s, color 0.2s;
}
header nav a:hover {
    background: #e9ecef;
    color: #007bff;
}
.logout-btn {
    background-color: #dc3545 !important; 
    color: white !important;
    padding: 10px 15px !important;
    border-radius: 8px !important;
    font-weight: 600 !important;
    margin-left: 20px; 
    box-shadow: 0 4px 10px rgba(220, 53, 69, 0.4);
    transition: background 0.3s, transform 0.2s, box-shadow 0.3s !important;
}
.logout-btn:hover {
    background-color: #c82333 !important;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(220, 53, 69, 0.6);
}

/* ---------- Container ---------- */
.container {
    background: #ffffff;
    padding: 40px 50px;
    border-radius: 15px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.15);
    max-width: 1000px;
    margin: 40px auto; 
    animation: fadeIn 1s ease-in-out;
}

/* ---------- Hero Section ---------- */
.hero-section {
    background: #ffffff;
    color: #333;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 40px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    text-align: center;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    transition: transform 0.3s, box-shadow 0.3s;
}
.hero-section:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}
.hero-text h1 {
    font-size: 28px;
    margin: 0;
    font-weight: 700;
    color: #007bff;
}
.hero-text .time-greeting {
    font-size: 16px;
    opacity: 0.8;
    margin-top: 5px;
}
.hero-icon i {
    font-size: 50px;
    color: #007bff;
}

/* ---------- Dashboard Cards ---------- */
.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 25px;
}
.card {
    color: white;
    padding: 25px 20px;
    border-radius: 12px;
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2); 
    transition: transform 0.3s, box-shadow 0.3s;
    cursor: pointer;
    text-align: center;
    text-decoration: none; 
}
.card i {
    font-size: 38px; 
    margin-bottom: 10px;
    display: block;
    animation: pulse 1s infinite alternate;
}
.card h3 {
    margin: 5px 0 5px 0;
    font-size: 18px;
    font-weight: 600;
}
.card.main-action-upload {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
}
.card.main-action-estimate {
    background: linear-gradient(135deg, #fd7e14 0%, #e85d04 100%);
}
.card.secondary-report {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); 
}
.card.secondary-notification {
    background: linear-gradient(135deg, #6f42c1 0%, #563d7c 100%); 
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
    opacity: 0.98; 
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
@keyframes pulse {
    from { transform: scale(1); }
    to { transform: scale(1.05); }
}
@media (max-width: 768px) {
    .dashboard-grid { grid-template-columns: 1fr 1fr; }
    .hero-section { flex-direction: column; }
}
@media (max-width: 480px) {
    .dashboard-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<header>
    <div class="logo">SnapIt User Dashboard</div>
    <nav>
        <a href="upload_item.php"><i class="fas fa-upload"></i> Upload</a>
        <a href="ai_estimate.php"><i class="fas fa-robot"></i> Estimate</a>
        <a href="reports.php"><i class="fas fa-file-alt"></i> Reports</a>
        <a href="notifications.php"><i class="fas fa-bell"></i> Alerts</a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
            <a href="admin_dashboard.php"><i class="fas fa-user-shield"></i> Admin</a>
        <?php endif; ?>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</header>

<div class="container">

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="hero-text">
            <h1>Welcome back, <?= $username ?>!</h1>
            <p class="time-greeting"><?= $time_based_greeting ?></p>
        </div>
        <div class="hero-icon">
            <i class="fas fa-tachometer-alt"></i>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="dashboard-grid">
        <a href="upload_item.php" class="card main-action-upload">
            <i class="fas fa-upload"></i>
            <h3>Upload Item</h3>
            <p>Submit new items for valuation.</p>
        </a>
        <a href="ai_estimate.php" class="card main-action-estimate">
            <i class="fas fa-robot"></i>
            <h3>Get AI Estimate</h3>
            <p>Instantly retrieve AI-driven value range.</p>
        </a>
        <a href="reports.php" class="card secondary-report">
            <i class="fas fa-file-alt"></i>
            <h3>My Reports</h3>
            <p>Review submitted items and reports.</p>
        </a>
        <a href="notifications.php" class="card secondary-notification">
            <i class="fas fa-bell"></i>
            <h3>Notifications</h3>
            <p>Check alerts and estimation status updates.</p>
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
        <a href="admin_dashboard.php" class="card" style="background: linear-gradient(135deg, #6c5ce7 0%, #a29bfe 100%);">
            <i class="fas fa-user-shield"></i>
            <h3>Admin Panel</h3>
            <p>Manage platform settings and users.</p>
        </a>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
