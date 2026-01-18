<?php
session_start();

// Redirect if not admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ===== Database Connection =====
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ===== Fetch AI Estimations & Items (Data Processing) =====
$performance_data = [];
$labels = [];
$accuracy_values = [];
$mse_values = [];
$sum_accuracy = 0;
$sum_mse = 0;
$total_estimations = 0;

try {
    $stmt = $pdo->query("
        SELECT 
            ai.ai_estimation_id,
            ai.ai_estimation_price_min,
            ai.ai_estimation_price_max,
            i.item_title,
            i.item_brand,
            i.market_price
        FROM ai_estimations ai
        JOIN items i ON ai.ai_estimation_item_id = i.item_id
        ORDER BY ai.ai_estimation_id DESC
    ");
    $estimations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($estimations) {
        foreach ($estimations as $e) {
            $predicted_avg = ($e['ai_estimation_price_min'] + $e['ai_estimation_price_max']) / 2;
            // Use predicted average if market price is null or zero
            $market_price = ($e['market_price'] > 0) ? $e['market_price'] : $predicted_avg; 

            // Note: Accuracy calculation is custom (1 - |difference| / Market Price)
            $accuracy = $market_price > 0 ? max(0, 1 - abs($predicted_avg - $market_price) / $market_price) : 0;
            
            // Mean Squared Error (MSE) calculation
            $mse = pow($predicted_avg - $market_price, 2);

            $performance_data[] = [
                'id' => $e['ai_estimation_id'],
                'item' => $e['item_title'] . " (" . $e['item_brand'] . ")",
                'predicted_avg' => $predicted_avg,
                'market_price' => $market_price,
                'accuracy' => $accuracy * 100,
                'mse' => $mse
            ];

            $labels[] = htmlspecialchars($e['item_title']);
            $accuracy_values[] = round($accuracy * 100, 2);
            $mse_values[] = round($mse, 2);

            $sum_accuracy += $accuracy * 100;
            $sum_mse += $mse;
        }

        $total_estimations = count($estimations);
        $average_accuracy = $total_estimations > 0 ? round($sum_accuracy / $total_estimations, 2) : 0;
        // Calculating Root Mean Squared Error (RMSE) for a more intuitive metric
        $average_mse = $total_estimations > 0 ? round($sum_mse / $total_estimations, 2) : 0;
        $rmse = round(sqrt($average_mse), 2); 
    }
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// ===== Helper Functions =====

function getCardColor($type, $value) {
    if ($type === 'accuracy') {
        return $value >= 80 ? 'bg-success' : ($value >= 50 ? 'bg-warning' : 'bg-danger');
    } elseif ($type === 'mse') {
        return $value <= 100000 ? 'bg-success' : ($value <= 500000 ? 'bg-warning' : 'bg-danger');
    } else {
        return 'bg-primary';
    }
}
function get_accuracy_badge_class(float $accuracy): string {
    if ($accuracy >= 80) return 'bg-success';
    if ($accuracy >= 50) return 'bg-primary';
    return 'bg-danger';
}
function get_mse_badge_class(float $mse): string {
    if ($mse <= 100000) return 'bg-success';
    if ($mse <= 500000) return 'bg-primary';
    return 'bg-danger';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI Model Performance Dashboard</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>


<style>
    :root {
        --snapit-blue: #007bff;
        --snapit-dark-bg: #212529;
        --snapit-light-bg: #f5f6fa;
        --snapit-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    body { 
        font-family: 'Poppins', sans-serif; 
        background-color: var(--snapit-light-bg); 
        margin:0; 
        padding:0; 
    }
    
    /* --- Admin Layout Wrapper --- */
    .wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
    }

    /* --- Sidebar --- */
    #sidebar {
        min-width: 250px;
        max-width: 250px;
        background: var(--snapit-dark-bg);
        color: #fff;
        position: sticky;
        top: 0;
        height: 100vh;
        box-shadow: 2px 0 10px rgba(0,0,0,0.15);
    }
    .sidebar-header {
        padding: 20px;
        background: #171a1d;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    .sidebar-header h3 { color: var(--snapit-blue); font-weight: 700; font-size: 1.5rem; margin: 0; }
    .sidebar-links { padding: 20px 0; }
    .sidebar-links a {
        padding: 15px 20px;
        font-size: 1.1em;
        display: block;
        color: #e9ecef;
        text-decoration: none;
        transition: all 0.3s;
        border-left: 5px solid transparent;
    }
    .sidebar-links a:hover, .sidebar-links .active {
        color: #fff;
        background: rgba(255, 255, 255, 0.08);
        border-left: 5px solid var(--snapit-blue);
    }
    .sidebar-links a i { margin-right: 10px; }

    /* --- Content Area --- */
    #content {
        width: 100%;
        padding: 30px;
        min-height: 100vh;
    }
    
    /* --- Header Card --- */
    .header-card {
        background: #fff;
        border-radius: 12px;
        padding: 25px 30px;
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
        margin-bottom: 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Summary Cards */
    .summary-cards .summary-card {
        color: #fff;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        transition: transform 0.3s;
        min-width: 220px;
    }
    .summary-cards .summary-card:hover { transform: translateY(-3px); }
    .summary-card h3 { font-size: 2rem; }
    .summary-card p { font-size: 0.9rem; margin: 0; }
    .summary-card i { font-size: 2rem; opacity: 0.5; float: right; }

    /* Chart Area */
    .chart-card {
        background: #fff;
        padding: 25px;
        border-radius: 12px;
        box-shadow: var(--snapit-shadow);
        margin-bottom: 30px;
    }
    .chart-container {
        /* Set a fixed height for the chart container */
        position: relative;
        height: 250px; 
        width: 100%;
    }

    /* Data Table Styling */
    .data-table-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--snapit-shadow);
    }
    .table thead th {
        background-color: #6c757d; /* Muted Grey for Logs/Metrics */
        color: white;
        font-weight: 600;
        border-bottom: none;
    }
    .table-hover tbody tr:hover {
        background-color: #f1f1f1;
    }
    .dataTables_wrapper .row {
        margin-top: 15px;
    }
    
    /* Action Button Styling */
    .btn-action {
        padding: 5px 10px;
        font-size: 0.9rem;
    }
</style>
</head>
<body>

<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-lock me-1"></i> Admin Panel</h3>
        </div>

        <div class="sidebar-links">
            <a href="admin_dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
             <a href="manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
            <a href="manage_ai_estimates.php"><i class="fas fa-chart-line"></i> System Logs</a>
            <a href="manage_reports.php"><i class="fas fa-file-alt"></i> Manage Reports</a>
            <a href="ai_performance_dashboard.php"><i class="fas fa-robot"></i> AI Model</a>
            
        </div>
    </nav>

    <div id="content">
        <div class="header-card">
            <h2><i class="fas fa-microchip me-2 text-dark"></i> AI Model Performance Dashboard</h2>
            <span class="me-3 fw-bold text-dark"> <a href="admin_login.php"><i class="fas fa-sign-out-alt"></i> Logout</a></span>
            </div>

        <div class="row summary-cards">
            <div class="col-md-4">
                <div class="summary-card <?= getCardColor('primary',0) ?>">
                    <i class="fas fa-cube"></i>
                    <h3><?= $total_estimations ?></h3>
                    <p>Total Data Points</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card <?= getCardColor('accuracy',$average_accuracy) ?>">
                    <i class="fas fa-bullseye"></i>
                    <h3><?= $average_accuracy ?>%</h3>
                    <p>Average Accuracy</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="summary-card <?= getCardColor('mse',$average_mse) ?>">
                    
                    
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="chart-card">
                    <h5 class="fw-bold mb-3">Estimation Accuracy and Error Over Items</h5>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="chart-card h-100 d-flex flex-column justify-content-center align-items-center text-center">
                    <h5 class="text-muted mb-3"><i class="fas fa-info-circle me-1"></i> Metric Interpretation</h5>
                    <p class="small">
                        The chart displays two key metrics: **Accuracy** (should be high) and **MSE** (should be low).
                        High MSE indicates significant price deviation from the market price.
                    </p>
                    <p class="small fw-bold text-dark">
                        Root Mean Squared Error (RMSE) is the average magnitude of the error, measured in KES.
                    </p>
                    
                </div>
            </div>
        </div>


        <div class="data-table-card">
            <h5 class="fw-bold mb-3">Detailed Item Performance Metrics</h5>
            <table id="performanceTable" class="table table-striped table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Details</th>
                        <th>Market Price (KES)</th>
                        <th>Predicted Avg (KES)</th>
                        <th>Accuracy (%)</th>
                        <th>MSE</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($performance_data)): ?>
                    <?php foreach ($performance_data as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['id']) ?></td>
                        <td><?= htmlspecialchars($p['item']) ?></td>
                        <td><?= number_format($p['market_price'], 2) ?></td>
                        <td><?= number_format($p['predicted_avg'], 2) ?></td>
                        <td>
                            <?php $acc = round($p['accuracy'], 2); ?>
                            <span class="badge <?= get_accuracy_badge_class($acc) ?>"><?= $acc ?>%</span>
                        </td>
                        <td>
                            <?php $mse = round($p['mse'], 2); ?>
                            <span class="badge <?= get_mse_badge_class($mse) ?>"><?= number_format($mse, 2) ?></span>
                        </td>
                        <td>
                            <a href="edit_ai_estimate.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary btn-action" data-bs-toggle="tooltip" title="Edit Data">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button" 
                                class="btn btn-sm btn-danger btn-action"
                                data-bs-toggle="modal"
                                data-bs-target="#confirmDeleteModal"
                                data-estimate-id="<?= $p['id']; ?>"
                                data-item-title="<?= htmlspecialchars($p['item']); ?>">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center py-4">
                        <i class="fas fa-info-circle me-2"></i> No estimation data available for performance tracking.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <footer class="mt-5 text-center text-muted">
            &copy; <?= date('Y'); ?> Snapit. Admin Dashboard.
        </footer>
    </div>
