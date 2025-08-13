<?php
// Start session for user management
session_start();

// Verify admin session
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once 'connect/db.php';

// Initialize database connection
$db_connection = new connect();
$db = $db_connection->myconnect();

// Check database connection
if (!$db || mysqli_connect_errno()) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Function to check if a column exists in a table
function columnExists($db, $table, $column) {
    $result = mysqli_query($db, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return (mysqli_num_rows($result) > 0);
}

// Array of columns to add
$columns = [
    'enable_animations' => "TINYINT(1) DEFAULT 1",
    'enable_hover_effects' => "TINYINT(1) DEFAULT 1",
    'enable_page_transitions' => "TINYINT(1) DEFAULT 1",
    'enable_scroll_animations' => "TINYINT(1) DEFAULT 1",
    'enable_button_effects' => "TINYINT(1) DEFAULT 1",
    'enable_menu_animations' => "TINYINT(1) DEFAULT 1",
    'animation_speed' => "VARCHAR(20) DEFAULT 'normal'"
];

// Check if table exists
$table_check = mysqli_query($db, "SHOW TABLES LIKE 'website_customization'");
if (mysqli_num_rows($table_check) == 0) {
    die("Error: The 'website_customization' table does not exist. Please run the initial setup first.");
}

// Add columns if they don't exist
$alter_queries = [];
foreach ($columns as $column => $type) {
    if (!columnExists($db, 'website_customization', $column)) {
        $alter_queries[] = "ADD COLUMN `$column` $type";
    }
}

// Execute ALTER TABLE if there are columns to add
if (!empty($alter_queries)) {
    $alter_sql = "ALTER TABLE `website_customization` " . implode(", ", $alter_queries);
    
    if (mysqli_query($db, $alter_sql)) {
        $message = "Database updated successfully with animation settings!";
        $message_type = "success";
    } else {
        $message = "Error updating database: " . mysqli_error($db);
        $message_type = "danger";
    }
} else {
    $message = "All animation settings columns already exist in the database.";
    $message_type = "info";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Animation Settings - IRCTC Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            padding: 2rem;
            background-color: #f8f9fa;
        }
        .card {
            max-width: 800px;
            margin: 2rem auto;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #1e3a8a;
            color: white;
            font-weight: 600;
        }
        .btn-primary {
            background-color: #1e3a8a;
            border-color: #1e3a8a;
        }
        .btn-primary:hover {
            background-color: #1e3a8a;
            border-color: #1e3a8a;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fas fa-sync-alt me-2"></i> Update Animation Settings</span>
                <a href="changing.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Back to Customization
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'danger' ? 'exclamation-circle' : 'info-circle'); ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>About Animation Settings</h5>
                    <p>This update adds the following settings to control animations on your website:</p>
                    <ul class="mb-0">
                        <li><strong>Enable All Animations:</strong> Master switch for all animations</li>
                        <li><strong>Hover Effects:</strong> Control hover animations on buttons and links</li>
                        <li><strong>Page Transitions:</strong> Fade and slide effects when navigating</li>
                        <li><strong>Scroll Animations:</strong> Animate elements as you scroll</li>
                        <li><strong>Button Effects:</strong> Ripple and press effects on buttons</li>
                        <li><strong>Menu Animations:</strong> Smooth dropdown and mobile menu animations</li>
                    </ul>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="changing.php" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i> Configure Animation Settings
                    </a>
                </div>
            </div>
            <div class="card-footer text-muted">
                <small>Last updated: <?php echo date('F j, Y, g:i a'); ?></small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
