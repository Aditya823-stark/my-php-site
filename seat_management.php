<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create seat_layout table if not exists
$create_seat_table = "CREATE TABLE IF NOT EXISTS seat_layouts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    coach_number VARCHAR(10) NOT NULL,
    coach_type ENUM('Sleeper', 'AC1', 'AC2', 'AC3', 'General', 'Chair Car') DEFAULT 'Sleeper',
    total_seats INT NOT NULL DEFAULT 72,
    seat_configuration VARCHAR(20) DEFAULT '3x2',
    base_fare DECIMAL(10,2) DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id)
)";
mysqli_query($db, $create_seat_table);

// Create seat_bookings table for detailed seat tracking
$create_booking_table = "CREATE TABLE IF NOT EXISTS seat_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    train_id INT NOT NULL,
    coach_number VARCHAR(10) NOT NULL,
    seat_number VARCHAR(10) NOT NULL,
    journey_date DATE NOT NULL,
    booking_status ENUM('Confirmed', 'Waitlisted', 'Cancelled') DEFAULT 'Confirmed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES passengers(id),
    FOREIGN KEY (train_id) REFERENCES trains(id),
    UNIQUE KEY unique_seat_date (train_id, coach_number, seat_number, journey_date)
)";
mysqli_query($db, $create_booking_table);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_coach'])) {
        $train_id = (int)$_POST['train_id'];
        $coach_number = mysqli_real_escape_string($db, $_POST['coach_number']);
        $coach_type = mysqli_real_escape_string($db, $_POST['coach_type']);
        $total_seats = (int)$_POST['total_seats'];
        $seat_config = mysqli_real_escape_string($db, $_POST['seat_configuration']);
        $base_fare = (float)$_POST['base_fare'];
        
        $sql = "INSERT INTO seat_layouts (train_id, coach_number, coach_type, total_seats, seat_configuration, base_fare) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "ississ", $train_id, $coach_number, $coach_type, $total_seats, $seat_config, $base_fare);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Coach layout added successfully!";
        } else {
            $error_msg = "Error adding coach: " . mysqli_error($db);
        }
    }
}

$trains = $fun->get_all_trains();

// Get seat layouts
$layouts_query = "SELECT sl.*, t.name as train_name 
                 FROM seat_layouts sl 
                 LEFT JOIN trains t ON sl.train_id = t.id 
                 ORDER BY t.name, sl.coach_number";
