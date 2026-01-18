<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require 'vendor/autoload.php'; // PhpSpreadsheet autoload
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Database connection
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";
$pdo = new PDO($dsn, $username, $password);

// Fetch AI estimations
$stmt = $pdo->query("
    SELECT ai.ai_estimation_id, ai.ai_estimation_price_min, ai.ai_estimation_price_max, 
           i.item_title, i.item_brand, i.market_price
    FROM ai_estimations ai
    JOIN items i ON ai.ai_estimation_item_id = i.item_id
    ORDER BY ai.ai_estimation_id DESC
");
$estimations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Create Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setCellValue('A1', 'Item');
$sheet->setCellValue('B1', 'Predicted Avg');
$sheet->setCellValue('C1', 'Accuracy (%)');
$sheet->setCellValue('D1', 'MSE');

$rowNum = 2;
foreach ($estimations as $e) {
    $predicted_avg = ($e['ai_estimation_price_min'] + $e['ai_estimation_price_max']) / 2;
    $market_price = $e['market_price'] ?? $predicted_avg;
    $accuracy = $market_price > 0 ? max(0, 1 - abs($predicted_avg - $market_price) / $market_price) : 0;
    $mse = pow($predicted_avg - $market_price, 2);

    $sheet->setCellValue("A$rowNum", $e['item_title'] . " ({$e['item_brand']})");
    $sheet->setCellValue("B$rowNum", $predicted_avg);
    $sheet->setCellValue("C$rowNum", round($accuracy*100,2));
    $sheet->setCellValue("D$rowNum", round($mse,2));
    $rowNum++;
}

$writer = new Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="ai_estimation_report.xlsx"');
$writer->save('php://output');
exit;
?>
