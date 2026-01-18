<?php
// train_ai.php — Self-learning retrainer for Used Item Value Estimator

$dsn = "pgsql:host=localhost;dbname=used_item_estimator";
$user = "postgres";
$password = "BQfa2050*";

try {
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "<h3>🔄 Training AI Model...</h3>";

    // 1️⃣ Fetch historical estimation data
    $stmt = $pdo->query("
        SELECT estimated_price, item_age, condition_score
        FROM ai_estimations
        WHERE estimated_price IS NOT NULL
    ");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($data) < 5) {
        die("<p>⚠️ Not enough data to train model (need at least 5 records).</p>");
    }

    // Prepare arrays for regression
    $X = [];
    $Y = [];

    foreach ($data as $row) {
        $age = (float)$row['item_age'];
        $cond = (float)$row['condition_score'];
        $price = (float)$row['estimated_price'];
        $X[] = [$age, $cond];
        $Y[] = $price;
    }

    // 2️⃣ Perform simple linear regression with 2 variables (age, condition)
    // Formula: price = b0 + b1*age + b2*condition

    $n = count($Y);
    $sumX1 = $sumX2 = $sumY = $sumX1Y = $sumX2Y = $sumX1X1 = $sumX2X2 = $sumX1X2 = 0;

    for ($i = 0; $i < $n; $i++) {
        $x1 = $X[$i][0];
        $x2 = $X[$i][1];
        $y = $Y[$i];
        $sumX1 += $x1;
        $sumX2 += $x2;
        $sumY += $y;
        $sumX1Y += $x1 * $y;
        $sumX2Y += $x2 * $y;
        $sumX1X1 += $x1 * $x1;
        $sumX2X2 += $x2 * $x2;
        $sumX1X2 += $x1 * $x2;
    }

    // Matrix calculations (manual least squares)
    $den = ($sumX1X1 * $sumX2X2) - ($sumX1X2 * $sumX1X2);
    if ($den == 0) die("<p>⚠️ Cannot compute regression: data too correlated.</p>");

    $b1 = (($sumX2X2 * $sumX1Y) - ($sumX1X2 * $sumX2Y)) / $den;
    $b2 = (($sumX1X1 * $sumX2Y) - ($sumX1X2 * $sumX1Y)) / $den;
    $b0 = ($sumY - $b1 * $sumX1 - $b2 * $sumX2) / $n;

    // 3️⃣ Clear old coefficients and insert new ones
    $pdo->exec("DELETE FROM ai_model_coefficients");

    $insert = $pdo->prepare("
        INSERT INTO ai_model_coefficients (feature_name, coefficient_value, model_version)
        VALUES (:feature, :value, 'v1.0')
    ");

    $insert->execute([':feature' => 'intercept', ':value' => $b0]);
    $insert->execute([':feature' => 'age', ':value' => $b1]);
    $insert->execute([':feature' => 'condition', ':value' => $b2]);

    // 4️⃣ Update training tracker
    $pdo->exec("UPDATE ai_training_tracker SET last_trained = NOW() WHERE id = 1");

    echo "<p>✅ Model retrained successfully!</p>";
    echo "<ul>
            <li><b>Intercept (b0):</b> $b0</li>
            <li><b>Age coefficient (b1):</b> $b1</li>
            <li><b>Condition coefficient (b2):</b> $b2</li>
          </ul>";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage();
} catch (Exception $e) {
    echo "⚠️ Error: " . $e->getMessage();
}
?>
