<?php
// Debug script to test multiple passenger booking
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

echo "<h2>🔍 Debug Multiple Passenger Booking</h2>";

// Test data for 5 passengers with seats 1, 2, 3, 4, 5
$test_passengers = [
    [
        'name' => 'Passenger 1',
        'age' => 30,
        'gender' => 'Male',
        'seat_no' => '1',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'test123',
        'train_id' => 1,
        'from_station_id' => 1,
        'to_station_id' => 2,
        'class_type' => 'General',
        'journey_date' => '2024-01-15',
        'payment_mode' => 'Offline',
        'fare' => 100,
        'distance' => 50
    ],
    [
        'name' => 'Passenger 2',
        'age' => 25,
        'gender' => 'Female',
        'seat_no' => '2',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'test123',
        'train_id' => 1,
        'from_station_id' => 1,
        'to_station_id' => 2,
        'class_type' => 'General',
        'journey_date' => '2024-01-15',
        'payment_mode' => 'Offline',
        'fare' => 100,
        'distance' => 50
    ],
    [
        'name' => 'Passenger 3',
        'age' => 35,
        'gender' => 'Male',
        'seat_no' => '3',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'test123',
        'train_id' => 1,
        'from_station_id' => 1,
        'to_station_id' => 2,
        'class_type' => 'General',
        'journey_date' => '2024-01-15',
        'payment_mode' => 'Offline',
        'fare' => 100,
        'distance' => 50
    ],
    [
        'name' => 'Passenger 4',
        'age' => 28,
        'gender' => 'Female',
        'seat_no' => '4',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'test123',
        'train_id' => 1,
        'from_station_id' => 1,
        'to_station_id' => 2,
        'class_type' => 'General',
        'journey_date' => '2024-01-15',
        'payment_mode' => 'Offline',
        'fare' => 100,
        'distance' => 50
    ],
    [
        'name' => 'Passenger 5',
        'age' => 32,
        'gender' => 'Male',
        'seat_no' => '5',
        'email' => 'test@example.com',
        'phone' => '1234567890',
        'password' => 'test123',
        'train_id' => 1,
        'from_station_id' => 1,
        'to_station_id' => 2,
        'class_type' => 'General',
        'journey_date' => '2024-01-15',
        'payment_mode' => 'Offline',
        'fare' => 100,
        'distance' => 50
    ]
];

echo "<h3>📋 Test Data:</h3>";
echo "<p>Attempting to book " . count($test_passengers) . " passengers with seats 1, 2, 3, 4, 5</p>";

// Check if seats are already booked
echo "<h3>🔍 Checking Seat Availability:</h3>";
foreach ($test_passengers as $index => $passenger) {
    $seat_check = "SELECT COUNT(*) as count FROM passengers 
                   WHERE train_id = {$passenger['train_id']} 
                   AND journey_date = '{$passenger['journey_date']}' 
                   AND class_type = '{$passenger['class_type']}' 
                   AND seat_no = '{$passenger['seat_no']}'
                   AND (status IS NULL OR status != 'cancelled')";
    
    $result = mysqli_query($db, $seat_check);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] > 0) {
        echo "<p style='color: orange;'>⚠️ Seat {$passenger['seat_no']} is already booked ({$row['count']} times)</p>";
    } else {
        echo "<p style='color: green;'>✅ Seat {$passenger['seat_no']} is available</p>";
    }
}

echo "<hr>";

// Attempt to book all passengers
echo "<h3>💾 Booking Passengers:</h3>";
$passenger_ids = [];
$booking_results = [];

foreach ($test_passengers as $index => $passenger_data) {
    echo "<h4>Booking Passenger " . ($index + 1) . ": {$passenger_data['name']} (Seat {$passenger_data['seat_no']})</h4>";
    
    // Debug: Show the data being passed
    echo "<details><summary>Click to view passenger data</summary>";
    echo "<pre>" . print_r($passenger_data, true) . "</pre>";
    echo "</details>";
    
    $passenger_id = $fun->add_passenger_with_seat($passenger_data);
    
    if ($passenger_id) {
        $passenger_ids[] = $passenger_id;
        $booking_results[] = [
            'success' => true,
            'id' => $passenger_id,
            'name' => $passenger_data['name'],
            'seat' => $passenger_data['seat_no']
        ];
        echo "<p style='color: green;'>✅ Successfully booked! Passenger ID: $passenger_id</p>";
    } else {
        $booking_results[] = [
            'success' => false,
            'name' => $passenger_data['name'],
            'seat' => $passenger_data['seat_no'],
            'error' => mysqli_error($db)
        ];
        echo "<p style='color: red;'>❌ Failed to book passenger</p>";
        if (mysqli_error($db)) {
            echo "<p style='color: red;'>Database Error: " . mysqli_error($db) . "</p>";
        }
    }
    echo "<hr>";
}

// Summary
echo "<h3>📊 Booking Summary:</h3>";
echo "<p><strong>Total Passengers Attempted:</strong> " . count($test_passengers) . "</p>";
echo "<p><strong>Successfully Booked:</strong> " . count($passenger_ids) . "</p>";
echo "<p><strong>Failed Bookings:</strong> " . (count($test_passengers) - count($passenger_ids)) . "</p>";

if (!empty($passenger_ids)) {
    echo "<p><strong>Passenger IDs:</strong> " . implode(', ', $passenger_ids) . "</p>";
    $main_id = $passenger_ids[0];
    echo "<p><a href='view_passenger.php?id=$main_id' class='btn btn-primary'>👉 View Booked Tickets</a></p>";
}

// Show detailed results
echo "<h3>📋 Detailed Results:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Passenger</th><th>Seat</th><th>Status</th><th>ID/Error</th></tr>";

foreach ($booking_results as $result) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($result['name']) . "</td>";
    echo "<td>" . $result['seat'] . "</td>";
    
    if ($result['success']) {
        echo "<td style='color: green;'>✅ Success</td>";
        echo "<td>" . $result['id'] . "</td>";
    } else {
        echo "<td style='color: red;'>❌ Failed</td>";
        echo "<td>" . ($result['error'] ?? 'Unknown error') . "</td>";
    }
    echo "</tr>";
}
echo "</table>";

// Check what's actually in the database
echo "<h3>🗄️ Database Verification:</h3>";
$verify_query = "SELECT id, name, seat_no, email, journey_date, created_at 
                 FROM passengers 
                 WHERE email = 'test@example.com' 
                 AND journey_date = '2024-01-15'
                 ORDER BY created_at DESC 
                 LIMIT 10";

$verify_result = mysqli_query($db, $verify_query);

if ($verify_result && mysqli_num_rows($verify_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Seat</th><th>Email</th><th>Journey Date</th><th>Created At</th></tr>";
    
    while ($row = mysqli_fetch_assoc($verify_result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . $row['seat_no'] . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . $row['journey_date'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No passengers found in database with test email</p>";
}

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .btn { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
    table { margin: 10px 0; }
    th, td { padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    details { margin: 10px 0; }
    hr { margin: 20px 0; }
</style>
