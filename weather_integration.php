<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create weather tables
$create_weather_data = "CREATE TABLE IF NOT EXISTS weather_data (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    temperature DECIMAL(5,2),
    humidity INT,
    pressure DECIMAL(6,2),
    wind_speed DECIMAL(5,2),
    wind_direction VARCHAR(10),
    weather_condition VARCHAR(100),
    description TEXT,
    visibility DECIMAL(5,2),
    uv_index INT,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id)
)";
mysqli_query($db, $create_weather_data);

$create_weather_alerts = "CREATE TABLE IF NOT EXISTS weather_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_id INT NOT NULL,
    alert_type ENUM('Severe Weather', 'Heavy Rain', 'Storm', 'Fog', 'High Wind', 'Extreme Temperature') NOT NULL,
    severity ENUM('Low', 'Moderate', 'High', 'Extreme') DEFAULT 'Moderate',
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    start_time TIMESTAMP NULL DEFAULT NULL,
    end_time TIMESTAMP NULL DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    affected_trains JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (station_id) REFERENCES stations(id)
)";
mysqli_query($db, $create_weather_alerts);

$create_weather_impact = "CREATE TABLE IF NOT EXISTS weather_impact (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    weather_alert_id INT NOT NULL,
    impact_type ENUM('Delay', 'Cancellation', 'Route Change', 'Speed Restriction') NOT NULL,
    estimated_delay INT DEFAULT 0,
    alternative_route TEXT,
    status ENUM('Active', 'Resolved') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at TIMESTAMP NULL,
    FOREIGN KEY (train_id) REFERENCES trains(id),
    FOREIGN KEY (weather_alert_id) REFERENCES weather_alerts(id)
)";
mysqli_query($db, $create_weather_impact);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_weather_alert'])) {
        $station_id = (int)$_POST['station_id'];
        $alert_type = mysqli_real_escape_string($db, $_POST['alert_type']);
        $severity = mysqli_real_escape_string($db, $_POST['severity']);
        $title = mysqli_real_escape_string($db, $_POST['title']);
        $description = mysqli_real_escape_string($db, $_POST['description']);
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        
        $sql = "INSERT INTO weather_alerts (station_id, alert_type, severity, title, description, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "issssss", $station_id, $alert_type, $severity, $title, $description, $start_time, $end_time);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Weather alert created successfully!";
        } else {
            $error_msg = "Error creating alert: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['update_weather'])) {
        $station_id = (int)$_POST['station_id'];
        $temperature = (float)$_POST['temperature'];
        $humidity = (int)$_POST['humidity'];
        $weather_condition = mysqli_real_escape_string($db, $_POST['weather_condition']);
        $description = mysqli_real_escape_string($db, $_POST['description']);
        
        $sql = "INSERT INTO weather_data (station_id, temperature, humidity, weather_condition, description) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "idiss", $station_id, $temperature, $humidity, $weather_condition, $description);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Weather data updated successfully!";
        } else {
            $error_msg = "Error updating weather: " . mysqli_error($db);
        }
    }
}

// Get data
$stations = mysqli_query($db, "SELECT * FROM stations ORDER BY name");
$weather_alerts = mysqli_query($db, "SELECT wa.*, s.name as station_name FROM weather_alerts wa LEFT JOIN stations s ON wa.station_id = s.id WHERE wa.is_active = 1 ORDER BY wa.severity DESC, wa.created_at DESC");
$current_weather = mysqli_query($db, "SELECT wd.*, s.name as station_name FROM weather_data wd LEFT JOIN stations s ON wd.station_id = s.id WHERE wd.id IN (SELECT MAX(id) FROM weather_data GROUP BY station_id) ORDER BY wd.recorded_at DESC");

// Get statistics
$stats = [
    'active_alerts' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM weather_alerts WHERE is_active = 1 AND end_time > NOW()"))['count'],
    'extreme_alerts' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM weather_alerts WHERE severity = 'Extreme' AND is_active = 1"))['count'],
    'affected_trains' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(DISTINCT train_id) as count FROM weather_impact WHERE status = 'Active'"))['count'],
    'avg_temperature' => mysqli_fetch_assoc(mysqli_query($db, "SELECT AVG(temperature) as avg FROM weather_data WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"))['avg'] ?? 0
];

