<?php
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$user = "postgres";
$pass = "BQfa2050*";

try {
    $conn = new PDO($dsn, $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
