<?php
// Generate a password hash
$password = "admin123";
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password Hash: " . $hash;
?>