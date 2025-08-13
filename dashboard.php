<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Get dashboard statistics
$total_passengers = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM passengers"))['count'];
$total_trains = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM trains"))['count'];
$total_stations = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM stations"))['count'];
$total_routes = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM routes"))['count'];

// Get today's bookings
$today_bookings = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM passengers WHERE DATE(created_at) = CURDATE()"))['count'];

// Get recent bookings
$recent_bookings = mysqli_query($db, "SELECT p.*, t.name as train_name, 
    fs.name as from_station, ts.name as to_station 
    FROM passengers p 
    LEFT JOIN trains t ON p.train_id = t.id 
    LEFT JOIN stations fs ON p.from_station_id = fs.id 
    LEFT JOIN stations ts ON p.to_station_id = ts.id 
    ORDER BY p.created_at DESC LIMIT 5");

// Get monthly revenue (assuming fare is stored)
$monthly_revenue = mysqli_fetch_assoc(mysqli_query($db, "SELECT SUM(fare) as revenue FROM passengers WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())"))['revenue'] ?? 0;
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="d-flex align-items-left align-items-md-center flex-column flex-md-row pt-2 pb-4">
            <div>
                <h3 class="fw-bold mb-3">Railway System Dashboard</h3>
                <h6 class="op-7 mb-2">Welcome to your railway management system</h6>
            </div>
            <div class="ms-md-auto py-2 py-md-0">
                <a href="passenger_booking.php" class="btn btn-primary btn-round">
                    <i class="fa fa-plus"></i> Book New Ticket
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Passengers</p>
                                    <h4 class="card-title"><?= number_format($total_passengers) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-train"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Trains</p>
                                    <h4 class="card-title"><?= number_format($total_trains) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-map-marker-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Stations</p>
                                    <h4 class="card-title"><?= number_format($total_stations) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-secondary bubble-shadow-small">
                                    <i class="fas fa-route"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Routes</p>
                                    <h4 class="card-title"><?= number_format($total_routes) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Stats -->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-round">
                    <div class="card-body">
                        <div class="card-head-row card-tools-still-right">
                            <div class="card-title">Today's Bookings</div>
                        </div>
                        <div class="card-category">Bookings made today</div>
                        <div class="d-flex">
                            <div class="avatar avatar-lg">
                                <span class="avatar-title rounded-circle border border-white bg-primary">
                                    <i class="fas fa-ticket-alt"></i>
                                </span>
                            </div>
                            <div class="info-user ms-3">
                                <div class="username"><?= number_format($today_bookings) ?> Bookings</div>
                                <div class="status">Today, <?= date('M d, Y') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-round">
                    <div class="card-body">
                        <div class="card-head-row card-tools-still-right">
                            <div class="card-title">Monthly Revenue</div>
                        </div>
                        <div class="card-category">Revenue for <?= date('F Y') ?></div>
                        <div class="d-flex">
                            <div class="avatar avatar-lg">
                                <span class="avatar-title rounded-circle border border-white bg-success">
                                    <i class="fas fa-rupee-sign"></i>
                                </span>
                            </div>
                            <div class="info-user ms-3">
                                <div class="username">₹<?= number_format($monthly_revenue, 2) ?></div>
                                <div class="status">This month</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Bookings -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-head-row">
                            <div class="card-title">Recent Bookings</div>
                            <div class="card-tools">
                                <a href="passenger_management.php" class="btn btn-label-success btn-round btn-sm me-2">
                                    <span class="btn-label">
                                        <i class="fa fa-eye"></i>
                                    </span>
                                    View All
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <?php if (mysqli_num_rows($recent_bookings) > 0): ?>
                                <table class="table align-items-center mb-0">
                                    <thead class="thead-light">
                                        <tr>
                                            <th scope="col">Passenger</th>
                                            <th scope="col">Route</th>
                                            <th scope="col">Train</th>
                                            <th scope="col">Date</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($booking = mysqli_fetch_assoc($recent_bookings)): ?>
                                            <tr>
                                                <td>
                                                    <div class="avatar avatar-sm">
                                                        <span class="avatar-title rounded-circle border border-white bg-primary text-white">
                                                            <?= strtoupper(substr($booking['name'], 0, 1)) ?>
                                                        </span>
                                                    </div>
                                                    <div class="info-user ms-3">
                                                        <div class="username"><?= htmlspecialchars($booking['name']) ?></div>
                                                        <div class="status"><?= htmlspecialchars($booking['email']) ?></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="text-muted"><?= htmlspecialchars($booking['from_station']) ?></span>
                                                    <i class="fas fa-arrow-right mx-1"></i>
                                                    <span class="text-muted"><?= htmlspecialchars($booking['to_station']) ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($booking['train_name']) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($booking['journey_date'])) ?></td>
                                                <td>
                                                    <span class="badge badge-success">Confirmed</span>
                                                </td>
                                                <td>
                                                    <a href="view_passenger.php?id=<?= $booking['id'] ?>" class="btn btn-link btn-primary btn-lg" data-bs-toggle="tooltip" title="View Ticket">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No recent bookings found</p>
                                    <a href="passenger_booking.php" class="btn btn-primary">Book First Ticket</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-12">
                <div class="card card-round">
                    <div class="card-header">
                        <div class="card-title">Quick Actions</div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="passenger_booking.php" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-ticket-alt mb-2"></i><br>
                                    Book Ticket
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="passenger_management.php" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-users mb-2"></i><br>
                                    Manage Passengers
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="train_management.php" class="btn btn-info btn-lg w-100">
                                    <i class="fas fa-train mb-2"></i><br>
                                    Manage Trains
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-3">
                                <a href="seats/passenger_details.php" class="btn btn-warning btn-lg w-100">
                                    <i class="fas fa-chair mb-2"></i><br>
                                    Seat Booking
                                </a>
                            </div>
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
    // Initialize tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();
    
    // Auto-refresh dashboard every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
});
</script>
