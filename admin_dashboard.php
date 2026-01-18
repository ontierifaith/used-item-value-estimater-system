<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// ===== Database Connection (CORRECTED) =====
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";

try {
    // Establishing the PDO connection
    $pdo = new PDO($dsn, $username, $password); 
    
    // Setting error mode to exception (This line caused the error if PDO object failed to initialize)
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); 
} catch (PDOException $e) {
    // If connection fails, stop execution and display error
    die("Database connection failed: " . $e->getMessage());
}

// ===== Fetch Summary Metrics (UNCHANGED) =====
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_reports = $pdo->query("SELECT COUNT(*) FROM ai_estimations")->fetchColumn();
$total_estimations = $total_reports;

// Fetch AI Estimations & calculate performance (UNCHANGED)
$labels = [];
$accuracy_values = [];
$mse_values = [];
$performance_data = [];

$stmt = $pdo->query("
    SELECT ai.ai_estimation_id, ai.ai_estimation_price_min, ai.ai_estimation_price_max, 
            i.item_title, i.item_brand, i.market_price
    FROM ai_estimations ai
    JOIN items i ON ai.ai_estimation_item_id = i.item_id
    ORDER BY ai.ai_estimation_id DESC
");

$estimations = $stmt->fetchAll(PDO::FETCH_ASSOC);
$total_accuracy = 0;
$total_mse = 0;

if ($estimations) {
    foreach ($estimations as $e) {
        $predicted_avg = ($e['ai_estimation_price_min'] + $e['ai_estimation_price_max']) / 2;
        $market_price = $e['market_price'] ?? $predicted_avg;
        $accuracy = $market_price > 0 ? max(0, 1 - abs($predicted_avg - $market_price) / $market_price) : 0;
        $mse = pow($predicted_avg - $market_price, 2);

        $total_accuracy += $accuracy;
        $total_mse += $mse;

        $performance_data[] = [
            'id' => $e['ai_estimation_id'],
            'item' => $e['item_title'] . " (" . $e['item_brand'] . ")",
            'predicted_avg' => $predicted_avg,
            'accuracy' => $accuracy * 100,
            'mse' => $mse
        ];

        $labels[] = $e['item_title'];
        $accuracy_values[] = round($accuracy * 100, 2);
        $mse_values[] = round($mse, 2);
    }
}

$avg_accuracy = $estimations ? round($total_accuracy / count($estimations) * 100, 2) : 0;
$avg_mse = $estimations ? round($total_mse / count($estimations), 2) : 0;

// Dynamic card classes (UNCHANGED)
$accuracy_class = $avg_accuracy >= 80 ? 'bg-success' : ($avg_accuracy >= 50 ? 'bg-warning' : 'bg-danger');
$mse_class = $avg_mse <= 50 ? 'bg-success' : ($avg_mse <= 100 ? 'bg-warning' : 'bg-danger');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - Snapit</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>


<style>
/* --- Global Styles --- */
:root {
    --snapit-blue: #007bff;
    --snapit-red: #d63031;
    --snapit-dark-bg: #212529; /* Dark background for sidebar */
    --snapit-light-bg: #f5f6fa;
    --snapit-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

body { 
    background-color: var(--snapit-light-bg); 
    font-family: 'Poppins', sans-serif; 
    margin:0; 
    padding:0; 
}

/* --- Wrapper for Sidebar Layout --- */
.wrapper {
    display: flex;
    width: 100%;
    min-height: 100vh;
}

/* --- Sidebar Styling --- */
#sidebar {
    min-width: 250px;
    max-width: 250px;
    background: var(--snapit-dark-bg);
    color: #fff;
    transition: all 0.3s;
    position: sticky;
    top: 0;
    height: 100vh;
    padding: 0;
    box-shadow: 2px 0 10px rgba(0,0,0,0.15);
}

#sidebar.active {
    margin-left: -250px;
}

.sidebar-header {
    padding: 20px;
    background: #171a1d;
    text-align: center;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}
.sidebar-header h3 {
    color: var(--snapit-blue);
    font-weight: 700;
    font-size: 1.5rem;
    margin: 0;
}

.sidebar-links {
    padding: 20px 0;
}

.sidebar-links a {
    padding: 15px 20px;
    font-size: 1.1em;
    display: block;
    color: #e9ecef;
    text-decoration: none;
    transition: all 0.3s;
    border-left: 5px solid transparent;
}

.sidebar-links a:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.08);
    border-left: 5px solid var(--snapit-blue);
}

.sidebar-links a i {
    margin-right: 10px;
}

/* --- Content Area --- */
#content {
    width: 100%;
    padding: 20px;
    min-height: 100vh;
    transition: all 0.3s;
}