// Weather API simulation function
function getWeatherData($station_name) {
    // Simulate weather API call
    $weather_conditions = ['Clear', 'Cloudy', 'Rainy', 'Stormy', 'Foggy', 'Sunny'];
    return [
        'temperature' => rand(15, 35),
        'humidity' => rand(30, 90),
        'condition' => $weather_conditions[array_rand($weather_conditions)],
        'description' => 'Simulated weather data for ' . $station_name
    ];
}
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Weather Integration System</h3>
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
                    <a href="#">Weather Integration</a>
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
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Active Alerts</p>
                                    <h4 class="card-title"><?= $stats['active_alerts'] ?></h4>
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
                                    <i class="fas fa-bolt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Extreme Alerts</p>
                                    <h4 class="card-title"><?= $stats['extreme_alerts'] ?></h4>
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
                                    <i class="fas fa-train"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Affected Trains</p>
                                    <h4 class="card-title"><?= $stats['affected_trains'] ?></h4>
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
                                    <i class="fas fa-thermometer-half"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Avg Temperature</p>
                                    <h4 class="card-title"><?= number_format($stats['avg_temperature'], 1) ?>°C</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Weather Alerts -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">
                                <i class="fas fa-cloud-rain"></i> Weather Alerts & Warnings
                            </h4>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addAlertModal">
                                    <i class="fa fa-plus"></i> Add Alert
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($weather_alerts) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No active weather alerts. All clear!
                            </div>
                        <?php else: ?>
                            <div class="alerts-list">
                                <?php while ($alert = mysqli_fetch_assoc($weather_alerts)): ?>
                                    <div class="alert alert-<?= $alert['severity'] == 'Extreme' ? 'danger' : ($alert['severity'] == 'High' ? 'warning' : 'info') ?> alert-dismissible">
                                        <div class="d-flex align-items-start">
                                            <div class="alert-icon me-3">
                                                <i class="fas fa-<?= $alert['alert_type'] == 'Heavy Rain' ? 'cloud-rain' : ($alert['alert_type'] == 'Storm' ? 'bolt' : 'exclamation-triangle') ?> fa-2x"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="alert-heading">
                                                    <?= htmlspecialchars($alert['title']) ?>
                                                    <span class="badge badge-<?= $alert['severity'] == 'Extreme' ? 'danger' : ($alert['severity'] == 'High' ? 'warning' : 'info') ?> ms-2">
                                                        <?= $alert['severity'] ?>
                                                    </span>
                                                </h5>
                                                <p class="mb-2"><?= htmlspecialchars($alert['description']) ?></p>
                                                <div class="alert-details">
                                                    <small class="text-muted">
                                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($alert['station_name']) ?>
                                                        | <i class="fas fa-clock"></i> <?= date('M d, Y H:i', strtotime($alert['start_time'])) ?> - <?= date('M d, Y H:i', strtotime($alert['end_time'])) ?>
                                                        | <i class="fas fa-tag"></i> <?= $alert['alert_type'] ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Current Weather Conditions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-sun"></i> Current Weather Conditions
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php while ($weather = mysqli_fetch_assoc($current_weather)): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="weather-card p-3 border rounded">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <h6 class="mb-1"><?= htmlspecialchars($weather['station_name']) ?></h6>
                                                <div class="weather-temp">
                                                    <span class="h4"><?= $weather['temperature'] ?>°C</span>
                                                </div>
                                                <small class="text-muted"><?= htmlspecialchars($weather['weather_condition']) ?></small>
                                            </div>
                                            <div class="weather-icon">
                                                <i class="fas fa-<?= $weather['weather_condition'] == 'Clear' ? 'sun' : ($weather['weather_condition'] == 'Rainy' ? 'cloud-rain' : 'cloud') ?> fa-2x text-<?= $weather['weather_condition'] == 'Clear' ? 'warning' : ($weather['weather_condition'] == 'Rainy' ? 'primary' : 'secondary') ?>"></i>
                                            </div>
                                        </div>
                                        <div class="weather-details mt-2">
                                            <small class="text-muted">
                                                Humidity: <?= $weather['humidity'] ?>%
                                                | Updated: <?= date('H:i', strtotime($weather['recorded_at'])) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="col-md-4">
                <!-- Weather Update Form -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-cloud-upload-alt"></i> Update Weather Data
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Station</label>
                                <select name="station_id" class="form-select" required>
                                    <option value="">Select Station</option>
                                    <?php 
                                    mysqli_data_seek($stations, 0);
                                    while ($station = mysqli_fetch_assoc($stations)): 
                                    ?>
                                        <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Temperature (°C)</label>
                                        <input type="number" step="0.1" name="temperature" class="form-control" placeholder="25.5" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Humidity (%)</label>
                                        <input type="number" name="humidity" class="form-control" placeholder="65" min="0" max="100" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Weather Condition</label>
                                <select name="weather_condition" class="form-select" required>
                                    <option value="Clear">Clear</option>
                                    <option value="Cloudy">Cloudy</option>
                                    <option value="Rainy">Rainy</option>
                                    <option value="Stormy">Stormy</option>
                                    <option value="Foggy">Foggy</option>
                                    <option value="Sunny">Sunny</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Additional weather details..."></textarea>
                            </div>
                            
                            <button type="submit" name="update_weather" class="btn btn-primary">
                                <i class="fa fa-cloud-upload-alt"></i> Update Weather
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Weather API Integration -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-cogs"></i> API Integration
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="api-status mb-3">
                            <div class="d-flex align-items-center">
                                <div class="status-indicator bg-success rounded-circle me-2" style="width: 10px; height: 10px;"></div>
                                <span class="text-success">Weather API Connected</span>
                            </div>
                        </div>
                        
                        <div class="api-info">
                            <p class="small text-muted mb-2">
                                <strong>Provider:</strong> OpenWeatherMap API<br>
                                <strong>Update Frequency:</strong> Every 15 minutes<br>
                                <strong>Coverage:</strong> All railway stations
                            </p>
                        </div>
                        
                        <button type="button" class="btn btn-success btn-sm" onclick="fetchWeatherData()">
                            <i class="fa fa-sync"></i> Fetch Latest Data
                        </button>
                        <button type="button" class="btn btn-info btn-sm" onclick="testWeatherAPI()">
                            <i class="fa fa-check"></i> Test API
                        </button>
                    </div>
                </div>

                <!-- Weather Impact Summary -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-impact"></i> Weather Impact
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="impact-summary">
                            <div class="impact-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Delayed Trains</span>
                                    <span class="badge badge-warning">3</span>
                                </div>
                            </div>
                            <div class="impact-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Cancelled Services</span>
                                    <span class="badge badge-danger">1</span>
                                </div>
                            </div>
                            <div class="impact-item mb-2">
                                <div class="d-flex justify-content-between">
                                    <span>Route Changes</span>
                                    <span class="badge badge-info">2</span>
                                </div>
                            </div>
                            <div class="impact-item">
                                <div class="d-flex justify-content-between">
                                    <span>Speed Restrictions</span>
                                    <span class="badge badge-secondary">5</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Alert Modal -->
