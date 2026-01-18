<?php
session_start();
// The user code uses include('db_connect.php'). Assuming this file 
// establishes the PDO connection as $conn.
include('db_connect.php'); 

// The original code used try/catch for the connection. We will rely on db_connect.php
// but define the connection logic here in case db_connect.php is missing.

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Ensure $conn is available, if db_connect.php failed (safety check)
if (!isset($conn) || !$conn instanceof PDO) {
    try {
        $conn = new PDO("pgsql:host=localhost;dbname=used_item_value_estimator", "postgres", "BQfa2050*");
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }
}

// Message/Notification handling (Flash Message)
$ui_message = $_SESSION['ui_message'] ?? null;
unset($_SESSION['ui_message']);

// ✅ Handle report deletion
if (isset($_POST['report_id'])) {
    $report_id = intval($_POST['report_id']);

    try {
        $stmt = $conn->prepare("DELETE FROM ai_estimations WHERE ai_estimation_id = :id");
        $stmt->bindParam(':id', $report_id, PDO::PARAM_INT);
        $stmt->execute();
        
        $_SESSION['ui_message'] = ['type' => 'success', 'text' => "Report ID **{$report_id}** deleted successfully."];
    } catch (PDOException $e) {
        $_SESSION['ui_message'] = ['type' => 'danger', 'text' => "Error deleting report: " . $e->getMessage()];
    }
    // Redirect to clear the POST data
    header("Location: manage_reports.php");
    exit;
}

// ✅ Fetch AI estimations joined with item and image info
try {
    $stmt = $conn->prepare("
        SELECT 
            e.ai_estimation_id, 
            e.ai_estimation_price_min, 
            e.ai_estimation_price_max, 
            e.ai_estimation_confidence, 
            e.ai_estimation_item_id,
            i.item_title, 
            i.item_brand, 
            img.item_image_path
        FROM ai_estimations e
        JOIN items i ON i.item_id = e.ai_estimation_item_id
        LEFT JOIN item_images img ON img.item_image_item_id = i.item_id
        ORDER BY e.ai_estimation_id DESC
    ");
    $stmt->execute();
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching reports: " . $e->getMessage());
}

/**
 * Helper function to return the appropriate Bootstrap badge class based on confidence.
 * @param float $confidence
 * @return string
 */
function get_confidence_badge_class(float $confidence): string {
    if ($confidence >= 90) return 'bg-success';
    if ($confidence >= 70) return 'bg-primary';
    if ($confidence >= 50) return 'bg-warning text-dark';
    return 'bg-danger'; 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Valuation Reports | Admin Panel</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<style>
    :root {
        --snapit-blue: #007bff;
        --snapit-dark-bg: #212529;
        --snapit-light-bg: #f5f6fa;
        --snapit-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    body { 
        background-color: var(--snapit-light-bg); 
        font-family: 'Poppins', sans-serif; 
        margin:0; 
        padding:0; 
    }

    /* --- Admin Layout Wrapper --- */
    .wrapper {
        display: flex;
        width: 100%;
        min-height: 100vh;
    }

    /* --- Sidebar (Mimicking Dashboard) --- */
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
    .header-card h2 {
        font-weight: 700;
        color: #333;
        margin: 0;
    }

    /* --- Data Table Styling --- */
    .data-table-card {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: var(--snapit-shadow);
    }
    .table thead th {
        background-color: #17a2b8; /* Info Blue for Reports */
        color: white;
        font-weight: 600;
        border-bottom: none;
    }
    .table-hover tbody tr:hover {
        background-color: #e9f7ff;
    }
    .dataTables_wrapper .row {
        margin-top: 15px;
    }
    
    /* --- Table Image Styling --- */
    .item-img-thumb {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #eee;
    }

    /* --- Action Button Styling --- */
    .btn-action {
        padding: 5px 10px;
        font-size: 0.9rem;
    }

    .price-range {
        font-weight: 600;
        color: #28a745; /* Success Green */
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
            <h2><i class="fas fa-file-invoice-dollar me-2 text-info"></i> Valuation Reports Management</h2>
            <a href="admin_login.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <?php if ($ui_message): ?>
            <div class="alert alert-<?= $ui_message['type'] ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?= $ui_message['type'] === 'success' ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                <?= $ui_message['text']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="data-table-card">
            <h5 class="fw-bold mb-3">Recent AI Estimation Reports</h5>
            <table id="reportsTable" class="table table-striped table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Details</th>
                        <th>Image</th>
                        <th>Estimated Price</th>
                        <th>Confidence</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reports) > 0): ?>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td><span class="badge bg-info"><?= htmlspecialchars($r['ai_estimation_id']) ?></span></td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($r['item_title']) ?></span><br>
                                    <small class="text-muted"><i class="fas fa-tag me-1"></i>Brand: <?= htmlspecialchars($r['item_brand']) ?></small>
                                </td>
                                <td>
                                    <?php if (!empty($r['item_image_path'])): ?>
                                        <img src="<?= htmlspecialchars($r['item_image_path']) ?>" alt="Item Image" class="item-img-thumb">
                                    <?php else: ?>
                                        <span class="text-muted"><i class="fas fa-camera-slash"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="price-range">
                                        $<?= number_format($r['ai_estimation_price_min'], 0) ?> - $<?= number_format($r['ai_estimation_price_max'], 0) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $confidence = (float)$r['ai_estimation_confidence']; ?>
                                    <span class="badge <?= get_confidence_badge_class($confidence) ?>">
                                        <?= $confidence ?>%
                                    </span>
                                </td>
                                <td>
                                    <button type="button" 
                                       class="btn btn-danger btn-action"
                                       data-bs-toggle="modal"
                                       data-bs-target="#confirmDeleteModal"
                                       data-report-id="<?= $r['ai_estimation_id']; ?>"
                                       data-item-title="<?= htmlspecialchars($r['item_title']); ?>">
                                        <i class="fas fa-trash-alt"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center py-4">
                            <i class="fas fa-info-circle me-2"></i> No valuation reports have been generated yet.
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
                <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirm Report Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the report for item: **<span id="itemTitlePlaceholder" class="fw-bold"></span>** (ID: <span id="reportIdPlaceholder" class="fw-bold"></span>)?</p>
                <p class="text-danger">This action will permanently remove the estimation record.</p>
                <form id="deleteReportForm" method="post" action="manage_reports.php">
                    <input type="hidden" name="report_id" id="modalReportIdInput">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="deleteReportForm" class="btn btn-danger"><i class="fas fa-trash-alt"></i> Delete Permanently</button>
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
    $('#reportsTable').DataTable({
        "order": [[ 0, "desc" ]], // Default sort by ID descending
        "pagingType": "simple_numbers",
        "language": {
            "search": "Filter reports:",
            "info": "Showing _START_ to _END_ of _TOTAL_ reports"
        },
        "columnDefs": [
            // Disable sorting on the Image and Action columns
            { "orderable": false, "targets": [2, 5] } 
        ]
    });

    // 2. Handle Delete Modal Setup
    const deleteModal = document.getElementById('confirmDeleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        const button = event.relatedTarget; 
        
        const reportId = button.getAttribute('data-report-id');
        const itemTitle = button.getAttribute('data-item-title');
        
        // Update modal content and form data
        document.getElementById('reportIdPlaceholder').textContent = reportId;
        document.getElementById('itemTitlePlaceholder').textContent = itemTitle;
        document.getElementById('modalReportIdInput').value = reportId;
    });
});
</script>

</body>
</html>