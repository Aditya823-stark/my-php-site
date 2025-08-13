<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create GPS tracking tables
$create_train_locations = "CREATE TABLE IF NOT EXISTS train_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    speed DECIMAL(5,2) DEFAULT 0.00,
    direction DECIMAL(5,2) DEFAULT 0.00,
    altitude DECIMAL(8,2) DEFAULT 0.00,
    accuracy DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('Running', 'Stopped', 'Delayed', 'Maintenance') DEFAULT 'Running',
    next_station_id INT NULL,
    distance_to_next DECIMAL(8,2) DEFAULT 0.00,
    estimated_arrival TIMESTAMP NULL,
    recorded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id),
    FOREIGN KEY (next_station_id) REFERENCES stations(id)
)";
mysqli_query($db, $create_train_locations);

$create_gps_devices = "CREATE TABLE IF NOT EXISTS gps_devices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    device_id VARCHAR(100) UNIQUE NOT NULL,
    train_id INT NOT NULL,
    device_model VARCHAR(100),
    firmware_version VARCHAR(50),
    battery_level INT DEFAULT 100,
    signal_strength INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    last_ping TIMESTAMP NULL,
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id)
)";
mysqli_query($db, $create_gps_devices);

$create_route_checkpoints = "CREATE TABLE IF NOT EXISTS route_checkpoints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    train_id INT NOT NULL,
    station_id INT NOT NULL,
    checkpoint_order INT NOT NULL,
    scheduled_arrival TIME NOT NULL,
    scheduled_departure TIME NOT NULL,
    actual_arrival TIMESTAMP NULL,
    actual_departure TIMESTAMP NULL,
    delay_minutes INT DEFAULT 0,
    status ENUM('Pending', 'Arrived', 'Departed', 'Skipped') DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (train_id) REFERENCES trains(id),
    FOREIGN KEY (station_id) REFERENCES stations(id)
)";
mysqli_query($db, $create_route_checkpoints);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_location'])) {
        $train_id = (int)$_POST['train_id'];
        $latitude = (float)$_POST['latitude'];
        $longitude = (float)$_POST['longitude'];
        $speed = (float)$_POST['speed'];
        $status = mysqli_real_escape_string($db, $_POST['status']);
        
        $sql = "INSERT INTO train_locations (train_id, latitude, longitude, speed, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "iddds", $train_id, $latitude, $longitude, $speed, $status);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Train location updated successfully!";
        } else {
            $error_msg = "Error updating location: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['add_gps_device'])) {
        $device_id = mysqli_real_escape_string($db, $_POST['device_id']);
        $train_id = (int)$_POST['train_id'];
        $device_model = mysqli_real_escape_string($db, $_POST['device_model']);
        
        $sql = "INSERT INTO gps_devices (device_id, train_id, device_model) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "sis", $device_id, $train_id, $device_model);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "GPS device added successfully!";
        } else {
            $error_msg = "Error adding GPS device: " . mysqli_error($db);
        }
    }
}

// Get data
$trains = $fun->get_all_trains();
$current_locations = mysqli_query($db, "SELECT tl.*, t.name as train_name, s.name as next_station FROM train_locations tl LEFT JOIN trains t ON tl.train_id = t.id LEFT JOIN stations s ON tl.next_station_id = s.id WHERE tl.id IN (SELECT MAX(id) FROM train_locations GROUP BY train_id) ORDER BY tl.recorded_at DESC");
$gps_devices = mysqli_query($db, "SELECT gd.*, t.name as train_name FROM gps_devices gd LEFT JOIN trains t ON gd.train_id = t.id ORDER BY gd.installed_at DESC");

