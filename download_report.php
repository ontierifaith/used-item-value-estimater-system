<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

require_once('tcpdf/tcpdf.php');

// Database connection
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";
$pdo = new PDO($dsn, $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ===== Fetch Users =====
$users = $pdo->query("
    SELECT u.user_id, u.user_full_name, u.user_email, u.user_phone, 
           l.login_user_name, l.login_rank, u.user_created_at
    FROM users u
    LEFT JOIN logins l ON u.user_id = l.login_user_id
    ORDER BY u.user_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Fetch AI Estimations =====
$estimations = $pdo->query("
    SELECT ai.ai_estimation_id, ai.ai_estimation_price_min, ai.ai_estimation_price_max, 
           ai.ai_estimation_confidence, ai.ai_estimation_depreciation, ai.ai_estimation_notes,
           i.item_title, i.item_brand, i.market_price
    FROM ai_estimations ai
    JOIN items i ON ai.ai_estimation_item_id = i.item_id
    ORDER BY ai.ai_estimation_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Fetch Reports =====
$reports = $pdo->query("
    SELECT r.report_id, r.report_title, r.report_description, r.report_created_at,
           u.user_full_name AS submitted_by
    FROM reports r
    LEFT JOIN users u ON r.report_user_id = u.user_id
    ORDER BY r.report_created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Fetch AI Model Performance =====
$ai_performance = $pdo->query("
    SELECT ai_model_performance_id, ai_model_name, ai_model_version, 
           ai_model_accuracy, ai_model_mse, ai_model_last_trained, ai_model_notes
    FROM ai_model_performance
    ORDER BY ai_model_performance_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Fetch Valuation Reports =====
$valuation_reports = $pdo->query("
    SELECT vr.valuation_report_id, i.item_title, i.item_brand, vr.valuation_report_path,
           vr.valuation_report_format, vr.valuation_report_generated_on
    FROM valuation_reports vr
    JOIN items i ON vr.valuation_report_item_id = i.item_id
    ORDER BY vr.valuation_report_generated_on DESC
")->fetchAll(PDO::FETCH_ASSOC);

// ===== Create TCPDF =====
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Used Item Value Estimator');
$pdf->SetTitle('Full Admin Report');
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, "Full Admin Report", 0, 1, 'C');
$pdf->Ln(5);

// ===== Function to Write Tables =====
function writeTable($pdf, $title, $header, $rows) {
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, $title, 0, 1, 'L');
    $pdf->SetFont('helvetica', 'B', 10);

    $tbl = '<table border="1" cellpadding="4"><thead><tr>';
    foreach ($header as $h) {
        $tbl .= '<th style="background-color:#007bff;color:#ffffff;">' . htmlspecialchars($h) . '</th>';
    }
    $tbl .= '</tr></thead><tbody>';
    $pdf->SetFont('helvetica', '', 9);

    foreach ($rows as $r) {
        $tbl .= '<tr>';
        foreach ($r as $cell) {
            $tbl .= '<td>' . htmlspecialchars($cell) . '</td>';
        }
        $tbl .= '</tr>';
    }
    $tbl .= '</tbody></table><br><br>';
    $pdf->writeHTML($tbl, true, false, false, false, '');
}

// ===== Users Table =====
$user_rows = [];
foreach ($users as $u) {
    $user_rows[] = [
        $u['user_id'], $u['user_full_name'], $u['user_email'], $u['user_phone'],
        $u['login_user_name'], $u['login_rank'], $u['user_created_at']
    ];
}
writeTable($pdf, 'Registered Users', ['ID', 'Full Name', 'Email', 'Phone', 'Username', 'Rank', 'Registered On'], $user_rows);

// ===== AI Estimations Table =====
$ai_rows = [];
foreach ($estimations as $e) {
    $predicted_avg = ($e['ai_estimation_price_min'] + $e['ai_estimation_price_max']) / 2;
    $market_price = $e['market_price'] ?? $predicted_avg;
    $accuracy = $market_price > 0 ? max(0, 1 - abs($predicted_avg - $market_price) / $market_price) * 100 : 0;
    $mse = pow($predicted_avg - $market_price, 2);

    $ai_rows[] = [
        $e['ai_estimation_id'],
        $e['item_title'] . ' (' . $e['item_brand'] . ')',
        number_format($e['ai_estimation_price_min'],2),
        number_format($e['ai_estimation_price_max'],2),
        number_format($e['ai_estimation_confidence'],1),
        number_format($e['ai_estimation_depreciation'],1),
        $e['ai_estimation_notes'] ?: '-',
        number_format($market_price,2),
        number_format($predicted_avg,2),
        number_format($accuracy,2),
        number_format($mse,2)
    ];
}
writeTable($pdf, 'AI Estimations', ['ID','Item','Min Price','Max Price','Confidence','Depreciation','Notes','Market Price','Predicted Avg','Accuracy (%)','MSE'], $ai_rows);

// ===== Reports Table =====
$report_rows = [];
foreach ($reports as $r) {
    $report_rows[] = [
        $r['report_id'], $r['report_title'], $r['report_description'], $r['submitted_by'], $r['report_created_at']
    ];
}
writeTable($pdf, 'Submitted Reports', ['ID','Title','Description','Submitted By','Created On'], $report_rows);

// ===== AI Model Performance Table =====
$ai_perf_rows = [];
foreach ($ai_performance as $p) {
    $ai_perf_rows[] = [
        $p['ai_model_performance_id'], $p['ai_model_name'], $p['ai_model_version'],
        number_format($p['ai_model_accuracy'],4), number_format($p['ai_model_mse'],2),
        $p['ai_model_last_trained'], $p['ai_model_notes'] ?: '-'
    ];
}
writeTable($pdf, 'AI Model Performance', ['ID','Model Name','Version','Accuracy','MSE','Last Trained','Notes'], $ai_perf_rows);

// ===== Valuation Reports Table =====
$val_rows = [];
foreach ($valuation_reports as $v) {
    $val_rows[] = [
        $v['valuation_report_id'], $v['item_title'] . ' (' . $v['item_brand'] . ')',
        $v['valuation_report_path'], $v['valuation_report_format'], $v['valuation_report_generated_on']
    ];
}
writeTable($pdf, 'Valuation Reports', ['ID','Item','Report Path','Format','Generated On'], $val_rows);

// ===== Output PDF =====
$pdf->Output('full_admin_report.pdf', 'D');
exit;
?>
