<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['item_id'])) die("No item specified.");
$item_id = (int)$_GET['item_id'];

try {
    $conn = new PDO("pgsql:host=localhost;dbname=used_item_value_estimator", "postgres", "BQfa2050*");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Fetch item info
$stmt = $conn->prepare("SELECT item_title, item_brand FROM items WHERE item_id = :item_id");
$stmt->execute([':item_id' => $item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) die("Item not found.");

// Helper functions
function fetchHTML($url) {
    $opts = ["http" => ["header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 15]];
    return @file_get_contents($url, false, stream_context_create($opts)) ?: '';
}

function cleanPrice($text) {
    $text = str_replace([',', 'KES', 'Sh', '$'], '', $text);
    preg_match_all('/\d+(\.\d+)?/', $text, $matches);
    if (empty($matches[0])) return 0;
    if (count($matches[0]) > 1) {
        return (floatval($matches[0][0]) + floatval($matches[0][1])) / 2;
    }
    return floatval($matches[0][0]);
}

function calculateStats(array $prices): array {
    if (empty($prices)) return ['min'=>0,'max'=>0,'avg'=>0,'median'=>0,'variance'=>0];
    sort($prices);
    $count = count($prices);
    $median = $prices[(int)($count/2)];
    $prices = array_filter($prices, fn($p) => $p > 0 && $p <= 2*$median);
    if (empty($prices)) return ['min'=>0,'max'=>0,'avg'=>0,'median'=>0,'variance'=>0];
    sort($prices);
    $min = min($prices); $max = max($prices);
    $avg = array_sum($prices)/count($prices);
    $median = $prices[(int)(count($prices)/2)];
    $variance = array_sum(array_map(fn($p)=>pow($p-$avg,2),$prices))/count($prices);
    return compact('min','max','avg','median','variance');
}

// Scrapers
function scrapeJumia($query) {
    $url = "https://www.jumia.co.ke/catalog/?q=" . urlencode($query);
    $html = fetchHTML($url);
    if (!$html) return [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("//article[contains(@class,'prd') or contains(@class,'c-prd')]");
    $products = [];
    foreach ($nodes as $node) {
        $name = $xpath->evaluate("string(.//h3[contains(@class,'name')])", $node);
        $price = cleanPrice($xpath->evaluate("string(.//*[contains(@class,'prc')])", $node));
        $imgNode = $xpath->query(".//img[contains(@class,'img')]", $node);
        $img = $imgNode->length ? ($imgNode->item(0)->getAttribute('data-src') ?: $imgNode->item(0)->getAttribute('src')) : '';
        $linkNode = $xpath->query(".//a[contains(@class,'core')]", $node);
        $link = $linkNode->length ? 'https://www.jumia.co.ke'.$linkNode->item(0)->getAttribute('href') : '';
        if ($name && $price>0) $products[] = ['name'=>$name,'price'=>$price,'image'=>$img,'link'=>$link];
    }
    return $products;
}

function scrapeEbay($query) {
    $url = "https://www.ebay.com/sch/i.html?_nkw=" . urlencode($query);
    $html = fetchHTML($url);
    if (!$html) return [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("//li[contains(@class,'s-item')]");
    $products = [];
    foreach ($nodes as $node) {
        $name = $xpath->evaluate("string(.//h3)", $node);
        $price = cleanPrice($xpath->evaluate("string(.//span[contains(@class,'s-item__price')])", $node));
        $imgNode = $xpath->query(".//img[contains(@class,'s-item__image-img')]", $node);
        $img = $imgNode->length ? $imgNode->item(0)->getAttribute('src') : '';
        $linkNode = $xpath->query(".//a[contains(@class,'s-item__link')]", $node);
        $link = $linkNode->length ? $linkNode->item(0)->getAttribute('href') : '';
        if ($name && $price>0) $products[] = ['name'=>$name,'price'=>$price,'image'=>$img,'link'=>$link];
    }
    return $products;
}

function scrapeOLX($query) {
    $url = "https://www.olx.co.ke/items/q-" . urlencode($query);
    $html = fetchHTML($url);
    if (!$html) return [];
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query("//li[contains(@class,'EIR5N')]");
    $products = [];
    foreach ($nodes as $node) {
        $name = $xpath->evaluate("string(.//h6)", $node);
        $price = cleanPrice($xpath->evaluate("string(.//span[contains(@data-testid,'ad-price')])", $node));
        $imgNode = $xpath->query(".//img", $node);
        $img = $imgNode->length ? $imgNode->item(0)->getAttribute('src') : '';
        $linkNode = $xpath->query(".//a", $node);
        $link = $linkNode->length ? $linkNode->item(0)->getAttribute('href') : '';
        if ($name && $price>0) $products[] = ['name'=>$name,'price'=>$price,'image'=>$img,'link'=>$link];
    }
    return $products;
}

// Merge sources
$allProducts = array_merge(scrapeJumia($item['item_title']), scrapeEbay($item['item_title']), scrapeOLX($item['item_title']));

// Price stats
$validPrices = array_filter(array_column($allProducts,'price'), fn($p)=>$p>0);
$stats = calculateStats($validPrices);
$confidence = count($validPrices)>0 ? min(95, max(60, 100 - ($stats['variance']/$stats['avg']*50))) : 0;
$depreciation = $stats['avg']>0 ? rand(15,35) : 0;
$marketSnapshot = json_encode($allProducts);

// --- START MISTRAL AI PART ---
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mistral.ai/v1/chat/completions");  
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer UWf89qiZ23zt2W5kIs9pn3XJPHSIuQeg",
    "Content-Type: application/json"
]);

$payload = json_encode([
    "model" => "mistral-tiny-latest",
    "messages" => [
        [
            "role" => "user",
            "content" => 
                "You are an AI pricing expert. Using this data, write a short but clear AI explanation:\n\n".
                "Item: {$item['item_title']} ({$item['item_brand']})\n".
                "Min Price: {$stats['min']}\n".
                "Max Price: {$stats['max']}\n".
                "Average Price: {$stats['avg']}\n".
                "Median Price: {$stats['median']}\n".
                "Variance: {$stats['variance']}\n".
                "Number of scraped listings: ".count($allProducts)."\n\n".
                "Generate a human-friendly explanation about the estimated fair price."
        ]
    ]
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
$response = curl_exec($ch);
curl_close($ch);

$aiJson = json_decode($response, true);
if(isset($aiJson['choices'][0]['message']['content'])){
    $ai_notes = $aiJson['choices'][0]['message']['content'];
} else {
    $ai_notes = "AI summary not available. Based on " . count($validPrices) . " listings.";
}
// --- END MISTRAL AI PART ---

// Insert AI estimation
$stmt2 = $conn->prepare("INSERT INTO ai_estimations 
(ai_estimation_item_id, ai_estimation_price_min, ai_estimation_price_max, ai_estimation_confidence, ai_estimation_depreciation, ai_estimation_market_snapshot, ai_estimation_notes) 
VALUES (:id,:min,:max,:conf,:dep,:snap,:notes)");
$stmt2->execute([
    ':id'=>$item_id, ':min'=>$stats['min'], ':max'=>$stats['max'],
    ':conf'=>$confidence, ':dep'=>$depreciation,
    ':snap'=>$marketSnapshot, ':notes'=>$ai_notes
]);

$ai_estimation_id = $conn->lastInsertId();

// Insert notification
$notif_stmt = $conn->prepare("
    INSERT INTO notifications (notification_user_id, notification_title, notification_message, notification_type)
    VALUES (:user_id, :title, :message, :type)
");
$notif_stmt->execute([
    ':user_id' => $user_id,
    ':title' => 'AI Estimation Completed',
    ':message' => "Your item '{$item['item_title']}' has been estimated successfully!",
    ':type' => 'estimation_success'
]);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>AI Estimation</title>
<style>
body{font-family:'Segoe UI',Arial,sans-serif;background:#f4f7fb;margin:0;padding:0;text-align:center}
.navbar{position:sticky;top:0;z-index:999;background:linear-gradient(90deg,#007bff,#00bfff);display:flex;justify-content:space-between;align-items:center;padding:10px 20px;color:white;box-shadow:0 4px 12px rgba(0,0,0,0.3);transition:all 0.3s;}
.navbar .logo{font-weight:bold;font-size:20px;text-shadow:1px 1px 2px rgba(0,0,0,0.3);}
.navbar .nav-links a{color:white;text-decoration:none;margin-left:15px;font-weight:bold;padding:6px 12px;border-radius:5px;transition:all 0.3s;}
.navbar .nav-links a:hover{background:rgba(255,255,255,0.2);}
.navbar .back-btn{padding:8px 18px;border-radius:5px;background:#28a745;color:white;text-decoration:none;font-weight:bold;transition:background 0.3s}
.navbar .back-btn:hover{background:#1e7e34;}
.notification{position:relative;display:inline-block;cursor:pointer;margin-left:20px}
.notification .bell{font-size:20px}
.notification .count{position:absolute;top:-6px;right:-10px;background:red;color:white;font-size:12px;padding:2px 6px;border-radius:50%}
.notification .dropdown{display:none;position:absolute;right:0;background:white;color:#333;min-width:300px;max-height:400px;overflow-y:auto;box-shadow:0 2px 10px rgba(0,0,0,0.2);border-radius:5px;z-index:1000}
.notification .dropdown .item{padding:10px;border-bottom:1px solid #ddd}
.notification .dropdown .item.unread{background:#e9f7ff}
.notification .dropdown .item:last-child{border-bottom:0}
.notification .dropdown .item:hover{background:#f1f1f1}
.card{background:#fff;padding:30px;margin:40px auto;width:95%;max-width:1000px;border-radius:15px;box-shadow:0 6px 20px rgba(0,0,0,.15);transition:all 0.3s;}
.card:hover{box-shadow:0 8px 25px rgba(0,0,0,.25);}
.price{font-size:22px;color:#007bff;font-weight:bold}
.scraped-products{margin-top:20px;overflow-x:auto;}
.scraped-products table{width:100%;border-collapse:collapse;margin-top:10px}
.scraped-products th,.scraped-products td{border:1px solid #ddd;padding:8px;text-align:center}
.scraped-products th{background:#007bff;color:#fff}
.scraped-products td a{color:#007bff;text-decoration:none}
.scraped-products td a:hover{text-decoration:underline}
/* Search bar */
.search-bar{padding:6px 12px;border-radius:8px;border:1px solid #ccc;width:220px;margin-left:15px;transition:all 0.3s;}
.search-bar:focus{outline:none;border-color:#007bff;box-shadow:0 0 8px rgba(0,123,255,0.5);}
.search-button{padding:6px 12px;border-radius:8px;border:none;background:#007bff;color:white;cursor:pointer;transition:all 0.3s;margin-left:5px;}
.search-button:hover{background:#0056b3;}
</style>
</head>
<body>

<div class="navbar">
    <div class="logo">Snapit AI Estimate</div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
        <a href="dashboard.php" class="back-btn">← Back</a>

        <form action="upload_item.php" method="get" style="display:inline;">
    <input type="text" name="q" placeholder="Search item..." class="search-bar" required>
    <button type="submit" class="search-button">🔍</button>
</form>


        <div class="notification" id="notifBell">
            <span class="bell">🔔</span>
            <span class="count" id="notifCount">0</span>
            <div class="dropdown" id="notifDropdown"></div>
        </div>
    </div>
</div>

<div class="card">
<h2>AI Estimation Complete</h2>
<h3><?=htmlspecialchars($item['item_title'])?> (<?=htmlspecialchars($item['item_brand'])?>)</h3>
<p class="price">KES <?=number_format($stats['min'],2)?> – KES <?=number_format($stats['max'],2)?></p>
<p><strong>Average:</strong> <?=number_format($stats['avg'],2)?> | <strong>Median:</strong> <?=number_format($stats['median'],2)?></p>
<p><strong>Confidence:</strong> <?=number_format($confidence,1)?>% | <strong>Depreciation:</strong> <?=number_format($depreciation,1)?>%</p>
<p><em><?=$ai_notes?></em></p>

<?php if(!empty($allProducts)): ?>
<div class="scraped-products">
<h4>Scraped Product Listings</h4>
<table>
<tr><th>#</th><th>Image</th><th>Product Name</th><th>Price (KES)</th></tr>
<?php foreach($allProducts as $i=>$p): ?>
<tr>
<td><?=($i+1)?></td>
<td>
<?php if(!empty($p['image'])): ?>
<img src="<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['name'])?>" style="width:80px;height:auto;border-radius:5px;">
<?php else: ?>N/A<?php endif; ?>
</td>
<td>
<?php if(!empty($p['link'])): ?>
<a href="<?=htmlspecialchars($p['link'])?>" target="_blank"><?=htmlspecialchars($p['name'])?></a>
<?php else: ?>
<?=htmlspecialchars($p['name'])?>
<?php endif; ?>
</td>
<td><?=number_format($p['price'],2)?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>
</div>

<script>
const bell = document.getElementById('notifBell');
const dropdown = document.getElementById('notifDropdown');
bell.addEventListener('click', () => {
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
});

function fetchNotifications() {
    fetch('fetch_notifications.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById('notifCount').textContent = data.unread_count;
            const dd = document.getElementById('notifDropdown');
            dd.innerHTML = '';
            if(data.notifications.length === 0){
                dd.innerHTML = '<div class="item">No notifications</div>';
            } else {
                data.notifications.forEach(n => {
                    const div = document.createElement('div');
                    div.className = 'item' + (n.notification_is_read === "f" ? ' unread' : '');
                    div.innerHTML = `<strong>${n.notification_title}</strong><br>${n.notification_message}<br><small>${n.notification_created_at}</small>`;
                    dd.appendChild(div);
                });
            }
        });
}
fetchNotifications();
setInterval(fetchNotifications, 5000);
</script>

</body>
</html>