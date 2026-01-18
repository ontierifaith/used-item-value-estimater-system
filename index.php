<?php
session_start();

// Database connection
try {
    $conn = new PDO("pgsql:host=localhost;dbname=used_item_value_estimator", "postgres", "BQfa2050*");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Handle login
$login_error = "";

if (isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("
            SELECT 
                u.user_id, 
                u.user_full_name, 
                l.login_user_name, 
                l.login_password_hash, 
                l.login_rank
            FROM users u
            JOIN logins l ON l.login_user_id = u.user_id
            WHERE u.user_email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['login_password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['login_user_name'];
            $_SESSION['fullname'] = $user['user_full_name'];
            $_SESSION['role'] = $user['login_rank'];

            $log_stmt = $conn->prepare("
                INSERT INTO user_activity (user_activity_user_id, user_activity_type, user_activity_details)
                VALUES (:uid, 'login_success', :details)
            ");
            $log_stmt->execute([
                ':uid' => $user['user_id'],
                ':details' => json_encode(['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']])
            ]);

            header("Location: dashboard.php");
            exit();
        } else {
            $login_error = "Invalid email or password.";
        }
    } catch (PDOException $e) {
        $login_error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login - SnapIt</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

<style>
/* Base styles */
body, html {
    height: 100%;
    font-family: 'Poppins', sans-serif;
    margin: 0;
    background: #f5f7fa; /* soft light background for better UX */
    display: flex;
    justify-content: center;
    align-items: center;
}

/* Container for centering login card */
.container {
    text-align: center;
}

/* SnapIt Logo */
.brand-logo {
    font-size: 48px;
    font-weight: 700;
    color: #007bff;
    margin-bottom: 25px;
}

/* Login Card */
.login-card {
    background: #ffffff;
    padding: 40px 30px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 380px;
    text-align: center;
}

/* Welcome Text */
.welcome-text h2 {
    color: #333;
    font-size: 26px;
    margin-bottom: 10px;
}
.welcome-text p {
    color: #666;
    font-size: 14px;
    margin-bottom: 25px;
}

/* Inputs */
input {
    width: 100%;
    padding: 14px;
    margin-bottom: 20px;
    border-radius: 10px;
    border: 1px solid #ddd;
    font-size: 15px;
}
input:focus {
    border-color: #007bff;
    outline: none;
    box-shadow: 0 0 8px rgba(0, 123, 255, 0.3);
}

/* Buttons */
button {
    width: 100%;
    padding: 14px;
    background: #007bff;
    color: #fff;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.3s;
}
button:hover {
    background: #0056b3;
}

/* Create Account Link */
.create-account-btn {
    display: block;
    margin-top: 15px;
    color: #007bff;
    font-weight: 600;
    text-decoration: none;
}
.create-account-btn:hover {
    text-decoration: underline;
}

/* Error message */
.error-message {
    color: #ff4d4f;
    background: #ffeaea;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
    font-size: 14px;
}

/* Footer */
.footer {
    font-size: 12px;
    color: #888;
    margin-top: 20px;
}

/* Responsive */
@media (max-width: 420px) {
    .login-card { padding: 30px 20px; }
    .brand-logo { font-size: 36px; margin-bottom: 20px; }
    .welcome-text h2 { font-size: 22px; }
}
</style>
</head>
<body>

<div class="container">
    <div class="brand-logo">SnapIt</div>

    <div class="login-card">
        <div class="welcome-text">
            <h2>Welcome Back</h2>
            <p>Sign in to your account to continue your pricing journey</p>
        </div>

        <?php if ($login_error): ?>
            <div class="error-message"><?= htmlspecialchars($login_error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>

        <a href="register.php" class="create-account-btn">Create Account</a>
        <div class="footer">&copy; <?= date('Y'); ?> SnapIt System</div>
    </div>
</div>

</body>
</html>
