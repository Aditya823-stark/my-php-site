<?php
include('../connect/db.php');

$db = (new connect())->myconnect();

if (isset($_POST['train_id'])) {
    $train_id = (int)$_POST['train_id'];
    
    // Get train name
    $train_query = "SELECT name FROM trains WHERE id = $train_id";
    $train_result = mysqli_query($db, $train_query);
    $train = mysqli_fetch_assoc($train_result);
    
    // Get schedule for this train
    $schedule_query = "SELECT ts.*, s.name as station_name 
                      FROM train_schedules ts 
                      LEFT JOIN stations s ON ts.station_id = s.id 
                      WHERE ts.train_id = $train_id AND ts.is_active = 1
                      ORDER BY ts.distance_from_start ASC, ts.departure_time ASC";
    
    $schedules = mysqli_query($db, $schedule_query);
    
    if (mysqli_num_rows($schedules) > 0) {
        echo '<div class="timetable-header text-center mb-3">';
        echo '<h4>Timetable for Train: ' . htmlspecialchars($train['name']) . '</h4>';
        echo '<p class="text-muted">Generated on ' . date('F d, Y \a\t H:i') . '</p>';
        echo '</div>';
        
        echo '<table class="table table-bordered table-striped">';
        echo '<thead class="table-dark">';
        echo '<tr>';
        echo '<th>Station</th>';
        echo '<th>Arrival Time</th>';
        echo '<th>Departure Time</th>';
        echo '<th>Platform</th>';
        echo '<th>Stop Duration</th>';
        echo '<th>Distance (KM)</th>';
        echo '<th>Operating Days</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while ($schedule = mysqli_fetch_assoc($schedules)) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($schedule['station_name']) . '</strong></td>';
            echo '<td>' . ($schedule['arrival_time'] ? date('H:i', strtotime($schedule['arrival_time'])) : '<span class="badge badge-info">Origin</span>') . '</td>';
            echo '<td>' . date('H:i', strtotime($schedule['departure_time'])) . '</td>';
            echo '<td><span class="badge badge-secondary">' . htmlspecialchars($schedule['platform_number'] ?: 'TBA') . '</span></td>';
            echo '<td>' . $schedule['stop_duration'] . ' min</td>';
            echo '<td>' . $schedule['distance_from_start'] . ' KM</td>';
            
            $days = explode(',', $schedule['day_of_week']);
            $day_badges = '';
            foreach ($days as $day) {
                $day_badges .= '<span class="badge badge-outline-primary me-1">' . substr(trim($day), 0, 3) . '</span>';
            }
            echo '<td>' . $day_badges . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        
        // Add journey summary
        $total_distance = mysqli_fetch_assoc(mysqli_query($db, "SELECT MAX(distance_from_start) as max_distance FROM train_schedules WHERE train_id = $train_id"))['max_distance'];
        $first_departure = mysqli_fetch_assoc(mysqli_query($db, "SELECT MIN(departure_time) as first_time FROM train_schedules WHERE train_id = $train_id"))['first_time'];
        $last_arrival = mysqli_fetch_assoc(mysqli_query($db, "SELECT MAX(COALESCE(arrival_time, departure_time)) as last_time FROM train_schedules WHERE train_id = $train_id"))['last_time'];
        
        echo '<div class="mt-3 p-3 bg-light rounded">';
        echo '<h6>Journey Summary</h6>';
        echo '<div class="row">';
        echo '<div class="col-md-3"><strong>Total Distance:</strong> ' . $total_distance . ' KM</div>';
        echo '<div class="col-md-3"><strong>First Departure:</strong> ' . date('H:i', strtotime($first_departure)) . '</div>';
        echo '<div class="col-md-3"><strong>Last Arrival:</strong> ' . date('H:i', strtotime($last_arrival)) . '</div>';
        
        // Calculate journey time
        $journey_time = strtotime($last_arrival) - strtotime($first_departure);
        $hours = floor($journey_time / 3600);
        $minutes = floor(($journey_time % 3600) / 60);
        echo '<div class="col-md-3"><strong>Journey Time:</strong> ' . $hours . 'h ' . $minutes . 'm</div>';
        echo '</div>';
        echo '</div>';
        
    } else {
        echo '<div class="alert alert-warning text-center">';
        echo '<i class="fas fa-exclamation-triangle"></i> No schedule found for this train.';
        echo '</div>';
    }
} else {
    echo '<div class="alert alert-danger">Invalid request</div>';
}
?>
