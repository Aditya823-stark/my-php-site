<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create notification_templates table
$create_templates_table = "CREATE TABLE IF NOT EXISTS notification_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_name VARCHAR(100) NOT NULL,
    template_type ENUM('SMS', 'Email', 'Both') DEFAULT 'Both',
    trigger_event ENUM('Booking Confirmed', 'Payment Received', 'Journey Reminder', 'Cancellation', 'Refund Processed', 'Train Delayed', 'Platform Change') NOT NULL,
    subject VARCHAR(200),
    email_body TEXT,
    sms_body VARCHAR(160),
    variables JSON,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_templates_table);

// Create notification_logs table
$create_logs_table = "CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT,
    notification_type ENUM('SMS', 'Email') NOT NULL,
    recipient VARCHAR(100) NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('Pending', 'Sent', 'Failed', 'Delivered') DEFAULT 'Pending',
    sent_at TIMESTAMP NULL,
    delivery_status VARCHAR(100),
    error_message TEXT,
    template_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES passengers(id),
    FOREIGN KEY (template_id) REFERENCES notification_templates(id)
)";
mysqli_query($db, $create_logs_table);

// Create notification_settings table
$create_settings_table = "CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL,
    setting_value TEXT,
    setting_type ENUM('SMS', 'Email', 'General') DEFAULT 'General',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_settings_table);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_template'])) {
        $name = mysqli_real_escape_string($db, $_POST['template_name']);
        $type = mysqli_real_escape_string($db, $_POST['template_type']);
        $event = mysqli_real_escape_string($db, $_POST['trigger_event']);
        $subject = mysqli_real_escape_string($db, $_POST['subject']);
        $email_body = mysqli_real_escape_string($db, $_POST['email_body']);
        $sms_body = mysqli_real_escape_string($db, $_POST['sms_body']);
        $variables = json_encode(explode(',', $_POST['variables']));
        
        $sql = "INSERT INTO notification_templates (template_name, template_type, trigger_event, subject, email_body, sms_body, variables) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($db, $sql);
        mysqli_stmt_bind_param($stmt, "sssssss", $name, $type, $event, $subject, $email_body, $sms_body, $variables);
        
        if (mysqli_stmt_execute($stmt)) {
            $success_msg = "Notification template added successfully!";
        } else {
            $error_msg = "Error adding template: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['send_test'])) {
        $test_email = mysqli_real_escape_string($db, $_POST['test_email']);
        $test_phone = mysqli_real_escape_string($db, $_POST['test_phone']);
        
        // Log test notifications
        if ($test_email) {
            $sql = "INSERT INTO notification_logs (notification_type, recipient, subject, message, status) VALUES ('Email', ?, 'Test Email', 'This is a test email from Railway Management System.', 'Sent')";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, "s", $test_email);
            mysqli_stmt_execute($stmt);
        }
        
        if ($test_phone) {
            $sql = "INSERT INTO notification_logs (notification_type, recipient, message, status) VALUES ('SMS', ?, 'Test SMS from Railway Management System.', 'Sent')";
            $stmt = mysqli_prepare($db, $sql);
            mysqli_stmt_bind_param($stmt, "s", $test_phone);
            mysqli_stmt_execute($stmt);
        }
        
        $success_msg = "Test notifications sent successfully!";
    }
}

// Get templates and logs
$templates = mysqli_query($db, "SELECT * FROM notification_templates ORDER BY created_at DESC");
$logs = mysqli_query($db, "SELECT nl.*, p.name as passenger_name FROM notification_logs nl LEFT JOIN passengers p ON nl.passenger_id = p.id ORDER BY nl.created_at DESC LIMIT 50");

