<?php
// Start session for user management
session_start();

// Include database connection
include('connect/db.php');

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access. Please log in as an administrator.");
}

try {
    // Initialize database connection
    $db = (new connect())->myconnect();
    
    // Add marquee_speed column if it doesn't exist
    $check_column = "SHOW COLUMNS FROM website_customization LIKE 'marquee_speed'";
    $result = mysqli_query($db, $check_column);
    
    if (mysqli_num_rows($result) == 0) {
        // Column doesn't exist, add it
        $alter_query = "ALTER TABLE website_customization ADD COLUMN marquee_speed INT DEFAULT 50 AFTER enable_transitions";
        if (mysqli_query($db, $alter_query)) {
            echo "Successfully added marquee_speed column to website_customization table.<br>";
            
            // Set default value for existing records
            $update_query = "UPDATE website_customization SET marquee_speed = 50 WHERE marquee_speed IS NULL";
            if (mysqli_query($db, $update_query)) {
                echo "Set default marquee speed to 50 for all existing records.<br>";
            } else {
                echo "Error setting default marquee speed: " . mysqli_error($db) . "<br>";
            }
        } else {
            echo "Error adding marquee_speed column: " . mysqli_error($db) . "<br>";
        }
    } else {
        echo "marquee_speed column already exists in website_customization table.<br>";
    }
    
    echo "<br>Marquee speed update completed. <a href='changing.php'>Go back to admin panel</a>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Close database connection
if (isset($db)) {
    mysqli_close($db);
}
?>