/* --- Top Navbar/Header --- */
.top-navbar { 
    background-color: #fff; 
    box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
    padding: 10px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
}
.navbar-toggle {
    background: none;
    border: none;
    color: var(--snapit-blue);
    font-size: 1.5rem;
    margin-right: 15px;
}
.logout-btn { 
    padding: 8px 18px; 
    background-color: var(--snapit-red); 
    color: #fff; 
    border-radius: 8px; 
    font-weight: 600; 
    text-decoration: none; 
    transition: transform 0.3s, background 0.3s; 
    box-shadow: 0 4px 10px rgba(214, 48, 49, 0.4);
}
.logout-btn:hover { 
    background-color: #c82333; 
    transform: translateY(-2px); 
    box-shadow: 0 6px 15px rgba(214, 48, 49, 0.6);
}

/* --- KPI Cards (Enhanced Metrics) --- */
.kpi-card { 
    padding: 25px; 
    color: #fff; 
    border-radius: 12px; 
    box-shadow: 0 8px 20px rgba(0,0,0,0.15); 
    text-align: left; 
    transition: transform 0.3s, box-shadow 0.3s;
    height: 100%;
    position: relative;
    overflow: hidden;
}
.kpi-card:hover { 
    transform: translateY(-5px); 
    box-shadow: 0 15px 30px rgba(0,0,0,0.2); 
}
.kpi-card h3 { 
    font-size: 2.5rem; 
    margin-bottom: 0px; 
    font-weight: 700;
}
.kpi-card p {
    font-size: 1rem;
    opacity: 0.85;
    margin-bottom: 0;
}
.kpi-card i {
    position: absolute;
    top: 50%;
    right: 20px;
    transform: translateY(-50%);
    font-size: 5rem;
    opacity: 0.15; /* Subtle background icon */
}

/* --- Data Section Styling --- */
.data-card {
    background: #fff;
    border-radius: 12px;
    padding: 25px;
    box-shadow: var(--snapit-shadow);
    margin-top: 20px;
    height: 100%;
}

/* --- Utility Icons (Download Buttons) --- */
.nav-icon-btn { 
    width: 38px; 
    height: 38px; 
    display:flex; 
    align-items:center; 
    justify-content:center; 
    border-radius:8px; 
    color:#fff; 
    margin-right:10px; 
    transition: opacity 0.2s;
}
.nav-icon-btn.pdf { background-color:#dc3545; }
.nav-icon-btn.excel { background-color:#28a745; }
.nav-icon-btn:hover { opacity:0.85; }

/* --- Colors for KPI Status --- */
.bg-primary { background-color: var(--snapit-blue) !important; }
.bg-info { background-color: #17a2b8 !important; }
.bg-warning { background-color: #ffc107 !important; color: #333 !important; }
.bg-danger { background-color: #dc3545 !important; }
.bg-success { background-color: #28a745 !important; }

/* --- Media Queries for Sidebar on Mobile --- */
@media (max-width: 768px) {
    #sidebar {
        margin-left: -250px;
        position: fixed; /* Fix sidebar position on small screens */
        z-index: 1030;
    }
    #sidebar.active {
        margin-left: 0;
    }
    #content {
        width: 100%;
    }
    #content.active {
        margin-left: 250px;
    }
    .top-navbar .d-flex {
        width: 100%;
        justify-content: space-between !important;
    }
}
</style>
</head>
<body>
    
<div class="wrapper">
    <nav id="sidebar">
        <div class="sidebar-header">
            <h3>Snapit Admin</h3>
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
        <nav class="top-navbar d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <button type="button" id="sidebarCollapse" class="navbar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0 d-none d-sm-inline-block text-dark fw-bold">System Overview</h4>
            </div>

            <div class="d-flex align-items-center">
                <a 
    href="download_report.php?format=pdf" 
    id="pdf-download-link"
    style="
        /* Container Setup */
        display: inline-flex;
        flex-direction: column; /* Stacks items vertically: icon on top, text below */
        align-items: center;
        text-decoration: none;
        margin: 0 10px; /* Space between other elements */
    "
    title="Download PDF Report" 
    target="_blank" 
>
    <span style="
        background-color: transparent;
        color: #007bff; /* Changed to Blue */
        
        display: flex; 
        align-items: center; 
        justify-content: center; 
        width: 38px; 
        height: 38px; 
        
        border-radius: 50%;
        font-size: 18px; 
        
        /* Transition for subtle hover effect */
        transition: background-color 0.2s, color 0.2s;
    ">
        <i class="fas fa-download"></i>
    </span>

    <span style="
        color: #007bff; /* Blue text */
        font-size: 12px; 
        font-weight: 500;
        margin-top: 4px; /* Space between icon and text */
    ">
        Export
    </span>
</a>
                

                <span class="me-3 fw-bold d-none d-lg-inline-block"><?= htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="admin_login.php" class="logout-btn">Logout</a>
            </div>
        </nav>

        <div class="container-fluid p-0">
            <h3 class="mb-4 fw-bold text-dark">Key Performance Indicators</h3>
            <div class="row g-4">
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="kpi-card bg-primary">
                        <i class="fas fa-users"></i>
                        <h3 class="counter"><?= $total_users ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="kpi-card bg-info">
                        <i class="fas fa-file-invoice"></i>
                        <h3 class="counter"><?= $total_reports ?></h3>
                        <p>Total Reports</p>
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="kpi-card bg-success">
                        <i class="fas fa-chart-bar"></i>
                        <h3 class="counter"><?= $total_estimations ?></h3>
                        <p>AI Estimations</p>
                    </div>
                </div>
                
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="kpi-card <?= $accuracy_class ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <h3 class="counter"><?= $avg_accuracy ?>%</h3>
                        <p>Avg Accuracy</p>
                    </div>
                </div>
            </div>

            <h3 class="mt-5 mb-4 fw-bold text-dark">AI Model Performance Analysis</h3>
            <div class="row g-4">
                <div class="col-12 col-lg-6">
                    <div class="data-card">
                        <h5 class="fw-bold mb-3">Estimation Trends</h5>
                        <canvas id="aiChart"></canvas>
                    </div>
                </div>
                <div class="col-12 col-lg-6">
                    <div class="data-card">
                        <h5 class="fw-bold mb-3">Model Metrics Summary</h5>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Metric</th>
                                    <th>Value</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td>**Average Accuracy**</td><td><?= $avg_accuracy ?>%</td><td><span class="badge rounded-pill <?= $accuracy_class ?>"><?= $avg_accuracy >= 80 ? 'Excellent' : ($avg_accuracy >= 50 ? 'Good' : 'Needs Improvement') ?></span></td></tr>
                                <tr><td>**Average MSE**</td><td><?= number_format($avg_mse, 2) ?></td><td><span class="badge rounded-pill <?= $mse_class ?>"><?= $avg_mse <= 50 ? 'Low Error' : ($avg_mse <= 100 ? 'Moderate Error' : 'High Error') ?></span></td></tr>
                                <tr><td>**Total Records**</td><td><?= $total_estimations ?></td><td><span class="badge rounded-pill bg-primary">Active</span></td></tr>
                            </tbody>
                        </table>
                        <h5 class="fw-bold mb-3 mt-4">Recent Estimations</h5>
                        <table id="performanceTable" class="table table-striped table-hover" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Item</th>
                                    <th>Avg Price</th>
                                    <th>Accuracy</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_slice($performance_data, 0, 5) as $data): ?>
                                <tr>
                                    <td><?= $data['id'] ?></td>
                                    <td><?= htmlspecialchars($data['item']) ?></td>
                                    <td>$<?= number_format($data['predicted_avg'], 2) ?></td>
                                    <td><?= round($data['accuracy'], 1) ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <footer class="mt-5 mb-3">&copy; <?= date('Y'); ?> Snapit. All rights reserved.</footer>
        </div>
    </div>
</div>

<script>
// Sidebar Toggle functionality
$(document).ready(function () {
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
        $('#content').toggleClass('active');
    });
});

