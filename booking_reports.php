<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$train_filter = $_GET['train_id'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build query with filters
$where_conditions = [];
$where_conditions[] = "DATE(p.journey_date) BETWEEN '$date_from' AND '$date_to'";

if ($train_filter) {
    $where_conditions[] = "p.train_id = " . (int)$train_filter;
}

if ($status_filter) {
    $where_conditions[] = "p.status = '" . mysqli_real_escape_string($db, $status_filter) . "'";
}

$where_clause = implode(' AND ', $where_conditions);

// Get bookings with filters
$bookings_query = "SELECT p.*, t.name as train_name, 
    fs.name as from_station, ts.name as to_station 
    FROM passengers p 
    LEFT JOIN trains t ON p.train_id = t.id 
    LEFT JOIN stations fs ON p.from_station_id = fs.id 
    LEFT JOIN stations ts ON p.to_station_id = ts.id 
    WHERE $where_clause
    ORDER BY p.journey_date DESC, p.created_at DESC";

$bookings = mysqli_query($db, $bookings_query);

// Get summary statistics
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    SUM(fare) as total_revenue,
    AVG(fare) as avg_fare,
    COUNT(DISTINCT train_id) as trains_used
    FROM passengers p 
    WHERE $where_clause";

$stats = mysqli_fetch_assoc(mysqli_query($db, $stats_query));

$trains = $fun->get_all_trains();
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Booking Reports</h3>
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
                    <a href="#">Reports</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Booking Reports</a>
                </li>
            </ul>
        </div>

        <!-- Filter Card -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-filter"></i> Filter Reports
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" name="date_from" id="date_from" class="form-control" value="<?= $date_from ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" name="date_to" id="date_to" class="form-control" value="<?= $date_to ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="train_id" class="form-label">Train</label>
                                <select name="train_id" id="train_id" class="form-select">
                                    <option value="">All Trains</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>" <?= $train_filter == $train['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($train['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="Confirmed" <?= $status_filter == 'Confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="Cancelled" <?= $status_filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                    <option value="Pending" <?= $status_filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-search"></i> Apply Filters
                                </button>
                                <a href="booking_reports.php" class="btn btn-secondary">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                                <button type="button" class="btn btn-success" onclick="exportToCSV()">
                                    <i class="fa fa-download"></i> Export CSV
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-ticket-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Bookings</p>
                                    <h4 class="card-title"><?= number_format($stats['total_bookings']) ?></h4>
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
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Revenue</p>
                                    <h4 class="card-title">₹<?= number_format($stats['total_revenue'], 2) ?></h4>
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
                                    <i class="fas fa-calculator"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Average Fare</p>
                                    <h4 class="card-title">₹<?= number_format($stats['avg_fare'], 2) ?></h4>
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
                                    <i class="fas fa-train"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Trains Used</p>
                                    <h4 class="card-title"><?= number_format($stats['trains_used']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bookings Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Booking Details
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($bookings) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No bookings found for the selected criteria.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="bookings-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Passenger</th>
                                            <th>Contact</th>
                                            <th>Route</th>
                                            <th>Train</th>
                                            <th>Journey Date</th>
                                            <th>Class</th>
                                            <th>Fare</th>
                                            <th>Status</th>
                                            <th>Booked On</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($bookings)): ?>
                                            <tr>
                                                <td><?= $booking['id'] ?></td>
                                                <td>
                                                    <strong><?= htmlspecialchars($booking['name']) ?></strong><br>
                                                    <small class="text-muted">Age: <?= $booking['age'] ?>, <?= $booking['gender'] ?></small>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?= htmlspecialchars($booking['email']) ?><br>
                                                        <?= htmlspecialchars($booking['phone']) ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="text-muted"><?= htmlspecialchars($booking['from_station']) ?></span><br>
                                                    <i class="fas fa-arrow-down text-primary"></i><br>
                                                    <span class="text-muted"><?= htmlspecialchars($booking['to_station']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($booking['train_name']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($booking['journey_date'])) ?></td>
                                                <td>
                                                    <span class="badge badge-secondary">
                                                        <?= htmlspecialchars($booking['class_type']) ?>
                                                    </span>
                                                </td>
                                                <td>₹<?= number_format($booking['fare'], 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= strtolower($booking['status']) === 'cancelled' ? 'danger' : 'success' ?>">
                                                        <?= htmlspecialchars($booking['status']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($booking['created_at'])) ?></td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <a href="view_passenger.php?id=<?= $booking['id'] ?>" 
                                                           class="btn btn-link btn-primary btn-lg" 
                                                           data-bs-toggle="tooltip" 
                                                           title="View Ticket">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
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

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#bookings-table').DataTable({
        "pageLength": 25,
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 0, "desc" ]]
    });
});

function exportToCSV() {
    var table = $('#bookings-table').DataTable();
    var data = table.rows({ search: 'applied' }).data();
    
    var csv = 'ID,Passenger Name,Email,Phone,From Station,To Station,Train,Journey Date,Class,Fare,Status,Booked On\n';
    
    data.each(function(row, index) {
        var rowData = table.row(index).node();
        var cells = $(rowData).find('td');
        
        csv += $(cells[0]).text() + ',';
        csv += '"' + $(cells[1]).find('strong').text() + '",';
        csv += '"' + $(cells[2]).text().split('\n')[0] + '",';
        csv += '"' + $(cells[2]).text().split('\n')[1] + '",';
        csv += '"' + $(cells[3]).text().split('\n')[0] + '",';
        csv += '"' + $(cells[3]).text().split('\n')[2] + '",';
        csv += '"' + $(cells[4]).text() + '",';
        csv += '"' + $(cells[5]).text() + '",';
        csv += '"' + $(cells[6]).text() + '",';
        csv += $(cells[7]).text() + ',';
        csv += '"' + $(cells[8]).text() + '",';
        csv += '"' + $(cells[9]).text() + '"\n';
    });
    
    var blob = new Blob([csv], { type: 'text/csv' });
    var url = window.URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.setAttribute('hidden', '');
    a.setAttribute('href', url);
    a.setAttribute('download', 'booking_reports_' + new Date().toISOString().split('T')[0] + '.csv');
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}
</script>