$layouts = mysqli_query($db, $layouts_query);
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Seat Management & Visualization</h3>
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
                    <a href="#">Seat Management</a>
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

        <div class="row">
            <!-- Add Coach Layout -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-chair"></i> Add Coach Layout
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
                            
                            <div class="form-group">
                                <label for="coach_number">Coach Number</label>
                                <input type="text" name="coach_number" id="coach_number" class="form-control" placeholder="e.g., S1, A1, B2" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="coach_type">Coach Type</label>
                                <select name="coach_type" id="coach_type" class="form-select" required>
                                    <option value="General">General</option>
                                    <option value="Sleeper">Sleeper</option>
                                    <option value="AC3">AC 3 Tier</option>
                                    <option value="AC2">AC 2 Tier</option>
                                    <option value="AC1">AC 1 Tier</option>
                                    <option value="Chair Car">Chair Car</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="total_seats">Total Seats</label>
                                <input type="number" name="total_seats" id="total_seats" class="form-control" value="72" min="20" max="100" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="seat_configuration">Seat Configuration</label>
                                <select name="seat_configuration" id="seat_configuration" class="form-select" required>
                                    <option value="3x2">3x2 (Sleeper)</option>
                                    <option value="2x2">2x2 (AC Chair)</option>
                                    <option value="2x1">2x1 (AC 1st Class)</option>
                                    <option value="3x3">3x3 (General)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="base_fare">Base Fare (₹)</label>
                                <input type="number" step="0.01" name="base_fare" id="base_fare" class="form-control" placeholder="Base fare for this coach type" required>
                            </div>
                            
                            <div class="card-action">
                                <button type="submit" name="add_coach" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add Coach
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Coach Layouts Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Coach Layouts
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($layouts) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No coach layouts found. Add some coaches to get started.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="layouts-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Train</th>
                                            <th>Coach</th>
                                            <th>Type</th>
                                            <th>Seats</th>
                                            <th>Configuration</th>
                                            <th>Base Fare</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($layout = mysqli_fetch_assoc($layouts)): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($layout['train_name']) ?>
                                                    </span>
                                                </td>
                                                <td><strong><?= htmlspecialchars($layout['coach_number']) ?></strong></td>
                                                <td>
                                                    <span class="badge badge-<?= $layout['coach_type'] == 'General' ? 'secondary' : ($layout['coach_type'] == 'AC1' ? 'success' : 'info') ?>">
                                                        <?= htmlspecialchars($layout['coach_type']) ?>
                                                    </span>
                                                </td>
                                                <td><?= $layout['total_seats'] ?></td>
                                                <td><?= htmlspecialchars($layout['seat_configuration']) ?></td>
                                                <td>₹<?= number_format($layout['base_fare'], 2) ?></td>
                                                <td>
                                                    <span class="badge badge-<?= $layout['is_active'] ? 'success' : 'danger' ?>">
                                                        <?= $layout['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-link btn-info btn-lg" 
                                                                data-bs-toggle="tooltip" title="View Seat Map"
                                                                onclick="showSeatMap(<?= $layout['id'] ?>, '<?= $layout['train_name'] ?>', '<?= $layout['coach_number'] ?>')">
                                                            <i class="fa fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link btn-warning btn-lg" data-bs-toggle="tooltip" title="Edit Layout">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-link btn-danger btn-lg" data-bs-toggle="tooltip" title="Delete Layout">
                                                            <i class="fa fa-times"></i>
                                                        </button>
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

        <!-- Seat Availability Checker -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-search"></i> Check Seat Availability
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select id="availability-train" class="form-select">
                                    <option value="">Select Train</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="availability-date" class="form-control" min="<?= date('Y-m-d') ?>">
                            </div>
                            <div class="col-md-3">
                                <select id="availability-coach" class="form-select">
                                    <option value="">Select Coach</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-info" onclick="checkAvailability()">
                                    <i class="fa fa-search"></i> Check Availability
                                </button>
                            </div>
                        </div>
                        <div id="availability-result" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Seat Map Modal -->
<div class="modal fade" id="seatMapModal" tabindex="-1" aria-labelledby="seatMapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="seatMapModalLabel">Seat Map</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="seat-map-content">
                    <!-- Seat map will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<style>
.seat {
    width: 40px;
    height: 40px;
    margin: 2px;
    border: 2px solid #ddd;
    border-radius: 5px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.seat.available {
    background-color: #28a745;
    color: white;
    border-color: #28a745;
}

.seat.booked {
    background-color: #dc3545;
    color: white;
    border-color: #dc3545;
    cursor: not-allowed;
}

.seat.selected {
    background-color: #007bff;
    color: white;
    border-color: #007bff;
}

.seat.waitlist {
    background-color: #ffc107;
    color: black;
    border-color: #ffc107;
}

.coach-layout {
    border: 2px solid #333;
    border-radius: 10px;
    padding: 20px;
    margin: 10px;
    background-color: #f8f9fa;
}

.aisle {
    width: 20px;
    display: inline-block;
}
</style>

<script>
$(document).ready(function() {
    $('#layouts-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
    });

    // Load coaches when train is selected
    $('#availability-train').change(function() {
        var trainId = $(this).val();
        if (trainId) {
            $.ajax({
                url: 'ajax/get_coaches.php',
                method: 'POST',
                data: { train_id: trainId },
                success: function(response) {
                    $('#availability-coach').html(response);
                }
            });
        } else {
            $('#availability-coach').html('<option value="">Select Coach</option>');
        }
    });
});

function showSeatMap(layoutId, trainName, coachNumber) {
    $('#seatMapModalLabel').text(`Seat Map - ${trainName} - Coach ${coachNumber}`);
    
    $.ajax({
        url: 'ajax/get_seat_map.php',
        method: 'POST',
        data: { layout_id: layoutId },
        success: function(response) {
            $('#seat-map-content').html(response);
            $('#seatMapModal').modal('show');
        },
        error: function() {
            alert('Error loading seat map');
        }
    });
}

function checkAvailability() {
    var trainId = $('#availability-train').val();
    var date = $('#availability-date').val();
    var coach = $('#availability-coach').val();
    
    if (!trainId || !date) {
        alert('Please select train and date');
        return;
    }
    
    $.ajax({
        url: 'ajax/check_availability.php',
        method: 'POST',
        data: { 
            train_id: trainId, 
            journey_date: date, 
            coach_number: coach 
        },
        success: function(response) {
            $('#availability-result').html(response);
        },
        error: function() {
            $('#availability-result').html('<div class="alert alert-danger">Error checking availability</div>');
        }
    });
}
</script>
