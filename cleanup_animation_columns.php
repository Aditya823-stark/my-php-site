<?php
// Include database connection
include('connect/db.php');

// Start output buffering
ob_start();
echo "<pre>Starting database cleanup...\n";

// Function to execute and log SQL
function execute_sql($db, $sql, $success_msg) {
    echo "Executing: $sql\n";
    if (mysqli_query($db, $sql)) {
        echo "✓ $success_msg\n";
        return true;
    } else {
        echo "✗ Error: " . mysqli_error($db) . "\n";
        return false;
    }
}

try {
    // Initialize database connection
    $db = (new connect())->myconnect();
    
    // Check if table exists, if not create it
    $table_check = "SHOW TABLES LIKE 'website_customization'";
    $result = mysqli_query($db, $table_check);
    
    if (mysqli_num_rows($result) == 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE IF NOT EXISTS website_customization (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            primary_color VARCHAR(20) DEFAULT '#1e3a8a',
            secondary_color VARCHAR(20) DEFAULT '#f97316',
            accent_color VARCHAR(20) DEFAULT '#059669',
            site_title VARCHAR(255) DEFAULT 'IRCTC Website',
            contact_email VARCHAR(255) DEFAULT 'contact@irctc.com',
            contact_phone VARCHAR(50) DEFAULT '',
            marquee_speed INT(11) DEFAULT 50,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        execute_sql($db, $create_table, "Created website_customization table");
        
        // Insert default record
        $insert_default = "INSERT INTO website_customization (id) VALUES (1) ON DUPLICATE KEY UPDATE id=id";
        execute_sql($db, $insert_default, "Ensured default record exists");
    } else {
        // Table exists, check and modify columns
        $columns_to_remove = [
            'enable_animations',
            'enable_hover_effects',
            'enable_page_transitions',
            'enable_scroll_animations',
            'enable_button_effects',
            'enable_menu_animations'
        ];
        
        foreach ($columns_to_remove as $column) {
            $check_column = "SHOW COLUMNS FROM website_customization LIKE '$column'";
            $result = mysqli_query($db, $check_column);
            
            if (mysqli_num_rows($result) > 0) {
                $alter_query = "ALTER TABLE website_customization DROP COLUMN `$column`";
                execute_sql($db, $alter_query, "Removed column: $column");
            } else {
                echo "Column $column does not exist\n";
            }
        }
        
        // Add marquee_speed if it doesn't exist
        $check_marquee = "SHOW COLUMNS FROM website_customization LIKE 'marquee_speed'";
        $result = mysqli_query($db, $check_marquee);
        
        if (mysqli_num_rows($result) == 0) {
            $alter_query = "ALTER TABLE website_customization ADD COLUMN marquee_speed INT DEFAULT 50";
            execute_sql($db, $alter_query, "Added marquee_speed column");
        } else {
            echo "marquee_speed column already exists\n";
        }
    }
    
    echo "\n✓ Database cleanup completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
} finally {
    // Close database connection
    if (isset($db)) {
        mysqli_close($db);
    }
    
    // Flush output buffer and display results
    $output = ob_get_clean();
    echo $output;
    echo "\n\nYou can now safely close this window or <a href='changing.php'>go back to admin panel</a>.";
}
?>
