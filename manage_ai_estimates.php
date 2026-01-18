<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Database Connection
$dsn = "pgsql:host=localhost;dbname=used_item_value_estimator";
$username = "postgres";
$password = "BQfa2050*";

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}

// Function to handle deletion (assuming delete_ai_estimate.php redirects here)
if (isset($_GET['deleted_id'])) {
    // This is a placeholder logic, as the deletion link points to a separate file, 
    // but a notification should be displayed if successful.
    $_SESSION['ui_message'] = ['type' => 'success', 'text' => "AI Estimate ID **" . intval($_GET['deleted_id']) . "** deleted successfully."];
    // Clear GET parameter
    header("Location: manage_ai_estimates.php");
    exit;
}

// Flash Message/Notification handling 
$ui_message = $_SESSION['ui_message'] ?? null;
unset($_SESSION['ui_message']);

// Fetch all AI estimations with item info
$stmt = $pdo->query("
    SELECT ai.ai_estimation_id, i.item_title, i.item_brand, ai.ai_estimation_price_min, ai.ai_estimation_price_max,
           ai.ai_estimation_confidence, ai.ai_estimation_depreciation, ai.ai_estimation_notes
    FROM ai_estimations ai
    JOIN items i ON ai.ai_estimation_item_id = i.item_id
    ORDER BY ai.ai_estimation_id DESC
");
$estimations = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Helper function to get the appropriate Bootstrap badge class for Confidence.
 * @param float $confidence
 * @return string
 */
function get_confidence_badge_class(float $confidence): string {
    if ($confidence >= 80) return 'bg-success';
    if ($confidence >= 50) return 'bg-primary';
    return 'bg-warning text-dark';
}

/**
 * Helper function to get the appropriate Bootstrap badge class for Depreciation.
 * @param float $depreciation
 * @return string
 */
function get_depreciation_badge_class(float $depreciation): string {
    if ($depreciation >= 50) return 'bg-danger';
    if ($depreciation >= 20) return 'bg-warning text-dark';
    return 'bg-info';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage AI Estimates | Admin Panel</title>

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
        background-color: #3f51b5; /* Deeper Blue/Indigo for AI */
        color: white;
        font-weight: 600;
        border-bottom: none;
    }
    .table-hover tbody tr:hover {
        background-color: #f0f4ff;
    }
    .dataTables_wrapper .row {
        margin-top: 15px;
    }
    
    .price-range {
        font-weight: 600;
        color: #007bff;
    }
    
    /* --- Action Button Styling --- */
    .btn-action {
        padding: 5px 10px;
        font-size: 0.9rem;
        margin: 2px;
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
            <a href="manage_reports.php" class="active"><i class="fas fa-file-alt"></i> Manage Reports</a>
            <a href="ai_performance_dashboard.php"><i class="fas fa-robot"></i> AI Model</a>
        </div>
    </nav>
    
    <div id="content">
        <div class="header-card">
            <h2><i class="fas fa-brain me-2 text-primary"></i> AI Estimation Records</h2>
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
            <h5 class="fw-bold mb-3">All Generated AI Estimates</h5>
            <table id="estimatesTable" class="table table-striped table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Item Details</th>
                        <th>Price Range (KES)</th>
                        <th>Confidence</th>
                        <th>Depreciation</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($estimations) > 0): ?>
                        <?php foreach ($estimations as $e): ?>
                            <tr>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($e['ai_estimation_id']) ?></span></td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($e['item_title']) ?></span><br>
                                    <small class="text-muted"><i class="fas fa-tag me-1"></i>Brand: <?= htmlspecialchars($e['item_brand']) ?></small>
                                </td>
                                <td>
                                    <span class="price-range">
                                        <?= number_format($e['ai_estimation_price_min'], 2) ?> - <?= number_format($e['ai_estimation_price_max'], 2) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php $confidence = (float)$e['ai_estimation_confidence']; ?>
                                    <span class="badge <?= get_confidence_badge_class($confidence) ?>">
                                        <?= number_format($confidence, 1) ?>%
                                    </span>
                                </td>
                                <td>
                                    <?php $depreciation = (float)$e['ai_estimation_depreciation']; ?>
                                    <span class="badge <?= get_depreciation_badge_class($depreciation) ?>">
                                        <?= number_format($depreciation, 1) ?>%
                                    </span>
                                </td>
                                <td><span title="<?= htmlspecialchars($e['ai_estimation_notes']) ?>"><?= substr(htmlspecialchars($e['ai_estimation_notes']), 0, 50) . (strlen($e['ai_estimation_notes']) > 50 ? '...' : '') ?></span></td>
                                <td>
                                    <a href="edit_ai_estimate.php?id=<?= $e['ai_estimation_id'] ?>" class="btn btn-sm btn-primary btn-action" data-bs-toggle="tooltip" title="Edit this estimate">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                        class="btn btn-sm btn-danger btn-action"
                                        data-bs-toggle="modal"
                                        data-bs-target="#confirmDeleteModal"
                                        data-estimate-id="<?= $e['ai_estimation_id']; ?>"
                                        data-item-title="<?= htmlspecialchars($e['item_title']); ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4">
                            <i class="fas fa-info-circle me-2"></i> No AI estimates found in the system.
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
                <h5 class="modal-title" id="confirmDeleteModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Confirm Estimate Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the AI estimate for **<span id="itemTitlePlaceholder" class="fw-bold"></span>** (ID: <span id="estimateIdPlaceholder" class="fw-bold"></span>)?</p>
                <p class="text-danger">This action will permanently remove this AI-generated valuation record.</p>
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
    $('#estimatesTable').DataTable({
        "order": [[ 0, "desc" ]], // Default sort by ID descending
        "pagingType": "full_numbers",
        "language": {
            "search": "Filter estimates:",
            "info": "Showing _START_ to _END_ of _TOTAL_ estimates"
        },
        "columnDefs": [
            // Disable sorting on the Notes and Actions columns
            { "orderable": false, "targets": [5, 6] } 
        ]
    });

    // 2. Enable Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipTriggerList.forEach(t => new bootstrap.Tooltip(t));


    // 3. Handle Delete Modal Setup
    const deleteModal = document.getElementById('confirmDeleteModal');
    deleteModal.addEventListener('show.bs.modal', function (event) {
        // Button that triggered the modal
        const button = event.relatedTarget; 
        
        const estimateId = button.getAttribute('data-estimate-id');
        const itemTitle = button.getAttribute('data-item-title');
        
        // Update modal content and form data
        document.getElementById('estimateIdPlaceholder').textContent = estimateId;
        document.getElementById('itemTitlePlaceholder').textContent = itemTitle;
        
        // Assuming your deletion script is 'delete_ai_estimate.php'
        const deleteLink = document.getElementById('deleteEstimateLink');
        deleteLink.href = 'delete_ai_estimate.php?id=' + estimateId;
    });
});
</script>

</body>
</html>