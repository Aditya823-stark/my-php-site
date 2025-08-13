<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create cancellation_requests table if not exists
$create_cancellation_table = "CREATE TABLE IF NOT EXISTS cancellation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    cancellation_reason TEXT,
    cancellation_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    refund_amount DECIMAL(10,2) DEFAULT 0,
    cancellation_charges DECIMAL(10,2) DEFAULT 0,
    refund_status ENUM('Pending', 'Processed', 'Rejected') DEFAULT 'Pending',
    processed_by INT NULL,
    processed_date TIMESTAMP NULL,
    refund_method ENUM('Original Payment', 'Bank Transfer', 'Cash', 'Wallet') DEFAULT 'Original Payment',
    bank_details TEXT NULL,
    admin_notes TEXT NULL,
    FOREIGN KEY (passenger_id) REFERENCES passengers(id)
)";
mysqli_query($db, $create_cancellation_table);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['process_cancellation'])) {
        $cancellation_id = (int)$_POST['cancellation_id'];
        $refund_status = mysqli_real_escape_string($db, $_POST['refund_status']);
        $refund_amount = (float)$_POST['refund_amount'];
        $cancellation_charges = (float)$_POST['cancellation_charges'];
        $refund_method = mysqli_real_escape_string($db, $_POST['refund_method']);
        $admin_notes = mysqli_real_escape_string($db, $_POST['admin_notes']);
        
        // Update cancellation request
        $update_sql = "UPDATE cancellation_requests SET 
                      refund_status = ?, 
                      refund_amount = ?, 
                      cancellation_charges = ?, 
                      refund_method = ?, 
                      admin_notes = ?, 
                      processed_date = NOW() 
                      WHERE id = ?";
        $stmt = mysqli_prepare($db, $update_sql);
        mysqli_stmt_bind_param($stmt, "sddssi", $refund_status, $refund_amount, $cancellation_charges, $refund_method, $admin_notes, $cancellation_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update passenger status if approved
            if ($refund_status === 'Processed') {
                $passenger_id = mysqli_fetch_assoc(mysqli_query($db, "SELECT passenger_id FROM cancellation_requests WHERE id = $cancellation_id"))['passenger_id'];
                mysqli_query($db, "UPDATE passengers SET status = 'Cancelled' WHERE id = $passenger_id");
            }
            $success_msg = "Cancellation request processed successfully!";
        } else {
            $error_msg = "Error processing cancellation: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['request_cancellation'])) {
        $passenger_id = (int)$_POST['passenger_id'];
        $reason = mysqli_real_escape_string($db, $_POST['cancellation_reason']);
        
        // Get passenger details for refund calculation
        $passenger = mysqli_fetch_assoc(mysqli_query($db, "SELECT * FROM passengers WHERE id = $passenger_id"));
        
        if ($passenger) {
            // Calculate refund amount based on cancellation policy
            $journey_date = $passenger['journey_date'];
            $current_date = date('Y-m-d');
            $days_difference = (strtotime($journey_date) - strtotime($current_date)) / (60 * 60 * 24);
            
            $original_fare = $passenger['fare'];
            $cancellation_charges = 0;
            $refund_amount = $original_fare;
            
            // Cancellation policy
            if ($days_difference >= 7) {
                $cancellation_charges = $original_fare * 0.10; // 10% charges
            } elseif ($days_difference >= 2) {
                $cancellation_charges = $original_fare * 0.25; // 25% charges
            } elseif ($days_difference >= 0.5) {
                $cancellation_charges = $original_fare * 0.50; // 50% charges
            } else {
                $cancellation_charges = $original_fare; // No refund
                $refund_amount = 0;
            }
            
            $refund_amount = $original_fare - $cancellation_charges;
            
            $insert_sql = "INSERT INTO cancellation_requests (passenger_id, cancellation_reason, refund_amount, cancellation_charges) 
                          VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($db, $insert_sql);
            mysqli_stmt_bind_param($stmt, "isdd", $passenger_id, $reason, $refund_amount, $cancellation_charges);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Cancellation request submitted successfully!";
            } else {
                $error_msg = "Error submitting cancellation request: " . mysqli_error($db);
            }
        }
    }
}