// Animated counters
const counters = document.querySelectorAll('.counter');
counters.forEach(counter => {
    let start = 0;
    const end = parseFloat(counter.innerText);
    const increment = end / 100;
    const interval = setInterval(() => {
        start += increment;
        if(start >= end) start = end;
        
        // Use toFixed for decimals if the content is a percentage
        let formattedValue = (counter.closest('.kpi-card').querySelector('p').textContent.includes('%')) ? start.toFixed(2) : Math.round(start);
        counter.innerText = formattedValue;

        if(start >= end) clearInterval(interval);
    }, 15);
});

$(document).ready(function() { 
    // DataTables Initialization 
    $('#performanceTable').DataTable({
        "paging": false, 
        "searching": false, 
        "info": false 
    }); 
    
    // Bootstrap tooltips Initialization 
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
});

// Chart.js
const ctx = document.getElementById('aiChart')?.getContext('2d');
if(ctx){
new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode($labels) ?>,
        datasets: [
            { label: 'Accuracy (%)', data: <?= json_encode($accuracy_values) ?>, borderColor: '#28a745', backgroundColor: 'rgba(40, 167, 69, 0.1)', fill: true, tension:0.3, pointHoverRadius:7 },
            { label: 'MSE', data: <?= json_encode($mse_values) ?>, borderColor: '#dc3545', backgroundColor: 'rgba(220, 53, 69, 0.1)', fill: true, tension:0.3, pointHoverRadius:7, yAxisID: 'y1' }
        ]
    },
    options: { 
        responsive:true, 
        plugins:{ 
            legend:{ position:'top' },
            tooltip: { mode: 'index', intersect: false } 
        }, 
        scales:{ 
            y: { 
                type: 'linear', 
                display: true, 
                position: 'left',
                beginAtZero: true,
                title: { display: true, text: 'Accuracy (%)' } 
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'Mean Squared Error (MSE)' }
            },
            x: {
                title: { display: true, text: 'Recent Items' }
            }
        } 
    }
});
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>