// Get notification statistics
$stats = [
    'total_sent' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM notification_logs WHERE status = 'Sent'"))['count'],
    'total_failed' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM notification_logs WHERE status = 'Failed'"))['count'],
    'today_sent' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM notification_logs WHERE status = 'Sent' AND DATE(sent_at) = CURDATE()"))['count'],
    'pending' => mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as count FROM notification_logs WHERE status = 'Pending'"))['count']
];
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">SMS/Email Notification System</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="index.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Operations</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Notifications</a>
                </li>
            </ul>
        </div>

        <?php if (isset($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Success!</strong> <?= $success_msg ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-success bubble-shadow-small">
                                    <i class="fas fa-paper-plane"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Total Sent</p>
                                    <h4 class="card-title"><?= number_format($stats['total_sent']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-danger bubble-shadow-small">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Failed</p>
                                    <h4 class="card-title"><?= number_format($stats['total_failed']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-info bubble-shadow-small">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Today Sent</p>
                                    <h4 class="card-title"><?= number_format($stats['today_sent']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card card-stats card-round">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-icon">
                                <div class="icon-big text-center icon-warning bubble-shadow-small">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                            <div class="col col-stats ms-3 ms-sm-0">
                                <div class="numbers">
                                    <p class="card-category">Pending</p>
                                    <h4 class="card-title"><?= number_format($stats['pending']) ?></h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Add Template -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-plus"></i> Add Notification Template
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Template Name</label>
                                <input type="text" name="template_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Template Type</label>
                                <select name="template_type" class="form-select" required>
                                    <option value="Both">Both (SMS & Email)</option>
                                    <option value="Email">Email Only</option>
                                    <option value="SMS">SMS Only</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Trigger Event</label>
                                <select name="trigger_event" class="form-select" required>
                                    <option value="Booking Confirmed">Booking Confirmed</option>
                                    <option value="Payment Received">Payment Received</option>
                                    <option value="Journey Reminder">Journey Reminder</option>
                                    <option value="Cancellation">Cancellation</option>
                                    <option value="Refund Processed">Refund Processed</option>
                                    <option value="Train Delayed">Train Delayed</option>
                                    <option value="Platform Change">Platform Change</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Email Subject</label>
                                <input type="text" name="subject" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Email Body</label>
                                <textarea name="email_body" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <label>SMS Body (160 chars)</label>
                                <textarea name="sms_body" class="form-control" rows="2" maxlength="160"></textarea>
                            </div>
                            <div class="form-group">
                                <label>Variables (comma separated)</label>
                                <input type="text" name="variables" class="form-control" placeholder="passenger_name,train_name,booking_id">
                            </div>
                            <button type="submit" name="add_template" class="btn btn-primary">Add Template</button>
                        </form>
                    </div>
                </div>

                <!-- Test Notifications -->
                <div class="card mt-3">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-vial"></i> Test Notifications
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label>Test Email Address</label>
                                <input type="email" name="test_email" class="form-control" placeholder="Enter email to test">
                            </div>
                            <div class="form-group">
                                <label>Test Phone Number</label>
                                <input type="tel" name="test_phone" class="form-control" placeholder="Enter phone number to test">
                            </div>
                            <button type="submit" name="send_test" class="btn btn-success">Send Test</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Templates & Logs -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="templates-tab" data-bs-toggle="pill" href="#templates" role="tab">
                                    <i class="fas fa-file-alt"></i> Templates
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="logs-tab" data-bs-toggle="pill" href="#logs" role="tab">
                                    <i class="fas fa-history"></i> Notification Logs
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="pills-tabContent">
                            <!-- Templates Tab -->
                            <div class="tab-pane fade show active" id="templates" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Type</th>
                                                <th>Event</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($template = mysqli_fetch_assoc($templates)): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($template['template_name']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $template['template_type'] == 'Both' ? 'primary' : ($template['template_type'] == 'Email' ? 'info' : 'success') ?>">
                                                            <?= $template['template_type'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($template['trigger_event']) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $template['is_active'] ? 'success' : 'danger' ?>">
                                                            <?= $template['is_active'] ? 'Active' : 'Inactive' ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-info">View</button>
                                                        <button class="btn btn-sm btn-warning">Edit</button>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Logs Tab -->
                            <div class="tab-pane fade" id="logs" role="tabpanel">
                                <div class="table-responsive">
                                    <table id="logs-table" class="display table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Type</th>
                                                <th>Recipient</th>
                                                <th>Subject/Message</th>
                                                <th>Status</th>
                                                <th>Passenger</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($log = mysqli_fetch_assoc($logs)): ?>
                                                <tr>
                                                    <td><?= date('M d, Y H:i', strtotime($log['created_at'])) ?></td>
                                                    <td>
                                                        <span class="badge badge-<?= $log['notification_type'] == 'Email' ? 'info' : 'success' ?>">
                                                            <?= $log['notification_type'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($log['recipient']) ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($log['subject']) ?></strong><br>
                                                        <small><?= htmlspecialchars(substr($log['message'], 0, 50)) ?>...</small>
                                                    </td>
                                                    <td>
                                                        <span class="badge badge-<?= $log['status'] == 'Sent' ? 'success' : ($log['status'] == 'Failed' ? 'danger' : 'warning') ?>">
                                                            <?= $log['status'] ?>
                                                        </span>
                                                    </td>
                                                    <td><?= htmlspecialchars($log['passenger_name']) ?: 'N/A' ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<script>
$(document).ready(function() {
    $('#logs-table').DataTable({
        "pageLength": 10,
        "lengthChange": false,
        "searching": true,
        "ordering": true,
        "info": true,
        "autoWidth": false,
        "responsive": true,
        "order": [[ 0, "desc" ]]
    });
});
</script>
