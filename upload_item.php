<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

try {
    $pdo = new PDO("pgsql:host=localhost;dbname=used_item_value_estimator", "postgres", "BQfa2050*");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

try {
    $categoryStmt = $pdo->query("SELECT item_category_id, item_category_name FROM item_category ORDER BY item_category_id");
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Failed to fetch categories: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $item_name = $_POST['item_name'];
    $brand = $_POST['brand'];
    $category = $_POST['category'];
    $purchase_year = $_POST['purchase_year'];
    $condition = $_POST['condition'];
    $description = $_POST['description'];

    $image_path = null;
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $image_path = $target_dir . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO items (item_user_id, item_category_id, item_title, item_brand, item_purchase_year, item_condition_code, item_description)
            VALUES (:user_id, :category, :title, :brand, :year, :condition, :description)
            RETURNING item_id
        ");
        $stmt->execute([
            ':user_id' => $user_id,
            ':category' => $category,
            ':title' => $item_name,
            ':brand' => $brand,
            ':year' => $purchase_year ?: null,
            ':condition' => $condition,
            ':description' => $description
        ]);
        $item_id = $stmt->fetchColumn();

        if ($image_path) {
            $imgStmt = $pdo->prepare("
                INSERT INTO item_images (item_image_item_id, item_image_path)
                VALUES (:item_id, :path)
            ");
            $imgStmt->execute([
                ':item_id' => $item_id,
                ':path' => $image_path
            ]);
        }

        header("Location: ai_estimate.php?item_id=" . $item_id);
        exit;

    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>SnapIt Item Upload</title>
<style>
body {
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: white;
    margin: 0;
    padding: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    color: #00334e;
}
.container {
    background: white;
    padding: 40px;
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    width: 450px;
    animation: fadeIn 0.8s ease-in-out;
    text-align: center;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
h1 { font-size: 40px; font-weight: 800; color: darkblue; margin-bottom: 5px; }
p.subtitle { font-size: 15px; color: #005f73; margin-bottom: 25px; font-style: italic; }
input, select, textarea { width: 100%; padding: 12px; margin-top: 10px; border: 1px solid #bcdff5; border-radius: 8px; outline: none; transition: 0.3s; font-size: 15px; }
input:focus, select:focus, textarea:focus { border-color: darkblue; box-shadow: 0 0 8px rgba(0, 150, 199, 0.3); }
.button-row { display: flex; justify-content: space-between; margin-top: 20px; }
.button-row button { width: 48%; padding: 14px; background: darkblue; border: none; color: white; font-size: 16px; border-radius: 10px; cursor: pointer; transition: 0.3s; }
.button-row button:hover { transform: scale(1.03); }
#submitBtn { background: #28a745; margin-top: 20px; }
#submitBtn:hover { background: #218838; }
#camera { width:100%; border-radius:10px; display:none; margin-top:10px; }
.image-preview { margin-top: 10px; max-width: 100%; border-radius: 10px; display: none; }
.error { color: red; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
<h1>SnapIt</h1>
<p class="subtitle">Estimate your item successfully with accuracy and confidence.</p>

<?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>

<form method="POST" enctype="multipart/form-data">

    <input type="text" name="item_name" placeholder="Item Name" required>
    <input type="text" name="brand" placeholder="Brand">

    <select name="category" required>
        <option value="">-- Select Category --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['item_category_id'] ?>"><?= htmlspecialchars($cat['item_category_name']) ?></option>
        <?php endforeach; ?>
    </select>

    <input type="number" name="purchase_year" placeholder="Purchase Year">

    <select name="condition" required>
        <option value="New">New</option>
        <option value="Used">Used</option>
        <option value="Fair">Fair</option>
        <option value="Poor">Poor</option>
    </select>

    <textarea name="description" placeholder="Item Description"></textarea>

    <!-- Hidden file input for PHP submission -->
    <input type="file" name="image" id="fileInput" style="display:none" />

    <!-- Camera video -->
    <video id="camera" autoplay></video>
    <canvas id="canvas" style="display:none;"></canvas>

    <!-- Buttons -->
    <div class="button-row">
        <button type="button" onclick="document.getElementById('fileInput').click()">Choose from Gallery</button>
        <button type="button" onclick="startCamera()">Take Photo</button>
    </div>

    <img id="imgPreview" class="image-preview" />

    <button type="submit" id="submitBtn">Submit</button>
</form>
</div>

<script>
const video = document.getElementById('camera');
const canvas = document.getElementById('canvas');
const fileInput = document.getElementById('fileInput');

function startCamera() {
    video.style.display = 'block';
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
    .then(stream => { video.srcObject = stream; })
    .catch(err => { alert("Camera not available: " + err); });

    // Replace "Take Photo" button behavior to capture
    document.querySelectorAll('.button-row button')[1].textContent = "Capture Photo";
    document.querySelectorAll('.button-row button')[1].onclick = capturePhoto;
}

function capturePhoto() {
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    const dataURL = canvas.toDataURL('image/png');

    // Show preview
    const preview = document.getElementById('imgPreview');
    preview.src = dataURL;
    preview.style.display = 'block';

    // Convert to file for PHP submission
    fetch(dataURL)
    .then(res => res.blob())
    .then(blob => {
        const file = new File([blob], "camera_photo.png", { type: "image/png" });
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
    });

    // Stop camera
    video.srcObject.getTracks().forEach(track => track.stop());
    video.style.display = 'none';
    document.querySelectorAll('.button-row button')[1].textContent = "Take Photo";
    document.querySelectorAll('.button-row button')[1].onclick = startCamera;
}
</script>
</body>
</html>
