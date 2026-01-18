<?php
try {
    $admin_conn = new PDO("pgsql:host=localhost;dbname=admin_system", "postgres", "BQfa2050*");
    $admin_conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Admin database connection failed: " . $e->getMessage());
}
?>
