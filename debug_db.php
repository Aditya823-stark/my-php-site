<?php
// Database Connection Test and Debug Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Connection Debug</h2>";

// Test 1: Check if database connection works
include("connect/db.php");
include("connect/fun.php");

try {
    $db = (new connect())->myconnect();
    
    if ($db) {
        echo "✅ Database connection successful<br>";
        echo "Connection type: " . get_class($db) . "<br>";
        
        // Test 2: Check if database exists and tables are accessible
        $result = mysqli_query($db, "SHOW TABLES");
        if ($result) {
            echo "✅ Database 'rail_sys' exists and is accessible<br>";
            echo "<strong>Available tables:</strong><br>";
            while ($row = mysqli_fetch_array($result)) {
                echo "- " . $row[0] . "<br>";
            }
        } else {
            echo "❌ Error accessing database: " . mysqli_error($db) . "<br>";
        }
        
        // Test 3: Check if passengers table exists and its structure
        $result = mysqli_query($db, "DESCRIBE passengers");
        if ($result) {
            echo "<br>✅ 'passengers' table exists<br>";
            echo "<strong>Table structure:</strong><br>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['Field'] . "</td>";
                echo "<td>" . $row['Type'] . "</td>";
                echo "<td>" . $row['Null'] . "</td>";
                echo "<td>" . $row['Key'] . "</td>";
                echo "<td>" . $row['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "❌ 'passengers' table does not exist or error: " . mysqli_error($db) . "<br>";
        }
        
        // Test 4: Check if trains table has data
        $result = mysqli_query($db, "SELECT COUNT(*) as count FROM trains");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "<br>✅ Trains table has " . $row['count'] . " records<br>";
        } else {
            echo "<br>❌ Error checking trains table: " . mysqli_error($db) . "<br>";
        }
        
        // Test 5: Check if stations table has data
        $result = mysqli_query($db, "SELECT COUNT(*) as count FROM stations");
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "✅ Stations table has " . $row['count'] . " records<br>";
        } else {
            echo "❌ Error checking stations table: " . mysqli_error($db) . "<br>";
        }
        
        // Test 6: Test add_passenger function with sample data
        echo "<br><h3>Testing add_passenger function:</h3>";
        $fun = new fun($db);
        
        $test_data = [
            'train_id' => 1,
            'name' => 'Test User',
            'age' => 25,
            'gender' => 'Male',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'password' => 'test123',
            'from_station_id' => 1,
            'to_station_id' => 2,
            'class_type' => 'A',
            'journey_date' => '2024-01-15',
            'fare' => 100,
            'distance' => 50
        ];
        
        echo "Attempting to add test passenger...<br>";
        $result = $fun->add_passenger($test_data);
        
        if ($result) {
            echo "✅ Test passenger added successfully! ID: " . $result . "<br>";
            
            // Verify the record was actually inserted
            $verify = mysqli_query($db, "SELECT * FROM passengers WHERE id = $result");
            if ($verify && mysqli_num_rows($verify) > 0) {
                echo "✅ Record verified in database<br>";
                $passenger = mysqli_fetch_assoc($verify);
                echo "Name: " . $passenger['name'] . "<br>";
                echo "Email: " . $passenger['email'] . "<br>";
            } else {
                echo "❌ Record not found in database after insert<br>";
            }
        } else {
            echo "❌ Failed to add test passenger<br>";
        }
        
    } else {
        echo "❌ Database connection failed<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception occurred: " . $e->getMessage() . "<br>";
}

echo "<br><h3>PHP Configuration:</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "MySQL Extension: " . (extension_loaded('mysqli') ? '✅ Loaded' : '❌ Not loaded') . "<br>";

?>
