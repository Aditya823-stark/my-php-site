<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create maintenance_logs table if not exists
$create_maintenance_table = "CREATE TABLE IF NOT EXISTS maintenance_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    maintenance_type ENUM('Routine', 'Emergency', 'Scheduled', 'Preventive') DEFAULT 'Routine',
    maintenance_category ENUM('Engine', 'Brakes', 'Electrical', 'Interior', 'Exterior', 'Safety', 'Other') DEFAULT 'Other',
    description TEXT NOT NULL,
    maintenance_date DATE NOT NULL,
    start_time TIME,
    end_time TIME,
    technician_name VARCHAR(100),
    cost DECIMAL(10,2) DEFAULT 0,
    parts_replaced TEXT,
    next_maintenance_due DATE,
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id)
)";
mysqli_query($db, $create_maintenance_table);

// Create maintenance_schedules table for recurring maintenance
$create_schedule_table = "CREATE TABLE IF NOT EXISTS maintenance_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    maintenance_type VARCHAR(100) NOT NULL,
    frequency_days INT NOT NULL,
    last_maintenance DATE,
    next_due_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id)
)";
mysqli_query($db, $create_schedule_table);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_maintenance'])) {
        $train_id = (int)$_POST['train_id'];
        $maintenance_type = mysqli_real_escape_string($db, $_POST['maintenance_type']);
        $category = mysqli_real_escape_string($db, $_POST['maintenance_category']);
        $description = mysqli_real_escape_string($db, $_POST['description']);
        $maintenance_date = $_POST['maintenance_date'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $technician = mysqli_real_escape_string($db, $_POST['technician_name']);
        $cost = (float)$_POST['cost'];
        $parts = mysqli_real_escape_string($db, $_POST['parts_replaced']);
        $next_due = $_POST['next_maintenance_due'];
        $priority = mysqli_real_escape_string($db, $_POST['priority']);
        $status = mysqli_real_escape_string($db, $_POST['status']);
        $notes = mysqli_real_escape_string($db, $_POST['notes']);
        $created_by = 'Admin'; // In real system, get from session
        
        $sql = "INSERT INTO maintenance_logs (train_id, maintenance_type, maintenance_category, description, 
                maintenance_date, start_time, end_time, technician_name, cost, parts_replaced, 
                next_maintenance_due, priority, status, notes, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "isssssssdssssss", $train_id, $maintenance_type, $category, 
                              $description, $maintenance_date, $start_time, $end_time, $technician, 
                              $cost, $parts, $next_due, $priority, $status, $notes, $created_by);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Maintenance log added successfully!";
        } else {
            $error_msg = "Error adding maintenance log: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['update_status'])) {
        $log_id = (int)$_POST['log_id'];
        $new_status = mysqli_real_escape_string($db, $_POST['new_status']);
        
        mysqli_query($db, "UPDATE maintenance_logs SET status = '$new_status' WHERE id = $log_id");
        $success_msg = "Maintenance status updated successfully!";
    }
}

$trains = $fun->get_all_trains();

// Get maintenance logs with train names
$logs_query = "SELECT ml.*, t.name as train_name 
               FROM maintenance_logs ml 
               LEFT JOIN trains t ON ml.train_id = t.id 
               ORDER BY ml.maintenance_date DESC, ml.created_at DESC";
$logs = mysqli_query($db, $logs_query);

// Get maintenance statistics
$stats = [
    'total_logs' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM maintenance_logs"))['count'],
    'pending' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM maintenance_logs WHERE status IN ('Scheduled', 'In Progress')"))['count'],
    'completed_this_month' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM maintenance_logs WHERE status = 'Completed' AND MONTH(maintenance_date) = MONTH(CURDATE())"))['count'],
    'total_cost_this_month' => mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(cost) as total FROM maintenance_logs WHERE MONTH(maintenance_date) = MONTH(CURDATE())"))['total'] ?? 0
];

// Get overdue maintenance
$overdue_query = "SELECT ml.*, t.name as train_name 
                  FROM maintenance_logs ml 
                  LEFT JOIN trains t ON ml.train_id = t.id 
                  WHERE ml.next_maintenance_due < CURDATE() AND ml.status = 'Completed'
                  ORDER BY ml.next_maintenance_due ASC";
