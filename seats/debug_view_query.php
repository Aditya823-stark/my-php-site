<?php
// Debug the view_passenger.php query logic
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("./../connect/db.php");
include("./../connect/fun.php");

$db = (new connect())->myconnect();
$fun = new fun($db);

echo "<h2>🔍 Debug View Passenger Query Logic</h2>";

// Test with passenger ID 103 (from our previous test)
$id = 103;
$q = mysqli_query($db, "SELECT * FROM passengers WHERE id = $id");
$data = mysqli_fetch_assoc($q);

if (!$data) {
    echo "<p style='color: red;'>❌ No passenger found with ID $id</p>";
    exit;
}

echo "<h3>📋 Main Passenger Data (ID: $id):</h3>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
foreach ($data as $key => $value) {
    echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
}
echo "</table>";

echo "<h3>🔍 Related Passengers Query:</h3>";

// This is the exact query from view_passenger.php
$related_passengers_query = "SELECT * FROM passengers
                            WHERE email = '{$data['email']}'
                            AND phone = '{$data['phone']}'
                            AND train_id = {$data['train_id']}
                            AND journey_date = '{$data['journey_date']}'
                            AND from_station_id = {$data['from_station_id']}
                            AND to_station_id = {$data['to_station_id']}
                            AND class_type = '{$data['class_type']}'
                            AND (status IS NULL OR status != 'cancelled')
                            ORDER BY CAST(seat_no AS UNSIGNED) ASC";

echo "<details><summary>Click to view SQL query</summary>";
echo "<pre>$related_passengers_query</pre>";
echo "</details>";

$related_passengers = mysqli_query($db, $related_passengers_query);

if (!$related_passengers) {
    echo "<p style='color: red;'>❌ Query failed: " . mysqli_error($db) . "</p>";
    exit;
}

echo "<h3>📊 Raw Query Results:</h3>";
$raw_results = [];
while ($row = mysqli_fetch_assoc($related_passengers)) {
    $raw_results[] = $row;
}

echo "<p><strong>Total rows returned:</strong> " . count($raw_results) . "</p>";

if (!empty($raw_results)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Seat</th><th>Email</th><th>Phone</th><th>Status</th></tr>";
    
    foreach ($raw_results as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . ($row['seat_no'] ?: 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . $row['phone'] . "</td>";
        echo "<td>" . ($row['status'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No results returned from query</p>";
}

echo "<h3>🔄 Processing with Duplicate Seat Filter (Original Logic):</h3>";

// Reset the query result
mysqli_data_seek(mysqli_query($db, $related_passengers_query), 0);
$related_passengers = mysqli_query($db, $related_passengers_query);

$all_passengers = [];
$seen_seats = [];
$step = 1;

while ($row = mysqli_fetch_assoc($related_passengers)) {
    echo "<p><strong>Step $step:</strong> Processing passenger '{$row['name']}' with seat '{$row['seat_no']}'</p>";
    
    if (!in_array($row['seat_no'], $seen_seats)) {
        $all_passengers[] = $row;
        $seen_seats[] = $row['seat_no'];
        echo "<p style='color: green;'>✅ Added to final list (seat not seen before)</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Skipped (seat already seen)</p>";
    }
    
    echo "<p><strong>Seen seats so far:</strong> " . implode(', ', $seen_seats) . "</p>";
    echo "<hr>";
    $step++;
}

echo "<h3>📊 Final Results After Processing:</h3>";
echo "<p><strong>Total passengers in final list:</strong> " . count($all_passengers) . "</p>";

if (!empty($all_passengers)) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Seat</th><th>Age</th><th>Gender</th></tr>";
    
    foreach ($all_passengers as $passenger) {
        echo "<tr>";
        echo "<td>" . $passenger['id'] . "</td>";
        echo "<td>" . htmlspecialchars($passenger['name']) . "</td>";
        echo "<td>" . ($passenger['seat_no'] ?: 'NULL') . "</td>";
        echo "<td>" . $passenger['age'] . "</td>";
        echo "<td>" . $passenger['gender'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No passengers in final list</p>";
}

echo "<h3>🔍 Potential Issues Analysis:</h3>";

// Check for potential issues
$issues = [];

// Check if there are NULL seat numbers
$null_seats = 0;
foreach ($raw_results as $row) {
    if (empty($row['seat_no'])) {
        $null_seats++;
    }
}

if ($null_seats > 0) {
    $issues[] = "⚠️ Found $null_seats passengers with NULL/empty seat numbers - this could cause filtering issues";
}

// Check for duplicate seat numbers
$seat_counts = [];
foreach ($raw_results as $row) {
    $seat = $row['seat_no'] ?: 'NULL';
    $seat_counts[$seat] = ($seat_counts[$seat] ?? 0) + 1;
}

foreach ($seat_counts as $seat => $count) {
    if ($count > 1) {
        $issues[] = "⚠️ Seat '$seat' appears $count times - duplicate seat filtering may remove valid passengers";
    }
}

if (empty($issues)) {
    echo "<p style='color: green;'>✅ No obvious issues detected</p>";
} else {
    echo "<ul>";
    foreach ($issues as $issue) {
        echo "<li style='color: orange;'>$issue</li>";
    }
    echo "</ul>";
}

echo "<h3>💡 Recommendations:</h3>";
echo "<ul>";
echo "<li>If you expect 5 passengers but only see " . count($all_passengers) . ", check if there are duplicate seat numbers or NULL seats</li>";
echo "<li>Consider removing the duplicate seat filter if all passengers should be shown regardless of seat duplication</li>";
echo "<li>Check if the query criteria are too restrictive</li>";
echo "</ul>";

?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { margin: 10px 0; border-collapse: collapse; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
    details { margin: 10px 0; }
    hr { margin: 15px 0; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
</style>
