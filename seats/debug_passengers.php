<?php
include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

echo "<h2>🔍 Passenger Data Debug Tool</h2>";

// Check recent passengers
echo "<h3>Recent Passengers (Last 10):</h3>";
$recent_query = "SELECT id, name, email, phone, seat_no, journey_date, created_at FROM passengers ORDER BY id DESC LIMIT 10";
$recent_result = mysqli_query($db, $recent_query);

if ($recent_result && mysqli_num_rows($recent_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Seat</th><th>Journey Date</th><th>Created</th></tr>";
    while ($row = mysqli_fetch_assoc($recent_result)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "<td>{$row['seat_no']}</td>";
        echo "<td>{$row['journey_date']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No passengers found in database!</p>";
}

// Check for duplicate bookings
echo "<h3>Checking for Multiple Passengers with Same Booking Details:</h3>";
$duplicate_query = "SELECT email, phone, journey_date, COUNT(*) as passenger_count, GROUP_CONCAT(name) as names, GROUP_CONCAT(seat_no) as seats
                   FROM passengers 
                   WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
                   GROUP BY email, phone, journey_date 
                   HAVING passenger_count > 1
                   ORDER BY passenger_count DESC";

$duplicate_result = mysqli_query($db, $duplicate_query);

if ($duplicate_result && mysqli_num_rows($duplicate_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Email</th><th>Phone</th><th>Journey Date</th><th>Passenger Count</th><th>Names</th><th>Seats</th></tr>";
    while ($row = mysqli_fetch_assoc($duplicate_result)) {
        echo "<tr>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "<td>{$row['journey_date']}</td>";
        echo "<td style='font-weight: bold; color: green;'>{$row['passenger_count']}</td>";
        echo "<td>{$row['names']}</td>";
        echo "<td>{$row['seats']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>No multiple passenger bookings found in the last 24 hours.</p>";
}

// Test specific case mentioned by user
echo "<h3>Searching for 'rutuja' and 'riddhi':</h3>";
$test_query = "SELECT id, name, email, phone, seat_no, journey_date, created_at 
               FROM passengers 
               WHERE name LIKE '%rutuja%' OR name LIKE '%riddhi%' 
               ORDER BY created_at DESC";
$test_result = mysqli_query($db, $test_query);

if ($test_result && mysqli_num_rows($test_result) > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Seat</th><th>Journey Date</th><th>Created</th></tr>";
    while ($row = mysqli_fetch_assoc($test_result)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td style='font-weight: bold;'>{$row['name']}</td>";
        echo "<td>{$row['email']}</td>";
        echo "<td>{$row['phone']}</td>";
        echo "<td>{$row['seat_no']}</td>";
        echo "<td>{$row['journey_date']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No passengers found with names 'rutuja' or 'riddhi'.</p>";
}

echo "<hr>";
echo "<p><strong>Instructions:</strong></p>";
echo "<ol>";
echo "<li>If you see multiple passengers with the same email/phone/journey_date, the system is working correctly</li>";
echo "<li>If you only see single passengers, there might be an issue with the passenger_details.php form submission</li>";
echo "<li>Check if the passenger data is being properly passed from passenger_details.php to add_multiple_passengers.php</li>";
echo "</ol>";

echo "<p><a href='view_passenger.php?id=" . (mysqli_fetch_assoc(mysqli_query($db, "SELECT MAX(id) as max_id FROM passengers"))['max_id'] ?? 1) . "'>View Latest Passenger Ticket</a></p>";
?>