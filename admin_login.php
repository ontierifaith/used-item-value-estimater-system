<?php
session_start();
include('db_connect.php');

$error = "";

// Handle POST request
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_username = trim($_POST['admin_username']);
    $admin_password = $_POST['admin_password'];

    $stmt = $conn->prepare("SELECT admin_id, admin_username, admin_password_hash FROM admins WHERE admin_username = :username");
    $stmt->execute(['username' => $admin_username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($admin_password, $admin['admin_password_hash'])) {
        $_SESSION['admin_id'] = $admin['admin_id'];
        $_SESSION['admin_username'] = $admin['admin_username'];
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login | Snapit</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --snapit-blue-dark: #4e54c8;
    --snapit-blue-light: #8f94fb;
    --snapit-accent: #007bff;
}
body {
    font-family: 'Poppins', sans-serif;
    background: #ffffff;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}
.login-card {
    background: #fff;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    width: 100%;
    max-width: 400px;
    animation: fadeIn 0.8s ease-out;
    transition: transform 0.3s;
    border-top: 5px solid var(--snapit-accent);
}
.login-card:hover {
    transform: translateY(-2px);
}
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
.brand-title {
    text-align: center;
    margin-bottom: 30px;
    color: var(--snapit-blue-dark);
    font-weight: 700;
    font-size: 2rem;
}
.brand-title i { margin-right: 10px; color: var(--snapit-accent); }
.input-group-custom { margin-bottom: 20px; position: relative; }
.input-group-custom input {
    border: none;
    border-bottom: 2px solid #ddd;
    padding: 12px 12px 12px 40px;
    width: 100%;
    font-size: 16px;
    border-radius: 0;
    transition: border-bottom-color 0.3s, box-shadow 0.3s;
    outline: none;
}
.input-group-custom input:focus {
    border-bottom-color: var(--snapit-accent);
    box-shadow: 0 4px 0 -2px var(--snapit-accent);
}
.input-group-custom .icon {
    position: absolute;
    top: 50%;
    left: 10px;
    transform: translateY(-50%);
    color: #999;
    font-size: 18px;
    pointer-events: none;
}
.tts-button {
    background: none;
    border: none;
    cursor: pointer;
    color: var(--snapit-blue-dark);
    font-size: 1.1em;
    padding: 0 5px;
    transition: 0.2s;
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    height: 100%;
}
.tts-button:hover { color: var(--snapit-accent); transform: translateY(-50%) scale(1.15); }
.form-label-hidden { display: none; }
.btn-login {
    background: var(--snapit-blue-dark);
    color: white;
    border: none;
    padding: 15px;
    border-radius: 10px;
    font-size: 18px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    box-shadow: 0 5px 15px rgba(78,84,200,0.4);
    margin-top: 10px;
}
.btn-login:hover {
    background: var(--snapit-blue-light);
    transform: translateY(-1px);
    box-shadow: 0 7px 20px rgba(78,84,200,0.6);
}
.error-alert {
    color: #dc3545;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    font-weight: 500;
    margin-top: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.footer { text-align: center; margin-top: 30px; font-size: 14px; color: rgba(0,0,0,0.5); }
</style>
</head>
<body>

<div class="login-card">
    <h2 class="brand-title"><i class="fas fa-lock"></i> Snapit Admin</h2>

    <form method="post" onsubmit="return validateForm();">
        <div class="input-group-custom">
            <label for="admin_username" class="form-label-hidden">Username</label>
            <i class="fas fa-user icon"></i>
            <input type="text" id="admin_username" name="admin_username" placeholder="Admin Username" required>
            <button type="button" class="tts-button" onclick="speakText('Enter your username')"><i class="fas fa-volume-up"></i></button>
        </div>

        <div class="input-group-custom">
            <label for="admin_password" class="form-label-hidden">Password</label>
            <i class="fas fa-key icon"></i>
            <input type="password" id="admin_password" name="admin_password" placeholder="Password" required>
            <button type="button" class="tts-button" onclick="speakText('Enter your password')"><i class="fas fa-volume-up"></i></button>
        </div>

        <button type="submit" class="btn-login">SIGN IN</button>
    </form>

    <?php if (!empty($error)) : ?>
        <p class="error-alert" id="error-message">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($error); ?>
            <button type="button" class="tts-button" style="position: static; transform: none; margin-left: 10px; color: #dc3545;" onclick="speakText('<?= htmlspecialchars($error); ?>')"><i class="fas fa-volume-up"></i></button>
        </p>
    <?php endif; ?>

    <div class="footer">Admin Panel Access Only | &copy; <?= date('Y'); ?> Snapit</div>
</div>

<script>
if ('speechSynthesis' in window) {
    function speakText(text) {
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'en-US';
        utterance.rate = 1;
        window.speechSynthesis.speak(utterance);
    }
} else {
    document.querySelectorAll('.tts-button').forEach(btn => btn.style.display = 'none');
}

function validateForm() {
    const username = document.getElementById("admin_username").value.trim();
    const password = document.getElementById("admin_password").value.trim();
    if (username === "" || password === "") {
        alert("Please fill in both username and password.");
        speakText("Please fill in both username and password.");
        return false;
    }
    return true;
}
</script>

</body>
</html>
