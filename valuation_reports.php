<?php
// valuation_reports.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

try {
    $conn = new PDO("pgsql:host=localhost;dbname=used_item_value_estimator", "postgres", "BQfa2050*", [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("DB Error: " . htmlspecialchars($e->getMessage()));
}

// Fetch all estimations
$stmt = $conn->query("
    SELECT e.ai_estimation_id, e.ai_estimation_item_id, e.ai_estimation_price_min,
           e.ai_estimation_price_max, e.ai_estimation_confidence, e.ai_estimation_depreciation,
           e.ai_estimation_notes, e.ai_estimation_created_at, i.item_title
    FROM ai_estimations e
    JOIN items i ON e.ai_estimation_item_id = i.item_id
    ORDER BY e.ai_estimation_id DESC
");
$estimations = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Valuation Reports</title>
<style>
/* Body & Table */
body { font-family: Arial, sans-serif; background:#f9f9f9; padding:0; margin:0; }
h2 { color:#333; padding: 20px; }

/* Sticky Navbar */
.navbar {
    position: sticky;
    top: 0;
    z-index: 999;
    background-color: #007bff;
    padding: 10px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: white;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}
.navbar a {
    color: white;
    text-decoration: none;
    margin-left: 15px;
    font-weight: bold;
}
.navbar a:hover {
    text-decoration: underline;
}

/* Table */
table { border-collapse: collapse; width:90%; background:#fff; box-shadow:0 0 5px rgba(0,0,0,0.1); margin:20px auto; }
th, td { border:1px solid #ccc; padding:10px; text-align:left; }
th { background:#007bff; color:white; }
tr:hover { background:#f2f2f2; }

/* Responsive */
@media (max-width: 768px) {
    .navbar { flex-direction: column; align-items: flex-start; }
    .navbar a { margin: 5px 0; }
}
</style>
</head>
<body>

<!-- Sticky Modern Navbar -->
<div class="navbar">
    <div class="logo"><strong>UsedItem Estimator</strong></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="valuation_reports.php">Valuations</a>
        <a href="items.php">Items</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<h2>AI Valuation Reports</h2>

<?php if (empty($estimations)): ?>
    <p style="text-align:center;">No AI estimations found.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Item</th>
            <th>Min Price</th>
            <th>Max Price</th>
            <th>Confidence</th>
            <th>Depreciation (%)</th>
            <th>Notes</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($estimations as $e): ?>
        <tr>
            <td><?= htmlspecialchars($e['ai_estimation_id']) ?></td>
            <td><?= htmlspecialchars($e['item_title']) ?></td>
            <td>$<?= htmlspecialchars($e['ai_estimation_price_min']) ?></td>
            <td>$<?= htmlspecialchars($e['ai_estimation_price_max']) ?></td>
            <td><?= htmlspecialchars($e['ai_estimation_confidence']) ?>%</td>
            <td><?= htmlspecialchars($e['ai_estimation_depreciation']) ?></td>
            <td><?= htmlspecialchars($e['ai_estimation_notes']) ?></td>
            <td><?= htmlspecialchars($e['ai_estimation_created_at']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

</body>
</html>
