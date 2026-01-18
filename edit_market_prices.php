<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

try {
    $pdo = new PDO("pgsql:host=localhost;dbname=used_item_value_estimator", "postgres", "BQfa2050*");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Handle update submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['item_id'], $_POST['market_price'])) {
    $update = $pdo->prepare("UPDATE items SET market_price = :price WHERE item_id = :id");
    $update->execute([
        ':price' => $_POST['market_price'],
        ':id' => $_POST['item_id']
    ]);
    header("Location: edit_market_prices.php");
    exit;
}

// Fetch all items
$stmt = $pdo->query("SELECT item_id, item_title, item_brand, market_price FROM items ORDER BY item_id DESC");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Market Prices</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
<h2>Edit Market Prices</h2>
<table class="table table-bordered">
<thead>
<tr>
<th>#</th>
<th>Item</th>
<th>Current Market Price (KES)</th>
<th>Update Price</th>
<th>Action</th>
</tr>
</thead>
<tbody>
<?php foreach ($items as $i => $item): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($item['item_title'].' ('.$item['item_brand'].')') ?></td>
<td><?= $item['market_price'] ? number_format($item['market_price'],2) : 'N/A' ?></td>
<td>
<form method="post" style="display:inline-block;">
<input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">
<input type="number" step="0.01" name="market_price" value="<?= $item['market_price'] ?>" class="form-control" required>
</td>
<td>
<button type="submit" class="btn btn-primary btn-sm">Update</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<a href="admin_dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
</div>
</body>
</html>
