<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

$stations = $fun->get_all_stations();
$trains = $fun->get_all_trains();
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Passenger Booking</h3>
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
                    <a href="#">Passenger Booking</a>
                </li>
            </ul>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-ticket-alt"></i> Book New Ticket
                        </div>
                    </div>
                    <div class="card-body">
                        <form action="form.php" method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="name">Passenger Name</label>
                                        <input type="text" name="name" id="name" class="form-control" placeholder="Enter full name" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="age">Age</label>
                                        <input type="number" name="age" id="age" class="form-control" placeholder="Age" min="1" max="120" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="gender">Gender</label>
                                        <select name="gender" id="gender" class="form-select" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email Address</label>
                                        <input type="email" name="email" id="email" class="form-control" placeholder="Enter email address" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone Number</label>
                                        <input type="tel" name="phone" id="phone" class="form-control" placeholder="Enter phone number" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="from_station_id">From Station</label>
                                        <select name="from_station_id" id="from_station_id" class="form-select" required>
                                            <option value="">Select Departure Station</option>
                                            <?php foreach ($stations as $station): ?>
                                                <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="to_station_id">To Station</label>
                                        <select name="to_station_id" id="to_station_id" class="form-select" required>
                                            <option value="">Select Destination Station</option>
                                            <?php foreach ($stations as $station): ?>
                                                <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="train_id">Select Train</label>
                                        <select name="train_id" id="train_id" class="form-select" required>
                                            <option value="">Choose Train</option>
                                            <?php foreach ($trains as $train): ?>
                                                <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="class_type">Class Type</label>
                                        <select name="class_type" id="class_type" class="form-select" required>
                                            <option value="">Select Class</option>
                                            <option value="Economy">Economy</option>
                                            <option value="Business">Business</option>
                                            <option value="First Class">First Class</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="journey_date">Journey Date</label>
                                        <input type="date" name="journey_date" id="journey_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="password">Password</label>
                                        <input type="password" name="password" id="password" class="form-control" placeholder="Create a password" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="payment_mode">Payment Mode</label>
                                        <select name="payment_mode" id="payment_mode" class="form-select" required>
                                            <option value="">Select Payment Mode</option>
                                            <option value="Online">Online Payment</option>
                                            <option value="Cash">Cash Payment</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="card-action">
                                <button type="submit" class="btn btn-success btn-lg">
                                    <i class="fa fa-ticket-alt"></i> Book Ticket
                                </button>
                                <button type="reset" class="btn btn-secondary btn-lg">
                                    <i class="fa fa-undo"></i> Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card card-info">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-info-circle"></i> Booking Information
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h5><i class="icon fa fa-info"></i> Important Notes:</h5>
                            <ul class="mb-0">
                                <li>Please ensure all details are correct before booking</li>
                                <li>Tickets can be cancelled up to 2 hours before departure</li>
                                <li>Valid ID proof required during travel</li>
                                <li>Children below 5 years travel free</li>
                            </ul>
                        </div>

                        <div class="card card-secondary">
                            <div class="card-header">
                                <div class="card-title">Quick Actions</div>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="seats/passenger_details.php" class="btn btn-primary">
                                        <i class="fa fa-users"></i> Multiple Passenger Booking
                                    </a>
                                    <a href="passenger_management.php" class="btn btn-secondary">
                                        <i class="fa fa-list"></i> View All Passengers
                                    </a>
                                    <a href="train_management.php" class="btn btn-info">
                                        <i class="fa fa-train"></i> Manage Trains & Routes
                                    </a>
                                </div>
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
    // Set minimum date to today
    $('#journey_date').attr('min', new Date().toISOString().split('T')[0]);
    
    // Form validation
    $('form').on('submit', function(e) {
        var fromStation = $('#from_station_id').val();
        var toStation = $('#to_station_id').val();
        
        if (fromStation === toStation && fromStation !== '') {
            e.preventDefault();
            alert('From and To stations cannot be the same!');
            return false;
        }
    });
});
</script>
