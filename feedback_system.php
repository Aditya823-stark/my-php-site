<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create feedback tables
$create_feedback_table = "CREATE TABLE IF NOT EXISTS passenger_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    train_id INT NOT NULL,
    booking_id INT NULL,
    feedback_type ENUM('Review', 'Complaint', 'Suggestion', 'Compliment') NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    category ENUM('Service', 'Cleanliness', 'Food', 'Staff', 'Punctuality', 'Safety', 'Facilities', 'Other') DEFAULT 'Other',
    priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
    admin_response TEXT NULL,
    responded_by INT NULL,
    responded_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES passengers(id),
    FOREIGN KEY (train_id) REFERENCES trains(id),
    FOREIGN KEY (responded_by) REFERENCES admin_users(id)
)";
mysqli_query($db, $create_feedback_table);

$create_feedback_attachments = "CREATE TABLE IF NOT EXISTS feedback_attachments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (feedback_id) REFERENCES passenger_feedback(id) ON DELETE CASCADE
)";
mysqli_query($db, $create_feedback_attachments);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['respond_feedback'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        $admin_response = mysqli_real_escape_string($db, $_POST['admin_response']);
        $status = mysqli_real_escape_string($db, $_POST['status']);
        $responded_by = 1; // Current admin user ID
        
        $sql = "UPDATE passenger_feedback SET admin_response = ?, status = ?, responded_by = ?, responded_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "ssii", $admin_response, $status, $responded_by, $feedback_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Response added successfully!";
        } else {
            $error_msg = "Error adding response: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['update_priority'])) {
        $feedback_id = (int)$_POST['feedback_id'];
        $priority = mysqli_real_escape_string($db, $_POST['priority']);
        
        $sql = "UPDATE passenger_feedback SET priority = ? WHERE id = ?";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "si", $priority, $feedback_id);
        mysqli_stmt_execute($stmt);
    }
}

// Get feedback data
$feedback_query = "SELECT pf.*, p.name as passenger_name, p.email, t.name as train_name, 
                   au.username as responded_by_name
                   FROM passenger_feedback pf 
                   LEFT JOIN passengers p ON pf.passenger_id = p.id 
                   LEFT JOIN trains t ON pf.train_id = t.id 
                   LEFT JOIN admin_users au ON pf.responded_by = au.id 
                   ORDER BY pf.created_at DESC";
$feedback_data = mysqli_query($db, $feedback_query);

// Get statistics
$stats = [
    'total_feedback' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM passenger_feedback"))['count'],
    'pending_feedback' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM passenger_feedback WHERE status IN ('Open', 'In Progress')"))['count'],
    'avg_rating' => mysqli_fetch_assoc(mysqli_query($db, "SELECT AVG(rating) as avg FROM passenger_feedback WHERE rating IS NOT NULL"))['avg'] ?? 0,
    'critical_issues' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM passenger_feedback WHERE priority = 'Critical' AND status != 'Closed'"))['count']
];

