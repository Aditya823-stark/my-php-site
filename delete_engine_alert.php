<?php
// Start session for user management
session_start();

// Include database connection
include('connect/db.php');

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Unauthorized access");
}

try {
    // Initialize database connection
    $db = (new connect())->myconnect();
    
    // Delete the specific alert
    $delete_query = "DELETE FROM train_alerts WHERE message LIKE '%hey engine is repairing%' OR title = 'Engime'";
    $result = mysqli_query($db, $delete_query);
    
    if ($result) {
        $affected_rows = mysqli_affected_rows($db);
        if ($affected_rows > 0) {
            echo "Successfully deleted $affected_rows alert(s).";
        } else {
            echo "No matching alerts found to delete.";
        }
    } else {
        echo "Error deleting alert: " . mysqli_error($db);
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Close database connection
if (isset($db)) {
    mysqli_close($db);
}

// Redirect back to the admin page
echo "<br><br><a href='irctc_website.php'>Back to Admin Panel</a>";
?>
