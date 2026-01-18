<?php
session_start();
include('db_connect.php');

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// ✅ Fetch AI estimations with item details and image
$stmt = $conn->prepare("
    SELECT i.item_id, i.item_title, i.item_brand, img.item_image_path, 
           e.ai_estimation_price_min, e.ai_estimation_price_max, 
           e.ai_estimation_confidence, e.ai_estimation_id
    FROM ai_estimations e
    JOIN items i ON i.item_id = e.ai_estimation_item_id
    LEFT JOIN item_images img ON img.item_image_item_id = i.item_id
    ORDER BY e.ai_estimation_id DESC
");
$stmt->execute();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Valuation Reports</title>
<style>
    body { 
        font-family: Arial, sans-serif; 
        background: #f4f4f4; 
        margin: 0;
        padding: 0;
    }
    h2 { 
        text-align: center; 
        color: #333; 
        margin-top: 20px;
    }

    /* Modern Sticky Navbar */
    .navbar {
        position: sticky;
        top: 0;
        z-index: 999;
        background-color: #007bff;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 20px;
        color: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .navbar .logo {
        font-weight: bold;
        font-size: 18px;
    }
    .navbar .nav-links a {
        color: white;
        text-decoration: none;
        margin-left: 15px;
        font-weight: bold;
    }
    .navbar .nav-links a:hover {
        text-decoration: underline;
    }

    /* Back to Dashboard Button in Navbar */
    .back-btn {
        display: inline-block;
        margin-left: 20px;
        padding: 8px 18px;
        border-radius: 5px;
        background: #28a745;
        color: white;
        text-decoration: none;
        font-weight: bold;
        transition: background 0.3s;
    }
    .back-btn:hover {
        background: #1e7e34;
    }

    table { 
        width: 95%; 
        border-collapse: collapse; 
        background: #fff; 
        box-shadow: 0 0 10px rgba(0,0,0,0.05);
        margin: 20px auto;
    }
    th, td { 
        border: 1px solid #ccc; 
        padding: 10px; 
        text-align: center; 
    }
    th { 
        background: #007bff; 
        color: white;
    }
    img { 
        max-width: 100px; 
        border-radius: 5px; 
    }

    /* Responsive Navbar */
    @media (max-width: 768px) {
        .navbar { flex-direction: column; align-items: flex-start; }
        .navbar .nav-links a, .back-btn { margin: 5px 0; }
    }
</style>
</head>
<body>

<!-- Modern Sticky Navbar with Back Button -->
<div class="navbar">
    <div class="logo">UsedItem Estimator</div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
        <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    </div>
</div>

<h2>Valuation Reports</h2>
<table>
<tr>
    <th>Item</th>
    <th>Brand</th>
    <th>Image</th>
    <th>Estimated Price</th>
    <th>Confidence</th>
    <th>Estimation ID</th>
</tr>
<?php
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>
            <td>" . htmlspecialchars($r['item_title']) . "</td>
            <td>" . htmlspecialchars($r['item_brand']) . "</td>
            <td>";
    if ($r['item_image_path']) {
        echo "<img src='" . htmlspecialchars($r['item_image_path']) . "' alt='Item Image'>";
    } else {
        echo "No Image";
    }
    echo "</td>
            <td>$" . htmlspecialchars($r['ai_estimation_price_min']) . " - $" . htmlspecialchars($r['ai_estimation_price_max']) . "</td>
            <td>" . htmlspecialchars($r['ai_estimation_confidence']) . "%</td>
            <td>" . htmlspecialchars($r['ai_estimation_id']) . "</td>
          </tr>";
}
?>
</table>

</body>
</html>


