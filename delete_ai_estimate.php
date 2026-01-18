<?php
session_start();

// Redirect if not admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['success_message'] = "Invalid request: No ID provided.";
    header("Location: ai_performance_dashboard.php");
    exit;
}

$estimation_id = (int) $_GET['id'];

// ===== Database Connection =====
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Delete the AI estimation
    $stmt = $pdo->prepare("DELETE FROM ai_estimations WHERE ai_estimation_id = :id");
    $stmt->bindParam(':id', $estimation_id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['success_message'] = "Estimation deleted successfully!";
    header("Location: ai_performance_dashboard.php");
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