// Get statistics
$stats = [
    'active_trains' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(DISTINCT train_id) as count FROM train_locations WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"))['count'],
    'total_devices' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM gps_devices WHERE is_active = 1"))['count'],
    'delayed_trains' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(DISTINCT train_id) as count FROM train_locations WHERE status = 'Delayed'"))['count'],
    'avg_speed' => mysqli_fetch_assoc(mysqli_query($db, "SELECT AVG(speed) as avg FROM train_locations WHERE recorded_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"))['avg'] ?? 0
];
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">GPS Train Tracking System</h3>
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
                    <a href="#">GPS Tracking</a>
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
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-train"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Active Trains</p>
                                    <h4 class="card-title"><?= $stats['active_trains'] ?></h4>
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
                                <div class="icon-big text-center icon-primary bubble-shadow-small">
                                    <i class="fas fa-satellite"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">GPS Devices</p>
                                    <h4 class="card-title"><?= $stats['total_devices'] ?></h4>
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
                                    <p class="card-category">Delayed Trains</p>
                                    <h4 class="card-title"><?= $stats['delayed_trains'] ?></h4>
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
                                    <i class="fas fa-tachometer-alt"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Avg Speed</p>
                                    <h4 class="card-title"><?= number_format($stats['avg_speed'], 1) ?> km/h</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Live Map -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex align-items-center">
                            <h4 class="card-title">
                                <i class="fas fa-map"></i> Live Train Tracking Map
                            </h4>
                            <div class="ms-auto">
                                <button type="button" class="btn btn-primary btn-sm" onclick="refreshMap()">
                                    <i class="fa fa-refresh"></i> Refresh
                                </button>
                                <button type="button" class="btn btn-success btn-sm" onclick="toggleAutoRefresh()">
                                    <i class="fa fa-play"></i> Auto Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="tracking-map" style="height: 500px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; position: relative;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center">
                                    <i class="fas fa-map-marked-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Interactive Train Tracking Map</h5>
                                    <p class="text-muted">Real-time GPS locations of all trains</p>
                                    <div class="mt-4">
                                        <?php while ($location = mysqli_fetch_assoc($current_locations)): ?>
                                            <div class="train-marker mb-2 p-2 bg-white border rounded shadow-sm">
                                                <div class="d-flex align-items-center">
                                                    <div class="train-icon me-3">
                                                        <i class="fas fa-train text-<?= $location['status'] == 'Running' ? 'success' : ($location['status'] == 'Delayed' ? 'warning' : 'danger') ?>"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <strong><?= htmlspecialchars($location['train_name']) ?></strong>
                                                        <br>
                                                        <small class="text-muted">
                                                            Lat: <?= $location['latitude'] ?>, Lng: <?= $location['longitude'] ?>
                                                            | Speed: <?= $location['speed'] ?> km/h
                                                            | Status: <?= $location['status'] ?>
                                                        </small>
                                                    </div>
                                                    <div class="train-status">
                                                        <span class="badge badge-<?= $location['status'] == 'Running' ? 'success' : ($location['status'] == 'Delayed' ? 'warning' : 'danger') ?>">
                                                            <?= $location['status'] ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Control Panel -->
            <div class="col-md-4">
                <!-- Update Location -->
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-map-pin"></i> Update Train Location
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Train</label>
                                <select name="train_id" class="form-select" required>
                                    <option value="">Select Train</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- GPS Location Button -->
                            <div class="mb-3">
                                <button type="button" class="btn btn-secondary" onclick="useMyLocation()">
                                    <i class="fas fa-location-arrow"></i> Use My Location
                                </button>
                                <button type="button" class="btn btn-info" onclick="startRealTimeTracking()">
                                    <i class="fas fa-satellite"></i> Start Real-Time Tracking
                                </button>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Latitude</label>
                                        <input type="number" step="0.000001" name="latitude" id="latitude" class="form-control" placeholder="28.6139" required>
                                        <small class="text-muted">Click 'Use My Location' to auto-fill</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Longitude</label>
                                        <input type="number" step="0.000001" name="longitude" id="longitude" class="form-control" placeholder="77.2090" required>
                                        <small class="text-muted">GPS coordinates will appear here</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Speed (km/h)</label>
                                        <input type="number" step="0.01" name="speed" class="form-control" placeholder="80.5" value="0">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="Running">Running</option>
                                            <option value="Stopped">Stopped</option>
                                            <option value="Delayed">Delayed</option>
                                            <option value="Maintenance">Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_location" class="btn btn-primary">
                                <i class="fa fa-map-pin"></i> Update Location
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Add GPS Device -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-plus"></i> Add GPS Device
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Device ID</label>
                                <input type="text" name="device_id" class="form-control" placeholder="GPS001" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Train</label>
                                <select name="train_id" class="form-select" required>
                                    <option value="">Select Train</option>
                                    <?php foreach ($trains as $train): ?>
                                        <option value="<?= $train['id'] ?>"><?= htmlspecialchars($train['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Device Model</label>
                                <input type="text" name="device_model" class="form-control" placeholder="Garmin GTU 10">
                            </div>
                            
                            <button type="submit" name="add_gps_device" class="btn btn-success">
                                <i class="fa fa-plus"></i> Add Device
                            </button>
                        </form>
                    </div>
                </div>

                <!-- GPS Devices List -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-satellite"></i> GPS Devices
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="device-list">
                            <?php while ($device = mysqli_fetch_assoc($gps_devices)): ?>
                                <div class="device-item mb-3 p-2 border rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($device['device_id']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($device['train_name']) ?></small>
                                        </div>
                                        <div>
                                            <span class="badge badge-<?= $device['is_active'] ? 'success' : 'danger' ?>">
                                                <?= $device['is_active'] ? 'Active' : 'Inactive' ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            Model: <?= htmlspecialchars($device['device_model']) ?><br>
                                            Battery: <?= $device['battery_level'] ?>% | Signal: <?= $device['signal_strength'] ?>%
                                        </small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
let autoRefreshInterval;
let isAutoRefreshActive = false;
let realTimeTrackingInterval;
let isRealTimeActive = false;

// Generate or get device ID from localStorage
function getDeviceId() {
    let id = localStorage.getItem("device_id");
    if (!id) {
        id = 'device-' + Math.random().toString(36).substr(2, 9);
        localStorage.setItem("device_id", id);
    }
    return id;
}

// Use current location to fill GPS coordinates
function useMyLocation() {
    if (navigator.geolocation) {
        // Show loading state
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Getting Location...';
        btn.disabled = true;
        
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
            document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
            
            // Show success message
            btn.innerHTML = '<i class="fas fa-check"></i> Location Found!';
            btn.className = 'btn btn-success';
            
            // Reset button after 2 seconds
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.className = 'btn btn-secondary';
                btn.disabled = false;
            }, 2000);
            
            // Also update speed if available
            const speedInput = document.querySelector('input[name="speed"]');
            if (speedInput && position.coords.speed) {
                speedInput.value = (position.coords.speed * 3.6).toFixed(2); // Convert m/s to km/h
            }
            
            console.log('Location obtained:', {
                lat: position.coords.latitude,
                lng: position.coords.longitude,
                accuracy: position.coords.accuracy,
                speed: position.coords.speed
            });
            
        }, function(error) {
            btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
            btn.className = 'btn btn-danger';
            btn.disabled = false;
            
            let errorMsg = 'Error getting location: ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    errorMsg += 'Location access denied by user.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    errorMsg += 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    errorMsg += 'Location request timed out.';
                    break;
                default:
                    errorMsg += 'Unknown error occurred.';
                    break;
            }
            
            alert(errorMsg);
            
            // Reset button after 3 seconds
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.className = 'btn btn-secondary';
            }, 3000);
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 60000
        });
    } else {
        alert('Geolocation not supported on this browser.');
    }
}