$overdue = mysqli_query($db, $overdue_query);
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Train Maintenance Management</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="index.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Operations</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Maintenance Logs</a>
                </li>
            </ul>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Error!</strong> <?= $error_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-tools"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Maintenance</p>
                                    <h4 class="card-title"><?= $stats['total_logs'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Pending</p>
                                    <h4 class="card-title"><?= $stats['pending'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Completed This Month</p>
                                    <h4 class="card-title"><?= $stats['completed_this_month'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Monthly Cost</p>
                                    <h4 class="card-title">₹<?= number_format($stats['total_cost_this_month'], 0) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Maintenance Alert -->
        <?php if (mysqli_num_rows($overdue) > 0): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-exclamation-triangle"></i> Overdue Maintenance Alert</h5>
                        <p>The following trains have overdue maintenance:</p>
                        <ul class="mb-0">
                            <?php while ($overdue_item = mysqli_fetch_assoc($overdue)): ?>
                                <li>
                                    <strong><?= htmlspecialchars($overdue_item['train_name']) ?></strong> - 
                                    Due: <?= date('M d, Y', strtotime($overdue_item['next_maintenance_due'])) ?> 
                                    (<?= abs((strtotime($overdue_item['next_maintenance_due']) - time()) / (60*60*24)) ?> days overdue)
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Add Maintenance Log -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-plus"></i> Add Maintenance Log
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="train_id">Select Train</label>
                                <select name="train_id" id="train_id" class="form-select" required>
                                    <option value="">Choose Train</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="maintenance_type">Type</label>
                                        <select name="maintenance_type" id="maintenance_type" class="form-select" required>
                                            <option value="Routine">Routine</option>
                                            <option value="Emergency">Emergency</option>
                                            <option value="Scheduled">Scheduled</option>
                                            <option value="Preventive">Preventive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="maintenance_category">Category</label>
                                        <select name="maintenance_category" id="maintenance_category" class="form-select" required>
                                            <option value="Engine">Engine</option>
                                            <option value="Brakes">Brakes</option>
                                            <option value="Electrical">Electrical</option>
                                            <option value="Interior">Interior</option>
                                            <option value="Exterior">Exterior</option>
                                            <option value="Safety">Safety</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3" placeholder="Describe the maintenance work" required></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="maintenance_date">Maintenance Date</label>
                                <input type="date" name="maintenance_date" id="maintenance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_time">Start Time</label>
                                        <input type="time" name="start_time" id="start_time" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="end_time">End Time</label>
                                        <input type="time" name="end_time" id="end_time" class="form-control">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="technician_name">Technician Name</label>
                                <input type="text" name="technician_name" id="technician_name" class="form-control" placeholder="Enter technician name">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="cost">Cost (₹)</label>
                                        <input type="number" step="0.01" name="cost" id="cost" class="form-control" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="priority">Priority</label>
                                        <select name="priority" id="priority" class="form-select" required>
                                            <option value="Low">Low</option>
                                            <option value="Medium" selected>Medium</option>
                                            <option value="High">High</option>
                                            <option value="Critical">Critical</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="parts_replaced">Parts Replaced</label>
                                <textarea name="parts_replaced" id="parts_replaced" class="form-control" rows="2" placeholder="List parts that were replaced"></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="next_maintenance_due">Next Maintenance Due</label>
                                <input type="date" name="next_maintenance_due" id="next_maintenance_due" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-select" required>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="In Progress">In Progress</option>
                                    <option value="Completed">Completed</option>
                                    <option value="Cancelled">Cancelled</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="notes">Additional Notes</label>
                                <textarea name="notes" id="notes" class="form-control" rows="2" placeholder="Any additional notes"></textarea>
                            </div>
                            
                            <div class="card-action">
                                <button type="submit" name="add_maintenance" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add Maintenance Log
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Maintenance Logs Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Maintenance Logs
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($logs) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No maintenance logs found. Add some logs to get started.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="maintenance-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Train</th>
                                            <th>Type</th>
                                            <th>Category</th>
                                            <th>Date</th>
                                            <th>Technician</th>
                                            <th>Cost</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($log['train_name']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($log['maintenance_type']) ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?= htmlspecialchars($log['maintenance_category']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($log['maintenance_date'])) ?></td>
                                                <td><?= htmlspecialchars($log['technician_name']) ?: '<span class="text-muted">N/A</span>' ?></td>
                                                <td>₹<?= number_format($log['cost'], 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $log['priority'] == 'Critical' ? 'danger' : ($log['priority'] == 'High' ? 'warning' : ($log['priority'] == 'Medium' ? 'info' : 'secondary')) ?>">
                                                        <?= htmlspecialchars($log['priority']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $log['status'] == 'Completed' ? 'success' : ($log['status'] == 'In Progress' ? 'warning' : ($log['status'] == 'Cancelled' ? 'danger' : 'secondary')) ?>">
                                                        <?= htmlspecialchars($log['status']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-link btn-info btn-lg" 
                                                                data-bs-toggle="tooltip" title="View Details"
                                                                onclick="viewMaintenanceDetails(<?= $log['id'] ?>)">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                        <?php if ($log['status'] != 'Completed'): ?>
                                                            <button type="button" class="btn btn-link btn-success btn-lg" 
                                                                    data-bs-toggle="tooltip" title="Update Status"
                                                                    onclick="updateStatus(<?= $log['id'] ?>, '<?= $log['status'] ?>')">
                                                                <i class="fa fa-edit"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStatusModalLabel">Update Maintenance Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="log_id" id="modal_log_id">
                    <div class="form-group">
                        <label for="new_status">New Status</label>
                        <select name="new_status" id="new_status" class="form-select" required>
                            <option value="Scheduled">Scheduled</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#maintenance-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 3, "desc" ]]
    });
});

function updateStatus(logId, currentStatus) {
    $('#modal_log_id').val(logId);
    $('#new_status').val(currentStatus);
    $('#updateStatusModal').modal('show');
}

function viewMaintenanceDetails(logId) {
    // In a real system, this would load detailed maintenance information
    alert('View maintenance details for log ID: ' + logId);
}
</script>
