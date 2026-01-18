<?php
include('db_connect.php');

if (isset($_POST['register'])) {
    try {
        $conn->beginTransaction();

        $full_name = $_POST['full_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $username = $_POST['username'];
        $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);

        // Insert into users table
        $stmt = $conn->prepare("INSERT INTO users (user_full_name, user_email, user_phone) 
                                VALUES (:name, :email, :phone)");
        $stmt->execute([
            ':name' => $full_name,
            ':email' => $email,
            ':phone' => $phone
        ]);

        $user_id = $conn->lastInsertId();

        // Insert into logins table
        $stmt2 = $conn->prepare("INSERT INTO logins (login_user_id, login_user_name, login_password_hash, login_rank)
                                 VALUES (:uid, :uname, :pass, 'user')");
        $stmt2->execute([
            ':uid' => $user_id,
            ':uname' => $username,
            ':pass' => $password_hash
        ]);

        $conn->commit();

        echo "<script>
                alert('Registration successful. Please proceed to login.');
                window.location='index.php';
              </script>";
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        echo "<script>
                alert('Registration failed: " . $e->getMessage() . "');
              </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>User Registration</title>
<style>
body { font-family: Arial, sans-serif; background:#fff; display:flex; align-items:center; justify-content:center; height:100vh; margin:0;}
form {background:#f8f9fa; padding:35px 45px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.1); width:370px; text-align:center;}
h2 {color:#333; margin-bottom:25px;}
input {width:90%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:8px; font-size:15px;}
input:focus {border-color:#0984e3; outline:none; box-shadow:0 0 8px rgba(9,132,227,0.4);}
.error {color:red; font-size:13px; text-align:left; width:90%; margin:auto;}
.strength {font-size:13px; color:#555;}
button {width:95%; background:#0984e3; color:white; border:none; padding:12px; font-size:16px; border-radius:8px; cursor:pointer; margin-top:10px;}
button:hover {background:#74b9ff;}
a {text-decoration:none; color:#0984e3; display:inline-block; margin-top:15px;}
a:hover {text-decoration:underline;}
</style>
</head>
<body>

<form method="POST" id="registerForm">
    <h2>Create Account</h2>

    <input type="text" name="full_name" id="full_name" placeholder="Full Name" required>
    <div class="error" id="nameError"></div>

    <input type="email" name="email" id="email" placeholder="Email Address" required>
    <div class="error" id="emailError"></div>

    <input type="text" name="phone" id="phone" placeholder="Phone Number">
    <div class="error" id="phoneError"></div>

    <input type="text" name="username" id="username" placeholder="Username" required>
    <div class="error" id="usernameError"></div>

    <input type="password" name="password" id="password" placeholder="Password" required>
    <div class="strength" id="passwordStrength"></div>

    <button type="submit" name="register">Sign Up</button>
    <a href="index.php">Already have an account? Login</a>
</form>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const form = document.getElementById("registerForm");
    const email = document.getElementById("email");
    const password = document.getElementById("password");
    const name = document.getElementById("full_name");
    const username = document.getElementById("username");
    const phone = document.getElementById("phone");
    const emailError = document.getElementById("emailError");
    const nameError = document.getElementById("nameError");
    const usernameError = document.getElementById("usernameError");
    const phoneError = document.getElementById("phoneError");
    const passwordStrength = document.getElementById("passwordStrength");

    email.addEventListener("input", () => {
        const pattern = /^[^ ]+@[^ ]+\.[a-z]{2,3}$/;
        emailError.textContent = !email.value.match(pattern) ? "Please enter a valid email address." : "";
    });

    name.addEventListener("input", () => {
        nameError.textContent = name.value.length < 3 ? "Name must be at least 3 characters." : "";
    });

    username.addEventListener("input", () => {
        usernameError.textContent = username.value.length < 3 ? "Username must be at least 3 characters." : "";
    });

    phone.addEventListener("input", () => {
        const phonePattern = /^[0-9]{10,15}$/;
        phoneError.textContent = phone.value && !phone.value.match(phonePattern) ? "Enter a valid phone number (10–15 digits)." : "";
    });

    password.addEventListener("input", () => {
        const value = password.value;
        let strength = "";
        if (value.length < 6) {
            strength = "Weak password. Use at least 6 characters.";
            passwordStrength.style.color = "red";
        } else if (value.match(/[A-Z]/) && value.match(/[0-9]/) && value.match(/[^A-Za-z0-9]/)) {
            strength = "Strong password.";
            passwordStrength.style.color = "green";
        } else {
            strength = "Medium strength password.";
            passwordStrength.style.color = "orange";
        }
        passwordStrength.textContent = strength;
    });

    form.addEventListener("submit", function(event) {
        if (emailError.textContent || nameError.textContent || usernameError.textContent || phoneError.textContent) {
            event.preventDefault();
        }
    });
});
</script>

</body>
</html>