// Get all cancellation requests
$cancellations_query = "SELECT cr.*, p.name, p.email, p.phone, p.journey_date, p.fare, 
                       t.name as train_name, fs.name as from_station, ts.name as to_station
                       FROM cancellation_requests cr
                       LEFT JOIN passengers p ON cr.passenger_id = p.id
                       LEFT JOIN trains t ON p.train_id = t.id
                       LEFT JOIN stations fs ON p.from_station_id = fs.id
                       LEFT JOIN stations ts ON p.to_station_id = ts.id
                       ORDER BY cr.cancellation_date DESC";
$cancellations = mysqli_query($db, $cancellations_query);

// Get passengers for new cancellation
$active_passengers = mysqli_query($db, "SELECT p.*, t.name as train_name 
                                       FROM passengers p 
                                       LEFT JOIN trains t ON p.train_id = t.id 
                                       WHERE p.status != 'Cancelled' AND p.journey_date >= CURDATE()
                                       ORDER BY p.journey_date ASC");
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Ticket Cancellation & Refund Management</h3>
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
                    <a href="#">Railway Management</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Cancellation Management</a>
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

        <!-- Cancellation Policy Card -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card card-info">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-info-circle"></i> Cancellation Policy
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-calendar-check fa-2x text-success mb-2"></i>
                                    <h6>7+ Days Before</h6>
                                    <p class="text-muted">10% Cancellation Charges</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-calendar-alt fa-2x text-info mb-2"></i>
                                    <h6>2-7 Days Before</h6>
                                    <p class="text-muted">25% Cancellation Charges</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-calendar-times fa-2x text-warning mb-2"></i>
                                    <h6>12 Hours - 2 Days</h6>
                                    <p class="text-muted">50% Cancellation Charges</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center">
                                    <i class="fas fa-ban fa-2x text-danger mb-2"></i>
                                    <h6>Less than 12 Hours</h6>
                                    <p class="text-muted">No Refund</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- New Cancellation Request -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-times-circle"></i> New Cancellation Request
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="passenger_id">Select Passenger</label>
                                <select name="passenger_id" id="passenger_id" class="form-select" required>
                                    <option value="">Choose Passenger</option>
                                    <?php while ($passenger = mysqli_fetch_assoc($active_passengers)): ?>
                                        <option value="<?= $passenger['id'] ?>" 
                                                data-fare="<?= $passenger['fare'] ?>"
                                                data-journey="<?= $passenger['journey_date'] ?>">
                                            <?= htmlspecialchars($passenger['name']) ?> - 
                                            <?= htmlspecialchars($passenger['train_name']) ?> - 
                                            <?= date('M d, Y', strtotime($passenger['journey_date'])) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="cancellation_reason">Cancellation Reason</label>
                                <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="3" placeholder="Enter reason for cancellation" required></textarea>
                            </div>
                            
                            <div id="refund-preview" class="alert alert-info" style="display: none;">
                                <h6>Refund Preview</h6>
                                <div id="refund-details"></div>
                            </div>
                            
                            <div class="card-action">
                                <button type="submit" name="request_cancellation" class="btn btn-danger">
                                    <i class="fa fa-times"></i> Submit Cancellation Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Cancellation Requests Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Cancellation Requests
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($cancellations) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No cancellation requests found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="cancellations-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Passenger</th>
                                            <th>Journey Details</th>
                                            <th>Original Fare</th>
                                            <th>Refund Amount</th>
                                            <th>Charges</th>
                                            <th>Status</th>
                                            <th>Requested On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($cancellation = mysqli_fetch_assoc($cancellations)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($cancellation['name']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($cancellation['email']) ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary"><?= htmlspecialchars($cancellation['train_name']) ?></span><br>
                                                    <small><?= htmlspecialchars($cancellation['from_station']) ?> → <?= htmlspecialchars($cancellation['to_station']) ?></small><br>
                                                    <small class="text-muted"><?= date('M d, Y', strtotime($cancellation['journey_date'])) ?></small>
                                                </td>
                                                <td>₹<?= number_format($cancellation['fare'], 2) ?></td>
                                                <td>₹<?= number_format($cancellation['refund_amount'], 2) ?></td>
                                                <td>₹<?= number_format($cancellation['cancellation_charges'], 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $cancellation['refund_status'] == 'Processed' ? 'success' : ($cancellation['refund_status'] == 'Rejected' ? 'danger' : 'warning') ?>">
                                                        <?= $cancellation['refund_status'] ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y H:i', strtotime($cancellation['cancellation_date'])) ?></td>
                                                <td>
                                                    <?php if ($cancellation['refund_status'] == 'Pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-primary" 
                                                                onclick="processCancellation(<?= $cancellation['id'] ?>, '<?= htmlspecialchars($cancellation['name']) ?>', <?= $cancellation['refund_amount'] ?>, <?= $cancellation['cancellation_charges'] ?>)">
                                                            Process
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">Processed</span>
                                                    <?php endif; ?>
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

<!-- Process Cancellation Modal -->
<div class="modal fade" id="processCancellationModal" tabindex="-1" aria-labelledby="processCancellationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <div class="modal-header">
                    <h5 class="modal-title" id="processCancellationModalLabel">Process Cancellation Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="cancellation_id" id="modal_cancellation_id">
                    
                    <div class="form-group">
                        <label>Passenger Name</label>
                        <input type="text" id="modal_passenger_name" class="form-control" readonly>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund_status">Refund Status</label>
                        <select name="refund_status" id="refund_status" class="form-select" required>
                            <option value="Processed">Approve & Process Refund</option>
                            <option value="Rejected">Reject Request</option>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="refund_amount">Refund Amount (₹)</label>
                                <input type="number" step="0.01" name="refund_amount" id="modal_refund_amount" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cancellation_charges">Cancellation Charges (₹)</label>
                                <input type="number" step="0.01" name="cancellation_charges" id="modal_cancellation_charges" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="refund_method">Refund Method</label>
                        <select name="refund_method" id="refund_method" class="form-select" required>
                            <option value="Original Payment">Original Payment Method</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Wallet">Wallet Credit</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_notes">Admin Notes</label>
                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3" placeholder="Add any notes about this cancellation"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="process_cancellation" class="btn btn-primary">Process Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#cancellations-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 6, "desc" ]]
    });

    // Calculate refund preview when passenger is selected
    $('#passenger_id').change(function() {
        var selectedOption = $(this).find(':selected');
        var fare = parseFloat(selectedOption.data('fare')) || 0;
        var journeyDate = selectedOption.data('journey');
        
        if (fare > 0 && journeyDate) {
            var today = new Date();
            var journey = new Date(journeyDate);
            var timeDiff = journey.getTime() - today.getTime();
            var daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24));
            
            var charges = 0;
            var refund = fare;
            var policy = '';
            
            if (daysDiff >= 7) {
                charges = fare * 0.10;
                policy = '10% cancellation charges (7+ days before journey)';
            } else if (daysDiff >= 2) {
                charges = fare * 0.25;
                policy = '25% cancellation charges (2-7 days before journey)';
            } else if (daysDiff >= 0.5) {
                charges = fare * 0.50;
                policy = '50% cancellation charges (12 hours - 2 days before journey)';
            } else {
                charges = fare;
                refund = 0;
                policy = 'No refund (less than 12 hours before journey)';
            }
            
            refund = fare - charges;
            
            $('#refund-details').html(`
                <strong>Original Fare:</strong> ₹${fare.toFixed(2)}<br>
                <strong>Cancellation Charges:</strong> ₹${charges.toFixed(2)}<br>
                <strong>Refund Amount:</strong> ₹${refund.toFixed(2)}<br>
                <small class="text-muted">${policy}</small>
            `);
            $('#refund-preview').show();
        } else {
            $('#refund-preview').hide();
        }
    });
});

function processCancellation(id, name, refundAmount, charges) {
    $('#modal_cancellation_id').val(id);
    $('#modal_passenger_name').val(name);
    $('#modal_refund_amount').val(refundAmount);
    $('#modal_cancellation_charges').val(charges);
    $('#processCancellationModal').modal('show');
}
</script>
