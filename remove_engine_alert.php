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
    
    // First, let's find the alert to confirm
    $find_query = "SELECT * FROM train_alerts WHERE message LIKE '%engine is repairing%' OR title = 'Engime' OR message LIKE '%hey engine%'";
    $result = mysqli_query($db, $find_query);
    
    if (mysqli_num_rows($result) > 0) {
        echo "Found " . mysqli_num_rows($result) . " matching alert(s):<br><br>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "ID: " . $row['id'] . "<br>";
            echo "Title: " . htmlspecialchars($row['title']) . "<br>";
            echo "Message: " . htmlspecialchars($row['message']) . "<br>";
            echo "Start Date: " . $row['start_date'] . "<br>";
            echo "End Date: " . $row['end_date'] . "<br><br>";
        }
        
        // Now delete the alerts
        $delete_query = "DELETE FROM train_alerts WHERE message LIKE '%engine is repairing%' OR title = 'Engime' OR message LIKE '%hey engine%'";
        $delete_result = mysqli_query($db, $delete_query);
        
        if ($delete_result) {
            $affected_rows = mysqli_affected_rows($db);
            echo "<br>Successfully deleted $affected_rows alert(s).<br>";
        } else {
            echo "<br>Error deleting alerts: " . mysqli_error($db) . "<br>";
        }
    } else {
        echo "No matching alerts found in the database.<br>";
        echo "The alert might be hardcoded in the PHP file or coming from another source.<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

// Close database connection
if (isset($db)) {
    mysqli_close($db);
}

echo "<br><br><a href='irctc_website.php'>Back to Website</a> | ";
echo "<a href='admin_panel.php'>Go to Admin Panel</a>";
?>