</div>

<div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirm Data Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the underlying data for item **<span id="itemTitlePlaceholder" class="fw-bold"></span>** (ID: <span id="estimateIdPlaceholder" class="fw-bold"></span>)?</p>
                <p class="text-danger">Deleting this entry will affect performance calculations.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a id="deleteEstimateLink" href="#" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete Permanently</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    // 1. Initialize DataTables for Search/Pagination
    $('#performanceTable').DataTable({
        "order": [[ 0, "desc" ]], // Default sort by ID descending
        "pagingType": "simple_numbers",
        "language": {
            "search": "Filter performance records:",
            "info": "Showing _START_ to _END_ of _TOTAL_ records"
        },
        "columnDefs": [
            { "orderable": false, "targets": [6] } 
        ]
    });

    // 2. Handle Delete Modal Setup
    const deleteModal = document.getElementById('confirmDeleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; 
        
        const estimateId = button.getAttribute('data-estimate-id');
        const itemTitle = button.getAttribute('data-item-title');
        
        document.getElementById('estimateIdPlaceholder').textContent = estimateId;
        document.getElementById('itemTitlePlaceholder').textContent = itemTitle;
        
        // Link to the external deletion script
        const deleteLink = document.getElementById('deleteEstimateLink');
        deleteLink.href = 'delete_ai_estimate.php?id=' + estimateId;
    });

    // 3. Initialize Chart.js
    new Chart(document.getElementById('performanceChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Accuracy (%)',
                    data: <?= json_encode($accuracy_values) ?>,
                    borderColor: 'rgba(40, 167, 69, 1)', // Green
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y'
                },
                {
                    label: 'MSE (Error Squared)',
                    data: <?= json_encode($mse_values) ?>,
                    borderColor: 'rgba(220, 53, 69, 1)', // Red
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // Ensures the chart fills the 250px height
            plugins: { 
                title: { display: false },
                legend: { position: 'top' } 
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Accuracy (%)' },
                    grid: { drawOnChartArea: true }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: { display: true, text: 'MSE Value (Error Squared)' },
                    grid: { drawOnChartArea: false }, 
                    beginAtZero: true
                }
            }
        }
    });
});
</script>

</body>
</html>