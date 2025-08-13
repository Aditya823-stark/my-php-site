<?php
include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

echo "<h2>🧪 Test Multiple Passenger Insertion</h2>";

// Test data similar to what user mentioned
$test_passengers = [
    [
        'name' => 'rutuja',
        'age' => 25,
        'gender' => 'Female',
        'seat_no' => '1',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'password' => 'test123',
        'train_id' => 1,
        'from_station_id' => 1,
        'to_station_id' => 2,
        'class_type' => 'AC Tier 2',
        'journey_date' => '2025-01-20',
        'payment_mode' => 'Online',
        'fare' => 500,
        'distance' => 100
    ],
    [
        'name' => 'riddhi',
        'age' => 23,
        'gender' => 'Female',
        'seat_no' => '2',
        'email' => 'test@example.com',
        'phone' => '9876543210',
        'password' => 'test123',
        'train_id' => 1,
        'from_station_id' => 1,
        'to_station_id' => 2,
        'class_type' => 'AC Tier 2',
        'journey_date' => '2025-01-20',
        'payment_mode' => 'Online',
        'fare' => 500,
        'distance' => 100
    ]
];

echo "<h3>Inserting Test Passengers:</h3>";

$inserted_ids = [];
foreach ($test_passengers as $index => $passenger_data) {
    echo "<p>Inserting passenger " . ($index + 1) . ": " . $passenger_data['name'] . "...</p>";
    
    $passenger_id = $fun->add_passenger_with_seat($passenger_data);
    
    if ($passenger_id) {
        $inserted_ids[] = $passenger_id;
        echo "<p style='color: green;'>✅ Success! Passenger ID: $passenger_id</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to insert passenger: " . $passenger_data['name'] . "</p>";
    }
}

if (!empty($inserted_ids)) {
    echo "<h3>Verification - Checking Inserted Data:</h3>";
    
    $first_id = $inserted_ids[0];
    $check_query = "SELECT * FROM passengers WHERE id IN (" . implode(',', $inserted_ids) . ") ORDER BY id";
    $check_result = mysqli_query($db, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Seat</th><th>Email</th><th>Phone</th><th>Journey Date</th></tr>";
        while ($row = mysqli_fetch_assoc($check_result)) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['name']}</td>";
            echo "<td>{$row['seat_no']}</td>";
            echo "<td>{$row['email']}</td>";
            echo "<td>{$row['phone']}</td>";
            echo "<td>{$row['journey_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Test view_passenger.php:</h3>";
        echo "<p><a href='view_passenger.php?id=$first_id' target='_blank'>🎫 View Test Ticket (ID: $first_id)</a></p>";
        
        echo "<h3>Cleanup:</h3>";
        echo "<p><a href='?cleanup=1' style='color: red;'>🗑️ Delete Test Data</a></p>";
    }
}

// Cleanup functionality
if (isset($_GET['cleanup']) && $_GET['cleanup'] == '1') {
    echo "<h3>🗑️ Cleaning up test data...</h3>";
    $cleanup_query = "DELETE FROM passengers WHERE email = 'test@example.com' AND name IN ('rutuja', 'riddhi')";
    if (mysqli_query($db, $cleanup_query)) {
        echo "<p style='color: green;'>✅ Test data cleaned up successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to cleanup test data: " . mysqli_error($db) . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>This test helps verify:</strong></p>";
echo "<ul>";
echo "<li>Whether multiple passengers can be inserted with the same booking details</li>";
echo "<li>Whether view_passenger.php can display multiple passengers correctly</li>";
echo "<li>Whether the database structure supports multiple passengers per booking</li>";
echo "</ul>";
?>