<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// DB Connection
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Validate ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid estimation ID.");
}

$estimation_id = $_GET['id'];

// Fetch Estimation Data
$stmt = $pdo->prepare("
    SELECT 
        ai.ai_estimation_id,
        ai.ai_estimation_price_min,
        ai.ai_estimation_price_max,
        ai.ai_estimation_confidence,
        ai.ai_estimation_depreciation,
        ai.ai_estimation_notes,
        i.item_title,
        i.item_brand
    FROM ai_estimations ai
    JOIN items i ON ai.ai_estimation_item_id = i.item_id
    WHERE ai.ai_estimation_id = :id
");
$stmt->execute(['id' => $estimation_id]);
$estimation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$estimation) {
    die("Estimation not found.");
}

// Update Handler
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $price_min = $_POST['price_min'];
    $price_max = $_POST['price_max'];
    $confidence = $_POST['confidence'];
    $depreciation = $_POST['depreciation'];
    $notes = $_POST['notes'];

    $update = $pdo->prepare("
        UPDATE ai_estimations 
        SET 
            ai_estimation_price_min = :min,
            ai_estimation_price_max = :max,
            ai_estimation_confidence = :conf,
            ai_estimation_depreciation = :dep,
            ai_estimation_notes = :notes
        WHERE ai_estimation_id = :id
    ");

    $update->execute([
        'min' => $price_min,
        'max' => $price_max,
        'conf' => $confidence,
        'dep' => $depreciation,
        'notes' => $notes,
        'id' => $estimation_id
    ]);

    header("Location: ai_performance_dashboard.php?updated=1");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit AI Estimation</title>
    <style>
        body { font-family: Arial; background: #f4f6f9; padding: 20px; }
        .form-box { background: #fff; padding: 25px; width: 500px; margin: auto; border-radius: 10px;
                    box-shadow: 0 3px 10px rgba(0,0,0,0.2); }
        label { font-weight: bold; }
        input, textarea { width: 100%; padding: 10px; margin-top: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 5px; }
        button { padding: 10px 20px; background: #28a745; color: white; border: none; border-radius: 6px; cursor: pointer; }
        a.back { display: inline-block; margin-top: 10px; background: #007bff; padding: 8px 15px; color: #fff; border-radius: 6px; text-decoration: none; }
    </style>
</head>
<body>

<div class="form-box">
    <h2>Edit AI Estimate for: <?= htmlspecialchars($estimation['item_title']) ?> (<?= htmlspecialchars($estimation['item_brand']) ?>)</h2>

    <form method="POST">
        <label>Minimum Price</label>
        <input type="number" step="0.01" name="price_min" value="<?= $estimation['ai_estimation_price_min'] ?>" required>

        <label>Maximum Price</label>
        <input type="number" step="0.01" name="price_max" value="<?= $estimation['ai_estimation_price_max'] ?>" required>

        <label>Confidence (%)</label>
        <input type="number" name="confidence" value="<?= $estimation['ai_estimation_confidence'] ?>">

        <label>Depreciation (%)</label>
        <input type="number" name="depreciation" value="<?= $estimation['ai_estimation_depreciation'] ?>">

        <label>Notes</label>
        <textarea name="notes" rows="3"><?= htmlspecialchars($estimation['ai_estimation_notes']) ?></textarea>

        <button type="submit">Save Changes</button>
    </form>

    <a href="manage_ai_estimates.php" class="back">← Back</a>
</div>

</body>
</html>