// Send location data to server
function sendLocationToServer() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            const data = {
                device_id: getDeviceId(),
                latitude: position.coords.latitude,
                longitude: position.coords.longitude,
                speed: position.coords.speed || 0,
                accuracy: position.coords.accuracy || 0
            };

            fetch("track_phone.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    console.log("Server Response:", result.message);
                    // Update UI with tracking info
                    updateTrackingStatus(result);
                } else {
                    console.error("Server Error:", result.error);
                }
            })
            .catch(error => {
                console.error("Network Error:", error);
            });
        }, function(error) {
            console.error("Geolocation Error:", error);
        });
    } else {
        alert("Geolocation not supported!");
    }
}

// Start real-time tracking
function startRealTimeTracking() {
    const btn = event.target;
    
    if (!isRealTimeActive) {
        // Start tracking
        isRealTimeActive = true;
        btn.innerHTML = '<i class="fas fa-stop"></i> Stop Real-Time Tracking';
        btn.className = 'btn btn-danger';
        
        // Send location immediately
        sendLocationToServer();
        
        // Set up interval for continuous tracking
        realTimeTrackingInterval = setInterval(() => {
            sendLocationToServer();
        }, 10000); // Send location every 10 seconds
        
        // Show tracking status
        showTrackingStatus('Real-time tracking started. Device ID: ' + getDeviceId());
        
    } else {
        // Stop tracking
        isRealTimeActive = false;
        btn.innerHTML = '<i class="fas fa-satellite"></i> Start Real-Time Tracking';
        btn.className = 'btn btn-info';
        
        if (realTimeTrackingInterval) {
            clearInterval(realTimeTrackingInterval);
        }
        
        showTrackingStatus('Real-time tracking stopped.');
    }
}

