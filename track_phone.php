<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

include('connect/db.php');
$db = (new connect())->myconnect();

try {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        throw new Exception("No data received");
    }
    
    $device_id = mysqli_real_escape_string($db, $data['device_id']);
    $lat = (float)$data['latitude'];
    $lng = (float)$data['longitude'];
    $speed = (float)($data['speed'] ?? 0);
    $accuracy = (float)($data['accuracy'] ?? 0);
    
    // Validate coordinates
    if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        throw new Exception("Invalid coordinates");
    }
    
    // OPTIONAL: Assign a dummy train ID for mobile tracking
    $train_id = 9999; // Special ID for mobile devices
    
    // Create mobile train entry if it doesn't exist
    $check_train = mysqli_query($db, "SELECT id FROM trains WHERE id = $train_id");
    if (mysqli_num_rows($check_train) == 0) {
        mysqli_query($db, "INSERT INTO trains (id, name, from_station_id, to_station_id, departure_time, arrival_time) VALUES ($train_id, 'Mobile Tracker', 1, 1, '00:00:00', '23:59:59')");
    }
    
    // Insert or update device
    $device_query = "INSERT INTO gps_devices (device_id, train_id, device_model, battery_level, signal_strength, last_ping) 
        VALUES ('$device_id', $train_id, 'Mobile Tracker', 100, 95, NOW())
        ON DUPLICATE KEY UPDATE 
        last_ping = NOW(), 
        battery_level = 100, 
        signal_strength = 95,
        is_active = TRUE";
    
    if (!mysqli_query($db, $device_query)) {
        throw new Exception("Failed to update device: " . mysqli_error($db));
    }
    
    // Insert location
    $stmt = mysqli_prepare($db, "INSERT INTO train_locations (train_id, latitude, longitude, speed, accuracy, status, recorded_at) VALUES (?, ?, ?, ?, ?, 'Running', NOW())");
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . mysqli_error($db));
    }
    
    mysqli_stmt_bind_param($stmt, "idddd", $train_id, $lat, $lng, $speed, $accuracy);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Failed to insert location: " . mysqli_stmt_error($stmt));
    }
    
    // Get location count for this device
    $count_result = mysqli_query($db, "SELECT COUNT(*) as count FROM train_locations WHERE train_id = $train_id");
    $count = mysqli_fetch_assoc($count_result)['count'];
    
    $response = [
        'success' => true,
        'message' => "Location saved for device: $device_id",
        'device_id' => $device_id,
        'coordinates' => ['lat' => $lat, 'lng' => $lng],
        'speed' => $speed,
        'accuracy' => $accuracy,
        'total_locations' => $count,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