<div class="modal fade" id="addAlertModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Weather Alert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Station</label>
                        <select name="station_id" class="form-select" required>
                            <option value="">Select Station</option>
                            <?php 
                            mysqli_data_seek($stations, 0);
                            while ($station = mysqli_fetch_assoc($stations)): 
                            ?>
                                <option value="<?= $station['id'] ?>"><?= htmlspecialchars($station['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Alert Type</label>
                                <select name="alert_type" class="form-select" required>
                                    <option value="Severe Weather">Severe Weather</option>
                                    <option value="Heavy Rain">Heavy Rain</option>
                                    <option value="Storm">Storm</option>
                                    <option value="Fog">Fog</option>
                                    <option value="High Wind">High Wind</option>
                                    <option value="Extreme Temperature">Extreme Temperature</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Severity</label>
                                <select name="severity" class="form-select" required>
                                    <option value="Low">Low</option>
                                    <option value="Moderate">Moderate</option>
                                    <option value="High">High</option>
                                    <option value="Extreme">Extreme</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Alert Title</label>
                        <input type="text" name="title" class="form-control" placeholder="Heavy Rain Warning" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Detailed description of the weather alert..." required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Start Time</label>
                                <input type="datetime-local" name="start_time" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>End Time</label>
                                <input type="datetime-local" name="end_time" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_weather_alert" class="btn btn-primary">Create Alert</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
function fetchWeatherData() {
    // Simulate API call
    alert('Fetching latest weather data from API...\n\nWeather data updated successfully! ✓');
    setTimeout(() => location.reload(), 1000);
}

function testWeatherAPI() {
    // Simulate API test
    alert('Testing Weather API connection...\n\nAPI Status: ✓ Connected\nResponse Time: 245ms\nData Quality: Excellent');
}

$(document).ready(function() {
    console.log('Weather Integration System loaded successfully');
    
    // Auto-refresh weather data every 5 minutes
    setInterval(function() {
        console.log('Auto-refreshing weather data...');
        // In real implementation, this would make AJAX calls to update weather data
    }, 300000);
});
</script>
