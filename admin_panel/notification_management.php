<?php
session_start();
include('../connect/db.php');
include('../connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Handle form submissions
if ($_POST) {
    if (isset($_POST['add_notification'])) {
        $template_name = mysqli_real_escape_string($db, $_POST['template_name']);
        $template_type = $_POST['template_type'];
        $trigger_event = $_POST['trigger_event'];
        $subject = mysqli_real_escape_string($db, $_POST['subject']);
        $sms_body = mysqli_real_escape_string($db, $_POST['sms_body']);
        $email_body = mysqli_real_escape_string($db, $_POST['email_body']);
        
        $insert_sql = "INSERT INTO notification_templates (template_name, template_type, trigger_event, subject, sms_body, email_body, is_active) 
                      VALUES ('$template_name', '$template_type', '$trigger_event', '$subject', '$sms_body', '$email_body', TRUE)";
        mysqli_query($db, $insert_sql);
        $success_message = "Notification template created successfully!";
    }
    
    if (isset($_POST['toggle_notification'])) {
        $notification_id = $_POST['notification_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status == '1' ? '0' : '1';
        
        mysqli_query($db, "UPDATE notification_templates SET is_active = $new_status WHERE id = $notification_id");
        $success_message = "Notification status updated!";
    }
    
    if (isset($_POST['send_alert'])) {
        $alert_message = mysqli_real_escape_string($db, $_POST['alert_message']);
        $alert_type = $_POST['alert_type'];
        
        // Create a new notification template for the alert
        $insert_alert = "INSERT INTO notification_templates (template_name, template_type, trigger_event, sms_body, is_active) 
                        VALUES ('Emergency Alert', 'SMS', '$alert_type', '$alert_message', TRUE)";
        mysqli_query($db, $insert_alert);
        $success_message = "Emergency alert sent successfully!";
    }
}

// Fetch notifications
$notifications = mysqli_query($db, "SELECT * FROM notification_templates ORDER BY created_at DESC");
$notification_logs = mysqli_query($db, "SELECT nl.*, nt.template_name FROM notification_logs nl 
                                       LEFT JOIN notification_templates nt ON nl.template_id = nt.id 
                                       ORDER BY nl.created_at DESC LIMIT 20");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - Indian Railways Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #f97316;
            --success-color: #059669;
            --danger-color: #dc2626;
        }
        
        body {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 3px solid var(--secondary-color);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .notification-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            margin-bottom: 2rem;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            border: none;
            padding: 1.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 25px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .btn-success {
            background: linear-gradient(45deg, var(--success-color), #10b981);
            border: none;
            border-radius: 20px;
        }
        
        .btn-danger {
            background: linear-gradient(45deg, var(--danger-color), #ef4444);
            border: none;
            border-radius: 20px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.25);
        }
        
        .notification-item {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }
        
        .notification-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-active {
            background: linear-gradient(45deg, var(--success-color), #10b981);
            color: white;
        }
        
        .status-inactive {
            background: linear-gradient(45deg, #6b7280, #9ca3af);
            color: white;
        }
        
        .alert-section {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .log-item {
            border-left: 4px solid var(--secondary-color);
            padding-left: 1rem;
            margin-bottom: 1rem;
        }
        
        .nav-tabs .nav-link {
            border-radius: 10px 10px 0 0;
            border: none;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .nav-tabs .nav-link.active {
            background: var(--secondary-color);
            color: white;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h2 class="mb-0" style="color: var(--primary-color);">
                        <i class="fas fa-bell me-2"></i>Notification Management
                    </h2>
                </div>
                <div class="col-md-6 text-end">
                    <a href="../dashboard.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= $success_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Emergency Alert Section -->
        <div class="alert-section">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Send Emergency Alert</h4>
            <p>Send immediate notifications to all passengers about train delays, cancellations, or important updates.</p>
            <form method="POST" class="mt-3">
                <div class="row g-3">
                    <div class="col-md-4">
                        <select class="form-select" name="alert_type" required>
                            <option value="">Select Alert Type</option>
                            <option value="Train Delayed">Train Delayed</option>
                            <option value="Train Cancelled">Train Cancelled</option>
                            <option value="Platform Change">Platform Change</option>
                            <option value="Weather Alert">Weather Alert</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="alert_message" 
                               placeholder="Enter alert message (max 160 characters)" maxlength="160" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="send_alert" class="btn btn-danger w-100">
                            <i class="fas fa-broadcast-tower me-2"></i>Send Alert
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <ul class="nav nav-tabs mb-4" id="notificationTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="templates-tab" data-bs-toggle="tab" data-bs-target="#templates" type="button">
                    <i class="fas fa-template me-2"></i>Notification Templates
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="create-tab" data-bs-toggle="tab" data-bs-target="#create" type="button">
                    <i class="fas fa-plus me-2"></i>Create Template
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="logs-tab" data-bs-toggle="tab" data-bs-target="#logs" type="button">
                    <i class="fas fa-history me-2"></i>Notification Logs
                </button>
            </li>
        </ul>

        <div class="tab-content" id="notificationTabsContent">
            <!-- Templates Tab -->
            <div class="tab-pane fade show active" id="templates" role="tabpanel">
                <div class="card notification-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Active Notification Templates</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($notification = mysqli_fetch_assoc($notifications)): ?>
                            <div class="notification-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-2"><?= $notification['template_name'] ?></h6>
                                        <p class="text-muted mb-2">
                                            <strong>Trigger:</strong> <?= $notification['trigger_event'] ?> | 
                                            <strong>Type:</strong> <?= $notification['template_type'] ?>
                                        </p>
                                        <p class="mb-2"><?= substr($notification['sms_body'], 0, 100) ?>...</p>
                                        <small class="text-muted">
                                            Created: <?= date('M d, Y H:i', strtotime($notification['created_at'])) ?>
                                        </small>
                                    </div>
                                    <div class="ms-3">
                                        <span class="status-badge <?= $notification['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                            <?= $notification['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                        <form method="POST" class="d-inline ms-2">
                                            <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                            <input type="hidden" name="current_status" value="<?= $notification['is_active'] ?>">
                                            <button type="submit" name="toggle_notification" 
                                                    class="btn btn-sm <?= $notification['is_active'] ? 'btn-warning' : 'btn-success' ?>">
                                                <i class="fas <?= $notification['is_active'] ? 'fa-pause' : 'fa-play' ?>"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Create Template Tab -->
            <div class="tab-pane fade" id="create" role="tabpanel">
                <div class="card notification-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Create New Notification Template</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Template Name</label>
                                    <input type="text" class="form-control" name="template_name" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Type</label>
                                    <select class="form-select" name="template_type" required>
                                        <option value="SMS">SMS Only</option>
                                        <option value="Email">Email Only</option>
                                        <option value="Both">SMS & Email</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Trigger Event</label>
                                    <select class="form-select" name="trigger_event" required>
                                        <option value="Booking Confirmed">Booking Confirmed</option>
                                        <option value="Payment Received">Payment Received</option>
                                        <option value="Journey Reminder">Journey Reminder</option>
                                        <option value="Cancellation">Cancellation</option>
                                        <option value="Refund Processed">Refund Processed</option>
                                        <option value="Train Delayed">Train Delayed</option>
                                        <option value="Platform Change">Platform Change</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Email Subject</label>
                                    <input type="text" class="form-control" name="subject" placeholder="Email subject line">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SMS Message (160 chars max)</label>
                                    <textarea class="form-control" name="sms_body" rows="4" maxlength="160" 
                                              placeholder="SMS notification message"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Body</label>
                                    <textarea class="form-control" name="email_body" rows="4" 
                                              placeholder="Email notification content"></textarea>
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" name="add_notification" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Create Template
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Logs Tab -->
            <div class="tab-pane fade" id="logs" role="tabpanel">
                <div class="card notification-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Notification Logs</h5>
                    </div>
                    <div class="card-body">
                        <?php while ($log = mysqli_fetch_assoc($notification_logs)): ?>
                            <div class="log-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= $log['template_name'] ?? 'Unknown Template' ?></h6>
                                        <p class="mb-1">
                                            <strong>To:</strong> <?= $log['recipient'] ?> | 
                                            <strong>Type:</strong> <?= $log['notification_type'] ?>
                                        </p>
                                        <p class="text-muted mb-1"><?= substr($log['message'], 0, 80) ?>...</p>
                                        <small class="text-muted">
                                            <?= date('M d, Y H:i', strtotime($log['created_at'])) ?>
                                        </small>
                                    </div>
                                    <span class="badge <?= $log['status'] == 'Sent' ? 'bg-success' : ($log['status'] == 'Failed' ? 'bg-danger' : 'bg-warning') ?>">
                                        <?= $log['status'] ?>
                                    </span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Character counter for SMS
        document.querySelector('textarea[name="sms_body"]').addEventListener('input', function() {
            const maxLength = 160;
            const currentLength = this.value.length;
            const remaining = maxLength - currentLength;
            
            // Create or update counter
            let counter = this.parentElement.querySelector('.char-counter');
            if (!counter) {
                counter = document.createElement('small');
                counter.className = 'char-counter text-muted';
                this.parentElement.appendChild(counter);
            }
            
            counter.textContent = `${remaining} characters remaining`;
            counter.style.color = remaining < 20 ? '#dc2626' : '#6b7280';
        });

        // Auto-dismiss alerts
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);

        // Confirmation for emergency alerts
        document.querySelector('button[name="send_alert"]').addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to send this emergency alert to all passengers?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