$trains = $fun->get_all_trains();
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Passenger Feedback System</h3>
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
                    <a href="#">Feedback System</a>
                </li>
            </ul>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= $success_msg ?>
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
                                    <i class="fas fa-comments"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Feedback</p>
                                    <h4 class="card-title"><?= $stats['total_feedback'] ?></h4>
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
                                    <h4 class="card-title"><?= $stats['pending_feedback'] ?></h4>
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
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Avg Rating</p>
                                    <h4 class="card-title"><?= number_format($stats['avg_rating'], 1) ?>/5</h4>
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
                                <div class="icon-big text-center icon-danger bubble-shadow-small">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Critical Issues</p>
                                    <h4 class="card-title"><?= $stats['critical_issues'] ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feedback Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">
                                <i class="fas fa-list"></i> Passenger Feedback
                            </h4>
                            <div class="ms-auto">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterFeedback('all')">All</button>
                                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="filterFeedback('pending')">Pending</button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="filterFeedback('critical')">Critical</button>
                                    <button type="button" class="btn btn-outline-success btn-sm" onclick="filterFeedback('resolved')">Resolved</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="feedback-table" class="display table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 15%">Passenger</th>
                                        <th style="width: 10%">Type</th>
                                        <th style="width: 15%">Train</th>
                                        <th style="width: 20%">Subject</th>
                                        <th style="width: 8%">Rating</th>
                                        <th style="width: 10%">Priority</th>
                                        <th style="width: 10%">Status</th>
                                        <th style="width: 12%">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($feedback = mysqli_fetch_assoc($feedback_data)): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($feedback['passenger_name']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($feedback['email']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $feedback['feedback_type'] == 'Complaint' ? 'danger' : ($feedback['feedback_type'] == 'Review' ? 'info' : 'success') ?>">
                                                    <?= $feedback['feedback_type'] ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($feedback['train_name']) ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($feedback['subject']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars(substr($feedback['message'], 0, 50)) ?>...</small>
                                            </td>
                                            <td>
                                                <?php if ($feedback['rating']): ?>
                                                    <div class="rating">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i class="fas fa-star <?= $i <= $feedback['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                                        <?php endfor; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $feedback['priority'] == 'Critical' ? 'danger' : ($feedback['priority'] == 'High' ? 'warning' : 'secondary') ?>">
                                                    <?= $feedback['priority'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?= $feedback['status'] == 'Resolved' ? 'success' : ($feedback['status'] == 'In Progress' ? 'warning' : 'secondary') ?>">
                                                    <?= $feedback['status'] ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="form-button-action">
                                                    <button type="button" class="btn btn-link btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#viewModal<?= $feedback['id'] ?>" title="View Details">
                                                        <i class="fa fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-link btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#respondModal<?= $feedback['id'] ?>" title="Respond">
                                                        <i class="fa fa-reply"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?= $feedback['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Feedback Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Passenger:</strong> <?= htmlspecialchars($feedback['passenger_name']) ?></p>
                                                                <p><strong>Email:</strong> <?= htmlspecialchars($feedback['email']) ?></p>
                                                                <p><strong>Train:</strong> <?= htmlspecialchars($feedback['train_name']) ?></p>
                                                                <p><strong>Type:</strong> <?= $feedback['feedback_type'] ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Category:</strong> <?= $feedback['category'] ?></p>
                                                                <p><strong>Priority:</strong> <?= $feedback['priority'] ?></p>
                                                                <p><strong>Status:</strong> <?= $feedback['status'] ?></p>
                                                                <p><strong>Date:</strong> <?= date('M d, Y H:i', strtotime($feedback['created_at'])) ?></p>
                                                            </div>
                                                        </div>
                                                        <div class="mt-3">
                                                            <p><strong>Subject:</strong> <?= htmlspecialchars($feedback['subject']) ?></p>
                                                            <p><strong>Message:</strong></p>
                                                            <div class="bg-light p-3 rounded"><?= nl2br(htmlspecialchars($feedback['message'])) ?></div>
                                                        </div>
                                                        <?php if ($feedback['admin_response']): ?>
                                                            <div class="mt-3">
                                                                <p><strong>Admin Response:</strong></p>
                                                                <div class="bg-success bg-opacity-10 p-3 rounded border-start border-success border-3">
                                                                    <?= nl2br(htmlspecialchars($feedback['admin_response'])) ?>
                                                                    <br><small class="text-muted">Responded by <?= $feedback['responded_by_name'] ?> on <?= date('M d, Y H:i', strtotime($feedback['responded_at'])) ?></small>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Respond Modal -->
                                        <div class="modal fade" id="respondModal<?= $feedback['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Respond to Feedback</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="post">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="feedback_id" value="<?= $feedback['id'] ?>">
                                                            <div class="form-group">
                                                                <label>Response</label>
                                                                <textarea name="admin_response" class="form-control" rows="4" placeholder="Enter your response..." required><?= htmlspecialchars($feedback['admin_response']) ?></textarea>
                                                            </div>
                                                            <div class="form-group">
                                                                <label>Status</label>
                                                                <select name="status" class="form-select" required>
                                                                    <option value="Open" <?= $feedback['status'] == 'Open' ? 'selected' : '' ?>>Open</option>
                                                                    <option value="In Progress" <?= $feedback['status'] == 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                                                                    <option value="Resolved" <?= $feedback['status'] == 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                                                                    <option value="Closed" <?= $feedback['status'] == 'Closed' ? 'selected' : '' ?>>Closed</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="respond_feedback" class="btn btn-primary">Send Response</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#feedback-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 0, "desc" ]]
    });
});

function filterFeedback(type) {
    var table = $('#feedback-table').DataTable();
    
    switch(type) {
        case 'pending':
            table.columns(6).search('Open|In Progress', true, false).draw();
            break;
        case 'critical':
            table.columns(5).search('Critical').draw();
            break;
        case 'resolved':
            table.columns(6).search('Resolved|Closed', true, false).draw();
            break;
        default:
            table.search('').columns().search('').draw();
    }
}
</script>
