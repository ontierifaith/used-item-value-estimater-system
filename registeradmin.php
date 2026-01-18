<?php
// Configuration for PostgreSQL Database
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $admin_username = $_POST['admin_username'];
    $admin_password = $_POST['admin_password'];

    // 1. Check if username already exists (Optional but recommended)
    $stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE admin_username = :username");
    $stmt->execute(['username' => $admin_username]);
    if ($stmt->fetch()) {
        $message = "Error: Username already exists.";
    } else {
        // 2. IMPORTANT: Create the secure password hash
        // The password_verify function in your login script REQUIRES this
        $admin_password_hash = password_hash($admin_password, PASSWORD_DEFAULT);

        try {
            // 3. Insert the new user with the HASHED password
            $stmt = $pdo->prepare(
                "INSERT INTO admins (admin_username, admin_password_hash) VALUES (:username, :password_hash)"
            );
            $stmt->execute([
                'username' => $admin_username,
                'password_hash' => $admin_password_hash
            ]);
            $message = "Success! Admin user **$admin_username** registered. You can now log in.";
        } catch (PDOException $e) {
            $message = "Database error during insertion: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Registration</title>
<style>
    .message { padding: 10px; margin: 10px 0; border: 1px solid; }
    .success { border-color: green; color: green; background-color: #e6ffe6; }
    .error { border-color: red; color: red; background-color: #ffe6e6; }
</style>
</head>
<body>
<h2>Admin Registration (Use Once)</h2>

<?php if (!empty($message)) {
    $class = (strpos($message, 'Success') !== false) ? 'success' : 'error';
    echo "<p class='message $class'>$message</p>";
} ?>

<form method="post">
    <label>New Username:</label>
    <input type="text" name="admin_username" required><br><br>
    <label>New Password:</label>
    <input type="password" name="admin_password" required><br><br>
    <button type="submit">Register Admin</button>
</form>

<p>After successful registration, you can delete this file or remove its contents.</p>
</body>
</html>