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

// Create alerts table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS train_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_type ENUM('danger', 'warning', 'info', 'success', 'delay', 'cancellation', 'diversion') NOT NULL,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($db, $create_table)) {
    $message = "Train alerts table created successfully!";
    $message_type = "success";
    
    // Insert some default alerts if table is empty
    $check_alerts = mysqli_query($db, "SELECT COUNT(*) as count FROM train_alerts");
    $count = mysqli_fetch_assoc($check_alerts)['count'];
    
    if ($count == 0) {
        $default_alerts = [
            ['delay', 'Train Delay', 'Train 12345 is running 2 hours late due to foggy conditions.', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+1 day')), 1],
            ['info', 'Platform Change', 'Train 67890 will depart from Platform 5 instead of Platform 3.', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+3 hours')), 1],
            ['warning', 'Maintenance Work', 'Delays expected due to maintenance work between stations A and B.', date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+1 week')), 1]
        ];
        
        $stmt = mysqli_prepare($db, "INSERT INTO train_alerts (alert_type, title, message, start_date, end_date, is_active) VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($default_alerts as $alert) {
            mysqli_stmt_bind_param($stmt, 'sssssi', ...$alert);
            mysqli_stmt_execute($stmt);
        }
        
        $message .= " Added default alerts.";
    }
} else {
    $message = "Error creating table: " . mysqli_error($db);
    $message_type = "danger";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Alerts System - IRCTC Admin</title>
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
                <span><i class="fas fa-bell me-2"></i> Update Alerts System</span>
                <a href="changing.php" class="btn btn-sm btn-outline-light">
                    <i class="fas fa-arrow-left me-1"></i> Back to Customization
                </a>
            </div>
            <div class="card-body">
                <?php if (isset($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <h5 class="alert-heading"><i class="fas fa-info-circle me-2"></i>About Train Alerts System</h5>
                    <p>This update adds a comprehensive train alerts system with the following features:</p>
                    <ul class="mb-0">
                        <li>Multiple alert types: Danger, Warning, Info, Success, Delay, Cancellation, Diversion</li>
                        <li>Scheduled alerts with start and end dates</li>
                        <li>News ticker display on the main website</li>
                        <li>Responsive design for all devices</li>
                        <li>Easy management through admin panel</li>
                    </ul>
                </div>
                
                <div class="d-grid gap-2">
                    <a href="changing.php" class="btn btn-primary">
                        <i class="fas fa-cog me-2"></i> Configure Alerts System
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