// Update tracking status in UI
function updateTrackingStatus(result) {
    const statusDiv = document.getElementById('tracking-status');
    if (statusDiv) {
        statusDiv.innerHTML = `
            <div class="alert alert-success alert-sm">
                <strong>Location Tracked:</strong> ${result.coordinates.lat.toFixed(6)}, ${result.coordinates.lng.toFixed(6)}<br>
                <small>Speed: ${result.speed.toFixed(2)} m/s | Accuracy: ${result.accuracy.toFixed(0)}m | Total Points: ${result.total_locations}</small>
            </div>
        `;
    }
}

// Show tracking status message
function showTrackingStatus(message) {
    const statusDiv = document.getElementById('tracking-status');
    if (!statusDiv) {
        // Create status div if it doesn't exist
        const newDiv = document.createElement('div');
        newDiv.id = 'tracking-status';
        newDiv.className = 'mt-3';
        document.querySelector('.card-body form').appendChild(newDiv);
    }
    
    document.getElementById('tracking-status').innerHTML = `
        <div class="alert alert-info alert-sm">
            <i class="fas fa-satellite"></i> ${message}
        </div>
    `;
}

function refreshMap() {
    // Simulate map refresh
    console.log('Refreshing train locations...');
    location.reload();
}

function toggleAutoRefresh() {
    if (isAutoRefreshActive) {
        clearInterval(autoRefreshInterval);
        isAutoRefreshActive = false;
        document.querySelector('button[onclick="toggleAutoRefresh()"]').innerHTML = '<i class="fa fa-play"></i> Auto Refresh';
    } else {
        autoRefreshInterval = setInterval(refreshMap, 30000); // Refresh every 30 seconds
        isAutoRefreshActive = true;
        document.querySelector('button[onclick="toggleAutoRefresh()"]').innerHTML = '<i class="fa fa-pause"></i> Stop Auto Refresh';
    }
}

$(document).ready(function() {
    console.log('GPS Tracking System loaded successfully');
    
    // Simulate real-time updates
    setInterval(function() {
        // Update train markers with new positions
        $('.train-marker').each(function() {
            // Add subtle animation to show live tracking
            $(this).addClass('pulse');
            setTimeout(() => $(this).removeClass('pulse'), 1000);
        });
    }, 5000);
});

// Add CSS for pulse animation
const style = document.createElement('style');
style.textContent = `
    .pulse {
        animation: pulse 1s ease-in-out;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
`;
document.head.appendChild(style);
</script>
