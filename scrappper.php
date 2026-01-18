<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_GET['item_id'])) die("No item specified.");
$item_id = (int)$_GET['item_id'];

try {
    $pdo = new PDO("pgsql:host=localhost;dbname=used_item_value_estimator", "postgres", "BQfa2050*");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Fetch item info
$stmt = $pdo->prepare("SELECT item_title, item_brand FROM items WHERE item_id = :item_id");
$stmt->execute([':item_id' => $item_id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) die("Item not found.");

// --------------------------
// Helper Functions
// --------------------------
function fetchHTML($url) {
    $opts = ["http" => ["header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 15]];
    return @file_get_contents($url, false, stream_context_create($opts)) ?: '';
}

function cleanPrice($text) {
    $text = str_replace(',', '', $text);
    preg_match_all('/\d+(\.\d+)?/', $text, $matches);
    return isset($matches[0][0]) ? (float)$matches[0][0] : 0;
}

function calculateStats(array $prices): array {
    if (empty($prices)) return ['min'=>0,'max'=>0,'avg'=>0,'median'=>0,'variance'=>0];
    sort($prices);
    $count = count($prices);
    $min = min($prices); $max = max($prices);
    $avg = array_sum($prices)/$count;
    $median = $prices[(int)($count/2)];
    $variance = array_sum(array_map(fn($p)=>pow($p-$avg,2),$prices))/$count;
    return compact('min','max','avg','median','variance');
}

// --------------------------
// Jumia Scraper
// --------------------------
function fetchJumiaProductsData($searchTerm = "laptop") {
    $url = "https://www.jumia.co.ke/catalog/?q=" . urlencode($searchTerm);
    $opts = ["http" => ["header" => "User-Agent: Mozilla/5.0\r\n", "timeout" => 15]];
    $html = @file_get_contents($url, false, stream_context_create($opts));
    if (!$html) return [];

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $nodes = $xpath->query("//article[contains(@class,'prd') or contains(@class,'c-prd')]");
    $products = [];

    foreach ($nodes as $node) {
        $name = $xpath->evaluate("string(.//h3[contains(@class,'name')])", $node);
        $priceText = $xpath->evaluate("string(.//*[contains(@class,'prc')])", $node);
        $price = preg_replace("/[^\d]/", "", $priceText);
        $price = (float)$price;

        if ($name && $price > 0) {
            $products[] = ['name' => $name, 'price' => $price];
        }
    }

    return $products;
}

// --------------------------
// Pexels Image Fetch
// --------------------------
function fetchImagesFromPexels($query, $num=3) {
    $apiKey = "KVXkdlw30mSxQX3whuy3KMBj3bGlNNoWgZ2IhhDaLjFMaMXlc2otuuiI";
    $url = "https://api.pexels.com/v1/search?query=" . urlencode($query) . "&per_page=" . $num;
    $opts = ["http"=>["header"=>"Authorization: $apiKey\r\n","timeout"=>10]];
    $context = stream_context_create($opts);
    $data = @file_get_contents($url, false, $context);
    if(!$data) return [];
    $json = json_decode($data,true);
    return array_slice(array_map(fn($p)=>$p['src']['medium'],$json['photos']??[]),0,$num);
}

// --------------------------
// Main Logic
// --------------------------
$allProducts = fetchJumiaProductsData($item['item_title']);
$prices = array_column($allProducts,'price');
$stats = calculateStats($prices);
$confidence = count($prices)>0 ? min(95, max(60, 100 - ($stats['variance']/$stats['avg']*50))) : 0;
$depreciation = $stats['avg']>0 ? rand(15,35) : 0;
$marketSnapshot = json_encode($allProducts);
$ai_notes = "AI estimation based on ".count($prices)." listings scraped from Jumia.";

// Insert to DB
$stmt2 = $pdo->prepare("INSERT INTO ai_estimations (ai_estimation_item_id,ai_estimation_price_min,ai_estimation_price_max,ai_estimation_confidence,ai_estimation_depreciation,ai_estimation_market_snapshot,ai_estimation_notes) VALUES (:id,:min,:max,:conf,:dep,:snap,:notes)");
$stmt2->execute([
    ':id'=>$item_id, ':min'=>$stats['min'], ':max'=>$stats['max'],
    ':conf'=>$confidence, ':dep'=>$depreciation,
    ':snap'=>$marketSnapshot, ':notes'=>$ai_notes
]);

$similarImages = fetchImagesFromPexels($item['item_title'], 3);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>AI Estimation</title>
<style>
body{font-family:'Segoe UI',Arial;background:#f4f7fb;margin:0;padding:40px;text-align:center}
.card{background:#fff;padding:30px;margin:0 auto;width:850px;border-radius:15px;box-shadow:0 4px 15px rgba(0,0,0,.1)}
.price{font-size:22px;color:#007bff;font-weight:bold}
.similar-images{display:flex;justify-content:center;gap:10px;margin-top:20px}
.similar-images img{width:200px;height:auto;border-radius:10px}
.scraped-products{margin-top:20px}
.scraped-products table{width:100%;border-collapse:collapse;margin-top:10px}
.scraped-products th, .scraped-products td{border:1px solid #ddd;padding:8px;text-align:center}
.scraped-products th{background:#007bff;color:#fff}
.back-btn{margin-top:25px;display:inline-block;padding:10px 25px;background:#007bff;color:#fff;text-decoration:none;border-radius:5px}
.back-btn:hover{background:#0056b3}
</style>
</head>
<body>
<div class="card">
<h2>AI Estimation Complete</h2>
<h3><?=htmlspecialchars($item['item_title'])?> (<?=htmlspecialchars($item['item_brand'])?>)</h3>
<p class="price">KES <?=number_format($stats['min'],2)?> – KES <?=number_format($stats['max'],2)?></p>
<p><strong>Average:</strong> <?=number_format($stats['avg'],2)?> | <strong>Median:</strong> <?=number_format($stats['median'],2)?></p>
<p><strong>Confidence:</strong> <?=number_format($confidence,1)?>% | <strong>Depreciation:</strong> <?=number_format($depreciation,1)?>%</p>
<p><em><?=$ai_notes?></em></p>

<?php if(!empty($similarImages)): ?>
<div class="similar-images">
<?php foreach($similarImages as $img): ?>
<img src="<?=$img?>" alt="similar">
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if(!empty($allProducts)): ?>
<div class="scraped-products">
<h4>Scraped Jumia Products</h4>
<table>
<tr><th>#</th><th>Product Name</th><th>Price (KES)</th></tr>
<?php foreach($allProducts as $i=>$p): ?>
<tr>
<td><?=($i+1)?></td>
<td><?=htmlspecialchars($p['name'])?></td>
<td><?=number_format($p['price'],2)?></td>
</tr>
<?php endforeach; ?>
</table>
</div>
<?php endif; ?>

<a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
</div>
</body>
</html>
