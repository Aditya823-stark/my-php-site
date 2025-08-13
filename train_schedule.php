<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create train_schedules table if not exists
$create_schedule_table = "CREATE TABLE IF NOT EXISTS train_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    station_id INT NOT NULL,
    arrival_time TIME,
    departure_time TIME NOT NULL,
    platform_number VARCHAR(10),
    stop_duration INT DEFAULT 5,
    distance_from_start DECIMAL(10,2) DEFAULT 0,
    day_of_week SET('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT 'Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id),
    FOREIGN KEY (station_id) REFERENCES stations(id)
)";
mysqli_query($db, $create_schedule_table);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_schedule'])) {
        $train_id = (int)$_POST['train_id'];
        $station_id = (int)$_POST['station_id'];
        $arrival_time = $_POST['arrival_time'] ?: null;
        $departure_time = $_POST['departure_time'];
        $platform = mysqli_real_escape_string($db, $_POST['platform_number']);
        $stop_duration = (int)$_POST['stop_duration'];
        $distance = (float)$_POST['distance_from_start'];
        $days = implode(',', $_POST['day_of_week'] ?? []);
        
        $sql = "INSERT INTO train_schedules (train_id, station_id, arrival_time, departure_time, platform_number, stop_duration, distance_from_start, day_of_week) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "iisssids", $train_id, $station_id, $arrival_time, $departure_time, $platform, $stop_duration, $distance, $days);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Train schedule added successfully!";
        } else {
            $error_msg = "Error adding schedule: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['delete_schedule'])) {
        $schedule_id = (int)$_POST['schedule_id'];
        mysqli_query($db, "DELETE FROM train_schedules WHERE id = $schedule_id");
        $success_msg = "Schedule deleted successfully!";
    }
}

$trains = $fun->get_all_trains();
$stations = $fun->get_all_stations();

// Get all schedules with train and station names
$schedules_query = "SELECT ts.*, t.name as train_name, s.name as station_name 
                   FROM train_schedules ts 
                   LEFT JOIN trains t ON ts.train_id = t.id 
                   LEFT JOIN stations s ON ts.station_id = s.id 
                   ORDER BY t.name, ts.departure_time";
$schedules = mysqli_query($db, $schedules_query);
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Train Schedule Management</h3>
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
                    <a href="#">Train Schedules</a>
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
            <!-- Add Schedule Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-clock"></i> Add Train Schedule
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
                                <label for="station_id">Station</label>
                                <select name="station_id" id="station_id" class="form-select" required>
                                    <option value="">Choose Station</option>
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="arrival_time">Arrival Time</label>
                                        <input type="time" name="arrival_time" id="arrival_time" class="form-control">
                                        <small class="text-muted">Leave empty for starting station</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="departure_time">Departure Time</label>
                                        <input type="time" name="departure_time" id="departure_time" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="platform_number">Platform</label>
                                        <input type="text" name="platform_number" id="platform_number" class="form-control" placeholder="e.g., 1A, 2B">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="stop_duration">Stop Duration (min)</label>
                                        <input type="number" name="stop_duration" id="stop_duration" class="form-control" value="5" min="1">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="distance_from_start">Distance from Start (KM)</label>
                                <input type="number" step="0.1" name="distance_from_start" id="distance_from_start" class="form-control" value="0">
                            </div>
                            
                            <div class="form-group">
                                <label>Operating Days</label>
                                <div class="form-check-group">
                                    <?php 
                                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach ($days as $day): 
                                    ?>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" name="day_of_week[]" value="<?= $day ?>" id="<?= strtolower($day) ?>" checked>
                                            <label class="form-check-label" for="<?= strtolower($day) ?>">
                                                <?= substr($day, 0, 3) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="card-action">
                                <button type="submit" name="add_schedule" class="btn btn-primary">
                                    <i class="fa fa-plus"></i> Add Schedule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Schedules Table -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Train Schedules
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($schedules) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No train schedules found. Add some schedules to get started.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table id="schedules-table" class="display table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Train</th>
                                            <th>Station</th>
                                            <th>Arrival</th>
                                            <th>Departure</th>
                                            <th>Platform</th>
                                            <th>Stop Duration</th>
                                            <th>Distance</th>
                                            <th>Days</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($schedule = mysqli_fetch_assoc($schedules)): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($schedule['train_name']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($schedule['station_name']) ?></td>
                                                <td>
                                                    <?= $schedule['arrival_time'] ? date('H:i', strtotime($schedule['arrival_time'])) : '<span class="text-muted">Start</span>' ?>
                                                </td>
                                                <td><?= date('H:i', strtotime($schedule['departure_time'])) ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?= htmlspecialchars($schedule['platform_number']) ?: 'TBA' ?>
                                                    </span>
                                                </td>
                                                <td><?= $schedule['stop_duration'] ?> min</td>
                                                <td><?= $schedule['distance_from_start'] ?> KM</td>
                                                <td>
                                                    <small>
                                                        <?php
                                                        $days = explode(',', $schedule['day_of_week']);
                                                        echo implode(', ', array_map(function($day) {
                                                            return substr(trim($day), 0, 3);
                                                        }, $days));
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?= $schedule['is_active'] ? 'success' : 'danger' ?>">
                                                        <?= $schedule['is_active'] ? 'Active' : 'Inactive' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="form-button-action">
                                                        <button type="button" class="btn btn-link btn-warning btn-lg" data-bs-toggle="tooltip" title="Edit Schedule">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <form method="post" style="display: inline;">
                                                            <input type="hidden" name="schedule_id" value="<?= $schedule['id'] ?>">
                                                            <button type="submit" name="delete_schedule" class="btn btn-link btn-danger btn-lg" 
                                                                    data-bs-toggle="tooltip" title="Delete Schedule"
                                                                    onclick="return confirm('Are you sure you want to delete this schedule?');">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        </form>
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

        <!-- Timetable View -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-calendar-alt"></i> Train Timetable View
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <select id="timetable-train" class="form-select">
                                    <option value="">Select Train for Timetable</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <button class="btn btn-info" onclick="generateTimetable()">
                                    <i class="fa fa-table"></i> Generate Timetable
                                </button>
                                <button class="btn btn-success" onclick="printTimetable()">
                                    <i class="fa fa-print"></i> Print Timetable
                                </button>
                            </div>
                        </div>
                        <div id="timetable-content" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#schedules-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 0, "asc" ], [ 2, "asc" ]]
    });
});

function generateTimetable() {
    var trainId = $('#timetable-train').val();
    if (!trainId) {
        alert('Please select a train first');
        return;
    }
    
    // AJAX call to get timetable data
    $.ajax({
        url: 'ajax/get_timetable.php',
        method: 'POST',
        data: { train_id: trainId },
        success: function(response) {
            $('#timetable-content').html(response);
        },
        error: function() {
            $('#timetable-content').html('<div class="alert alert-danger">Error loading timetable</div>');
        }
    });
}

function printTimetable() {
    var content = $('#timetable-content').html();
    if (!content) {
        alert('Please generate timetable first');
        return;
    }
    
    var printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
        <head>
            <title>Train Timetable</title>
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <h2>Train Timetable</h2>
            ${content}
        </body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}
</script>
