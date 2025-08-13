<?php
// Start session for user management
session_start();

// Include database connection and functions
require_once 'connect/db.php';
require_once 'connect/fun.php';

// Verify admin session
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Initialize database connection
try {
    $db_connection = new connect();
    $db = $db_connection->myconnect();
    $fun = new fun($db);
    $connection_status = "success";
} catch (Exception $e) {
    $connection_status = "error";
    $error_message = $e->getMessage();
    die("Database connection failed: " . $error_message);
}

// Check database connection
if (!isset($db) || !$db || mysqli_connect_errno()) {
    $connection_status = "error";
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle form submissions
$message = '';
$message_type = '';

// Handle alert creation/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_alert'])) {
        $id = isset($_POST['alert_id']) ? (int)$_POST['alert_id'] : 0;
        $alert_type = mysqli_real_escape_string($db, $_POST['alert_type']);
        $title = mysqli_real_escape_string($db, $_POST['title']);
        $message_text = mysqli_real_escape_string($db, $_POST['message']);
        $start_date = mysqli_real_escape_string($db, $_POST['start_date']);
        $end_date = mysqli_real_escape_string($db, $_POST['end_date']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($id > 0) {
            // Update existing alert
            $query = "UPDATE train_alerts SET 
                     alert_type = '$alert_type',
                     title = '$title',
                     message = '$message_text',
                     start_date = '$start_date',
                     end_date = '$end_date',
                     is_active = $is_active
                     WHERE id = $id";
        } else {
            // Insert new alert
            $query = "INSERT INTO train_alerts 
                     (alert_type, title, message, start_date, end_date, is_active)
                     VALUES 
                     ('$alert_type', '$title', '$message_text', '$start_date', '$end_date', $is_active)";
        }
        
        if (mysqli_query($db, $query)) {
            $message = "Alert " . ($id > 0 ? 'updated' : 'created') . " successfully!";
            $message_type = "success";
        } else {
            $message = "Error: " . mysqli_error($db);
            $message_type = "danger";
        }
    } elseif (isset($_POST['delete_alert'])) {
        $id = (int)$_POST['alert_id'];
        if ($id > 0) {
            $query = "DELETE FROM train_alerts WHERE id = $id";
            if (mysqli_query($db, $query)) {
                $message = "Alert deleted successfully!";
                $message_type = "success";
            } else {
                $message = "Error deleting alert: " . mysqli_error($db);
                $message_type = "danger";
            }
        }
    }
}

// Get all active alerts
$alerts_query = "SELECT * FROM train_alerts ORDER BY created_at DESC";
$alerts_result = mysqli_query($db, $alerts_query);
$alerts = [];
if ($alerts_result) {
    while ($row = mysqli_fetch_assoc($alerts_result)) {
        $alerts[] = $row;
    }
}

// Get alert types
$alert_types = [
    'danger' => 'Danger (Red)',
    'warning' => 'Warning (Yellow)',
    'info' => 'Info (Blue)',
    'success' => 'Success (Green)',
    'delay' => 'Train Delay (Orange)',
    'cancellation' => 'Cancellation (Red)',
    'diversion' => 'Diversion (Purple)'
];

if ($_POST && $connection_status === "success") {
    if (isset($_POST['update_colors'])) {
        // Sanitize and validate color inputs
        $primary_color = filter_var($_POST['primary_color'], FILTER_SANITIZE_STRING);
        $secondary_color = filter_var($_POST['secondary_color'], FILTER_SANITIZE_STRING);
        $accent_color = filter_var($_POST['accent_color'], FILTER_SANITIZE_STRING);
        $marquee_speed = isset($_POST['marquee_speed']) ? (int)$_POST['marquee_speed'] : 50;
        
        // Validate color format (simple hex color validation)
        if (!preg_match('/^#[a-f0-9]{6}$/i', $primary_color)) {
            $primary_color = '#1e3a8a'; // Default primary color if invalid
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $secondary_color)) {
            $secondary_color = '#f97316'; // Default secondary color if invalid
        }
        if (!preg_match('/^#[a-f0-9]{6}$/i', $accent_color)) {
            $accent_color = '#059669'; // Default accent color if invalid
        }
        
        // Get and sanitize color values
        $primary_color = mysqli_real_escape_string($db, $primary_color);
        $secondary_color = mysqli_real_escape_string($db, $secondary_color);
        $accent_color = mysqli_real_escape_string($db, $accent_color);
        
        // Get site information with defaults
        $site_title = isset($_POST['site_title']) ? mysqli_real_escape_string($db, $_POST['site_title']) : 'IRCTC Website';
        $contact_email = isset($_POST['contact_email']) ? mysqli_real_escape_string($db, $_POST['contact_email']) : 'contact@irctc.com';
        $contact_phone = isset($_POST['contact_phone']) ? mysqli_real_escape_string($db, $_POST['contact_phone']) : '';
        
        // Check if customization record exists
        $check_query = "SELECT id FROM website_customization WHERE id = 1";
        $result = mysqli_query($db, $check_query);
        
        if (mysqli_num_rows($result) > 0) {
            // Update existing record
            $query = "UPDATE website_customization SET 
                     primary_color = '$primary_color',
                     secondary_color = '$secondary_color',
                     accent_color = '$accent_color',
                     site_title = " . ($site_title ? "'$site_title'" : "NULL") . ",
                     contact_email = " . ($contact_email ? "'$contact_email'" : "NULL") . ",
                     contact_phone = " . ($contact_phone ? "'$contact_phone'" : "NULL") . ",
                     marquee_speed = " . (int)$marquee_speed . ",
                     updated_at = NOW()
                     WHERE id = 1";
        } else {
            // Insert new record with only the fields we know exist
            $query = "INSERT INTO website_customization 
                     (id, primary_color, secondary_color, accent_color, site_title, contact_email, contact_phone, marquee_speed, created_at, updated_at)
                     VALUES (1, " . 
                     "'$primary_color', " . 
                     "'$secondary_color', " . 
                     "'$accent_color', " .
                     ($site_title ? "'$site_title'" : "NULL") . ", " .
                     ($contact_email ? "'$contact_email'" : "NULL") . ", " .
                     ($contact_phone ? "'$contact_phone'" : "NULL") . ", " .
                     (int)$marquee_speed . ", " .
                     "NOW(), NOW())";
        }
        
        if (mysqli_query($db, $query)) {
            $message = "Settings updated successfully!";
            $message_type = "success";
            
            // Update the customization array to reflect changes
            $customization['primary_color'] = $primary_color;
            $customization['secondary_color'] = $secondary_color;
            $customization['accent_color'] = $accent_color;
            $customization['site_title'] = $site_title;
            $customization['contact_email'] = $contact_email;
            $customization['contact_phone'] = $contact_phone;
            $customization['marquee_speed'] = $marquee_speed;
        } else {
            $message = "Error updating settings: " . mysqli_error($db);
            $message_type = "danger";
        }
    }
    
    if (isset($_POST['update_site_info'])) {
        // Update site information
        $site_title = mysqli_real_escape_string($db, $_POST['site_title']);
        $contact_phone = mysqli_real_escape_string($db, $_POST['contact_phone']);
        $contact_email = mysqli_real_escape_string($db, $_POST['contact_email']);
        
        $update_query = "UPDATE website_customization SET 
                        site_title = '$site_title',
                        contact_phone = '$contact_phone',
                        contact_email = '$contact_email'
                        WHERE id = 1";
        
        if (mysqli_query($db, $update_query)) {
            $message = "Site information updated successfully!";
            $message_type = "success";
        } else {
            $message = "Error updating site information: " . mysqli_error($db);
            $message_type = "error";
        }
    }
    
    if (isset($_POST['add_carousel_slide'])) {
        // Add new carousel slide
        $title = mysqli_real_escape_string($db, $_POST['slide_title']);
        $description = isset($_POST['slide_description']) ? mysqli_real_escape_string($db, $_POST['slide_description']) : '';
        $button_text = isset($_POST['slide_button_text']) ? mysqli_real_escape_string($db, $_POST['slide_button_text']) : '';
        $button_link = isset($_POST['slide_button_link']) ? mysqli_real_escape_string($db, $_POST['slide_button_link']) : '';
        $image_url = '';
        
        // Handle file upload or URL
        if (isset($_FILES['slide_image_file']) && $_FILES['slide_image_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/carousel/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['slide_image_file']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
            
            if (!in_array($file_extension, $allowed_extensions)) {
                $message = "Error: Only JPG, JPEG, PNG & GIF files are allowed.";
                $message_type = "danger";
            } else {
                $file_name = uniqid() . '.' . $file_extension;
                $target_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['slide_image_file']['tmp_name'], $target_path)) {
                    $image_url = $target_path;
                } else {
                    $message = "Error uploading image file.";
                    $message_type = "danger";
                }
            }
        } elseif (!empty($_POST['slide_image_url'])) {
            $image_url = filter_var($_POST['slide_image_url'], FILTER_VALIDATE_URL);
            if ($image_url === false) {
                $message = "Error: Invalid image URL.";
                $message_type = "danger";
            }
        } else {
            $message = "Error: Please provide an image file or URL.";
            $message_type = "danger";
        }
        
        if (!empty($image_url)) {
            $button_text_sql = !empty($button_text) ? "'$button_text'" : 'NULL';
            $button_link_sql = !empty($button_link) ? "'$button_link'" : 'NULL';
            $sort_order = !empty($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
            $is_active = 1; // Default to active
            
            $query = "INSERT INTO carousel_slides 
                     (title, description, image_url, button_text, button_link, sort_order, is_active) 
                     VALUES 
                     ('$title', '$description', '$image_url', 
                      $button_text_sql, 
                      $button_link_sql, 
                      $sort_order, $is_active)";
            
            if (mysqli_query($db, $query)) {
                $message = "Carousel slide added successfully!";
                $message_type = "success";
                // Refresh the page to show the new slide
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $message = "Error adding carousel slide: " . mysqli_error($db);
                $message_type = "danger";
            }
        }
    }
    
    // Handle toggle slide status
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_slide'])) {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
            if (!isset($_POST['id']) || !is_numeric($_POST['id']) || !isset($_POST['status'])) {
                throw new Exception('Invalid parameters');
            }
            
            $slideId = (int)$_POST['id'];
            $status = $_POST['status'] === '1' ? 1 : 0;
            
            // Update the slide status in the database
            $query = "UPDATE carousel_slides SET is_active = $status WHERE id = $slideId";
            $result = mysqli_query($db, $query);
            
            if (!$result) {
                throw new Exception('Failed to update slide status: ' . mysqli_error($db));
            }
            
            $response['success'] = true;
            $response['message'] = 'Slide status updated successfully';
            
        } catch (Exception $e) {
            http_response_code(400);
            $response['message'] = $e->getMessage();
        }
        
        echo json_encode($response);
        exit();
    }
    
    // Handle delete slide
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_slide'])) {
        header('Content-Type: application/json');
        $response = ['success' => false, 'message' => ''];
        
        try {
            if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
                throw new Exception('Invalid slide ID');
            }
            
            $slideId = (int)$_POST['id'];
            
            // Get slide info first to delete the image file
            $query = "SELECT image_url FROM carousel_slides WHERE id = $slideId";
            $result = mysqli_query($db, $query);
            
            if (!$result || mysqli_num_rows($result) === 0) {
                throw new Exception('Slide not found');
            }
            
            $slide = mysqli_fetch_assoc($result);
            $imagePath = $slide['image_url'];
            
            // Delete from database
            $query = "DELETE FROM carousel_slides WHERE id = $slideId";
            $result = mysqli_query($db, $query);
            
            if (!$result) {
                throw new Exception('Failed to delete slide: ' . mysqli_error($db));
            }
            
            // If it's a local file, delete it
            if (!empty($imagePath) && strpos($imagePath, 'http') !== 0) {
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($imagePath, PHP_URL_PATH);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
            }
            
            $response['success'] = true;
            $response['message'] = 'Slide deleted successfully';
            
        } catch (Exception $e) {
            http_response_code(400);
            $response['message'] = $e->getMessage();
        }
        
        echo json_encode($response);
        exit();
    }
}

// Initialize or verify website_customization table
$create_table_query = "CREATE TABLE IF NOT EXISTS website_customization (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    enable_animations TINYINT(1) DEFAULT 1,
    enable_hover_effects TINYINT(1) DEFAULT 1,
    enable_page_transitions TINYINT(1) DEFAULT 1,
    enable_scroll_animations TINYINT(1) DEFAULT 1,
    enable_button_effects TINYINT(1) DEFAULT 1,
    enable_menu_animations TINYINT(1) DEFAULT 1,
    animation_speed VARCHAR(20) DEFAULT 'normal',
    site_title VARCHAR(255) DEFAULT 'IRCTC Rail Connect',
    site_description TEXT,
    primary_color VARCHAR(7) DEFAULT '#1e3a8a',
    secondary_color VARCHAR(7) DEFAULT '#f97316',
    accent_color VARCHAR(7) DEFAULT '#059669',
    contact_phone VARCHAR(20),
    contact_email VARCHAR(255)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($db, $create_table_query)) {
    $message = "Error creating website_customization table: " . mysqli_error($db);
    $message_type = "error";
}

// Insert default values if no record exists
$check_table = mysqli_query($db, "SELECT * FROM website_customization WHERE id = 1");
if (mysqli_num_rows($check_table) == 0) {
    $insert_defaults = "INSERT INTO website_customization (id, site_title, primary_color, secondary_color, accent_color) 
                       VALUES (1, 'IRCTC Rail Connect', '#1e3a8a', '#f97316', '#059669')";
    if (!mysqli_query($db, $insert_defaults)) {
        $message = "Error initializing default settings: " . mysqli_error($db);
        $message_type = "error";
    }
}

// Fetch current settings
$customization = [];
$settings_result = mysqli_query($db, "SELECT * FROM website_customization WHERE id = 1");
if ($settings_result && mysqli_num_rows($settings_result) > 0) {
    $customization = mysqli_fetch_assoc($settings_result);
}

// Get carousel slides
$carousel_slides = [];

if ($connection_status === "success") {
    // Fetch website customization
    $customization_query = "SELECT * FROM website_customization WHERE id = 1 LIMIT 1";
    $customization_result = mysqli_query($db, $customization_query);
    if ($customization_result && mysqli_num_rows($customization_result) > 0) {
        $customization = mysqli_fetch_assoc($customization_result);
    } else {
        // Create default entry if none exists
        $default_query = "INSERT INTO website_customization (id, primary_color, secondary_color, accent_color, site_title, logo_url, contact_phone, contact_email) 
                         VALUES (1, '#1e3a8a', '#f97316', '#059669', 'IRCTC Rail Connect', 'IRCTC-logo1.png', '139', 'care@irctc.co.in')";
        mysqli_query($db, $default_query);
        
        $customization = [
            'primary_color' => '#1e3a8a',
            'secondary_color' => '#f97316', 
            'accent_color' => '#059669',
            'site_title' => 'IRCTC Rail Connect',
            'logo_url' => 'IRCTC-logo1.png',
            'contact_phone' => '139',
            'contact_email' => 'care@irctc.co.in'
        ];
    }
    
    // Fetch carousel slides
    $carousel_query = "SELECT * FROM carousel_slides ORDER BY sort_order ASC";
    $carousel_result = mysqli_query($db, $carousel_query);
    if ($carousel_result) {
        while ($slide = mysqli_fetch_assoc($carousel_result)) {
            $carousel_slides[] = $slide;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Website Customization - IRCTC Admin</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        body {
            background: #f8fafc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            color: white;
            padding: 20px 0;
            margin-bottom: 30px;
        }
        
        .admin-header h1 {
            margin: 0;
            font-weight: 600;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .card-header {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-bottom: 2px solid #e2e8f0;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .card-header h5 {
            margin: 0;
            color: #1f2937;
            font-weight: 600;
        }
        
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 8px;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #f97316;
            box-shadow: 0 0 0 0.2rem rgba(249, 115, 22, 0.25);
        }
        
        .btn {
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            border: none;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #1e40af, #1d4ed8);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #059669, #047857);
            border: none;
        }
        
        .btn-success:hover {
            background: linear-gradient(135deg, #047857, #065f46);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            border: none;
        }
        
        .btn-danger:hover {
            background: linear-gradient(135deg, #b91c1c, #991b1b);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #f97316, #ea580c);
            border: none;
            color: white;
        }
        
        .btn-warning:hover {
            background: linear-gradient(135deg, #ea580c, #dc2626);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(249, 115, 22, 0.3);
            color: white;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5, #a7f3d0);
            color: #065f46;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        .color-preview {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            display: inline-block;
            margin-left: 10px;
            vertical-align: middle;
        }
        
        /* Alert Ticker Styles */
        .alert-ticker {
            background-color: #f8f9fa;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1000;
            margin-bottom: 1rem;
        }
        
        .ticker-header {
            background-color: #1e3a8a;
            color: white;
            padding: 6px 15px;
            font-weight: 600;
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        
        .ticker-header i {
            color: #ffc107;
        }
        
        .ticker-content {
            overflow: hidden;
            position: relative;
            background-color: white;
            padding: 5px 0;
        }
        
        .ticker-track {
            display: flex;
            padding: 5px 0;
            animation: ticker 60s linear infinite;
            white-space: nowrap;
            align-items: center;
        }
        
        .ticker-item {
            display: inline-flex;
            margin-right: 30px;
            border-radius: 4px;
            font-size: 0.85rem;
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        
        .ticker-item:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .ticker-item .badge {
            font-size: 0.65rem;
            padding: 0.2rem 0.5rem;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        @keyframes ticker {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }
        
        /* Alert type specific styles */
        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            border-color: #ffeeba;
            color: #856404;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            border-color: #bee5eb;
            color: #0c5460;
        }
        
        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .alert-delay {
            background-color: #fff3e6;
            border-color: #ffe0b3;
            color: #663300;
        }
        
        .alert-cancellation {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .alert-diversion {
            background-color: #e6e6ff;
            border-color: #ccccff;
            color: #333399;
        }
        
        /* Badge colors for alert types */
        .bg-delay {
            background-color: #ff9800 !important;
        }
        
        .bg-cancellation {
            background-color: #dc3545 !important;
        }
        
        .bg-diversion {
            background-color: #6f42c1 !important;
        }
        
        .slide-preview {
            background: #f8fafc;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            border: 2px solid #e5e7eb;
        }
        
        .slide-preview h6 {
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .slide-preview p {
            color: #6b7280;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .preview-section {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-top: 20px;
            border: 2px solid #e5e7eb;
        }
        
        .navbar-preview {
            background: linear-gradient(135deg, var(--primary-color, #1e3a8a), #1e40af);
            color: white;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <h1><i class="fas fa-cogs"></i> Website Customization</h1>
                <div class="d-flex gap-2">
                    <a href="update_animation_settings.php" class="btn btn-outline-info" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Update animation settings">
                        <i class="fas fa-sync-alt"></i> Update Animations
                    </a>
                    <a href="index.php" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <a href="irctc_website.php" class="btn btn-warning" target="_blank">
                        <i class="fas fa-eye"></i> Preview Website
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Train Alerts Management -->
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-train me-2"></i> Train Alerts Management</h5>
                    </div>
                    <div class="card-body">
                        <!-- Alert Form -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h6 class="mb-0"><?php echo isset($_GET['edit']) ? 'Edit' : 'Add New'; ?> Alert</h6>
                            </div>
                            <div class="card-body">
                                <form method="POST" class="needs-validation" novalidate>
                                    <?php
                                    $editing_alert = null;
                                    if (isset($_GET['edit'])) {
                                        $edit_id = (int)$_GET['edit'];
                                        foreach ($alerts as $alert) {
                                            if ($alert['id'] == $edit_id) {
                                                $editing_alert = $alert;
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <input type="hidden" name="alert_id" value="<?php echo $editing_alert ? $editing_alert['id'] : ''; ?>">
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="alert_type" class="form-label">Alert Type</label>
                                            <select class="form-select" id="alert_type" name="alert_type" required>
                                                <option value="">Select alert type...</option>
                                                <?php foreach ($alert_types as $value => $label): ?>
                                                    <option value="<?php echo $value; ?>" 
                                                        <?php echo ($editing_alert && $editing_alert['alert_type'] === $value) ? 'selected' : ''; ?>>
                                                        <?php echo $label; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select an alert type.
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="title" class="form-label">Title</label>
                                            <input type="text" class="form-control" id="title" name="title" 
                                                   value="<?php echo $editing_alert ? htmlspecialchars($editing_alert['title']) : ''; ?>" required>
                                            <div class="invalid-feedback">
                                                Please provide a title.
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="message" class="form-label">Message</label>
                                        <textarea class="form-control" id="message" name="message" rows="3" required><?php 
                                            echo $editing_alert ? htmlspecialchars($editing_alert['message']) : ''; 
                                        ?></textarea>
                                        <div class="invalid-feedback">
                                            Please provide a message.
                                        </div>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="start_date" class="form-label">Start Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="start_date" name="start_date" 
                                                   value="<?php echo $editing_alert ? date('Y-m-d\TH:i', strtotime($editing_alert['start_date'])) : date('Y-m-d\TH:i'); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date & Time</label>
                                            <input type="datetime-local" class="form-control" id="end_date" name="end_date" 
                                                   value="<?php echo $editing_alert ? date('Y-m-d\TH:i', strtotime($editing_alert['end_date'])) : date('Y-m-d\TH:i', strtotime('+1 day')); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                               <?php echo ($editing_alert && $editing_alert['is_active']) || !$editing_alert ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <button type="submit" name="save_alert" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Alert
                                        </button>
                                        <?php if ($editing_alert): ?>
                                            <a href="changing.php" class="btn btn-secondary">
                                                <i class="fas fa-times me-1"></i> Cancel
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Alerts List -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Active Alerts</h6>
                            </div>
                            <div class="card-body p-0">
                                <?php if (empty($alerts)): ?>
                                    <div class="p-4 text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <p class="mb-0">No alerts found. Add your first alert using the form above.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Title</th>
                                                    <th>Message</th>
                                                    <th>Status</th>
                                                    <th>Period</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($alerts as $alert): 
                                                    $is_active = $alert['is_active'] && 
                                                               strtotime($alert['start_date']) <= time() && 
                                                               strtotime($alert['end_date']) >= time();
                                                    $status_class = $is_active ? 'success' : 'secondary';
                                                    $status_text = $is_active ? 'Active' : 'Inactive';
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-<?php echo $alert['alert_type']; ?>">
                                                                <?php echo ucfirst($alert['alert_type']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($alert['title']); ?></td>
                                                        <td><?php echo htmlspecialchars(substr($alert['message'], 0, 50)) . (strlen($alert['message']) > 50 ? '...' : ''); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                                <?php echo $status_text; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <small>
                                                                <?php echo date('M j, g:i a', strtotime($alert['start_date'])); ?><br>
                                                                to <?php echo date('M j, g:i a', strtotime($alert['end_date'])); ?>
                                                            </small>
                                                        </td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <a href="?edit=<?php echo $alert['id']; ?>#alert-form" class="btn btn-outline-primary" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                                <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this alert?')">
                                                                    <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                                                    <button type="submit" name="delete_alert" class="btn btn-outline-danger" title="Delete">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <!-- Color Customization -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-palette"></i> Color Customization</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-4">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="primary_color" class="form-label">Primary Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="primary_color" 
                                                   name="primary_color" value="<?php echo htmlspecialchars($customization['primary_color'] ?? '#1e3a8a'); ?>"
                                                   oninput="updateColorPreview('primary_color', this.value)">
                                            <input type="text" class="form-control" id="primary_text" 
                                                   value="<?php echo htmlspecialchars($customization['primary_color'] ?? '#1e3a8a'); ?>"
                                                   onchange="document.getElementById('primary_color').value=this.value; updateColorPreview('primary_color', this.value)">
                                        </div>
                                        <div class="form-text">Navbar, Buttons</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="secondary_color" class="form-label">Secondary Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="secondary_color" 
                                                   name="secondary_color" value="<?php echo htmlspecialchars($customization['secondary_color'] ?? '#f97316'); ?>"
                                                   oninput="updateColorPreview('secondary_color', this.value)">
                                            <input type="text" class="form-control" id="secondary_text"
                                                   value="<?php echo htmlspecialchars($customization['secondary_color'] ?? '#f97316'); ?>"
                                                   onchange="document.getElementById('secondary_color').value=this.value; updateColorPreview('secondary_color', this.value)">
                                        </div>
                                        <div class="form-text">Accents, Highlights</div>
                                    </div>
                                    
                                    <div class="col-md-4">
                                        <label for="accent_color" class="form-label">Accent Color</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color" id="accent_color" 
                                                   name="accent_color" value="<?php echo htmlspecialchars($customization['accent_color'] ?? '#059669'); ?>"
                                                   oninput="updateColorPreview('accent_color', this.value)">
                                            <input type="text" class="form-control" id="accent_text"
                                                   value="<?php echo htmlspecialchars($customization['accent_color'] ?? '#059669'); ?>"
                                                   onchange="document.getElementById('accent_color').value=this.value; updateColorPreview('accent_color', this.value)">
                                        </div>
                                        <div class="form-text">Borders, Links</div>
                                    </div>
                                </div>
                                
                                <!-- Color Preview -->
                                <div class="mt-4 p-3 border rounded">
                                    <h6>Preview</h6>
                                    <div class="d-flex gap-3 mb-3">
                                        <button class="btn btn-primary">Primary Button</button>
                                        <button class="btn btn-secondary">Secondary Button</button>
                                        <button class="btn" style="background-color: <?php echo htmlspecialchars($customization['accent_color'] ?? '#059669'); ?>; color: white;">Accent Button</button>
                                    </div>
                                    <div class="p-3 mb-3 text-white" style="background-color: <?php echo htmlspecialchars($customization['primary_color'] ?? '#1e3a8a'); ?>">
                                        Navbar Preview
                                    </div>
                                    <div class="p-3" style="border-left: 4px solid <?php echo htmlspecialchars($customization['accent_color'] ?? '#059669'); ?>; background: #f8f9fa;">
                                        <p class="mb-0">This is a preview of how your accent color will be used for highlights and borders.</p>
                                    </div>
                                </div>
                                
                                <script>
                                // Update color preview in real-time
                                function updateColorPreview(type, color) {
                                    // Update the text input
                                    document.getElementById(type + '_text').value = color;
                                    
                                    // Update preview elements
                                    if (type === 'primary_color') {
                                        document.querySelector('.btn-primary').style.backgroundColor = color;
                                        document.querySelector('.p-3.text-white').style.backgroundColor = color;
                                    } else if (type === 'secondary_color') {
                                        document.querySelector('.btn-secondary').style.backgroundColor = color;
                                    } else if (type === 'accent_color') {
                                        document.querySelector('.btn:not(.btn-primary):not(.btn-secondary)').style.backgroundColor = color;
                                        document.querySelector('[style*="border-left"]').style.borderLeftColor = color;
                                    }
                                }
                                
                                // Initialize color pickers
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Sync color inputs with text inputs
                                    const colorInputs = document.querySelectorAll('input[type="color"]');
                                    colorInputs.forEach(input => {
                                        input.addEventListener('input', function() {
                                            const textId = this.id + '_text';
                                            if (document.getElementById(textId)) {
                                                document.getElementById(textId).value = this.value;
                                            }
                                        });
                                    });
                                    
                                    // Sync text inputs with color inputs
                                    const textInputs = document.querySelectorAll('input[type="text"][id$="_text"]');
                                    textInputs.forEach(input => {
                                        input.addEventListener('change', function() {
                                            const colorId = this.id.replace('_text', '');
                                            if (document.getElementById(colorId)) {
                                                document.getElementById(colorId).value = this.value;
                                                updateColorPreview(colorId, this.value);
                                            }
                                        });
                                    });
                                });
                                </script>
                            
                            <div class="mb-3">
                                <h6 class="border-bottom pb-2 mb-3">Marquee Settings</h6>
                                
                                <!-- Marquee Speed Control -->
                                <div class="mb-3">
                                    <label for="marquee_speed" class="form-label">Marquee Speed</label>
                                    <div class="d-flex align-items-center">
                                        <i class="fas fa-tachometer-alt-slow me-2"></i>
                                        <input type="range" class="form-range me-3" min="10" max="100" step="5" id="marquee_speed" name="marquee_speed" 
                                               value="<?php echo isset($customization['marquee_speed']) ? $customization['marquee_speed'] : '50'; ?>"
                                               oninput="document.getElementById('speedValue').textContent = this.value + '%'">
                                        <span class="badge bg-primary" id="speedValue">
                                            <?php echo isset($customization['marquee_speed']) ? $customization['marquee_speed'] . '%' : '50%'; ?>
                                        </span>
                                    </div>
                                    <div class="form-text">Adjust the scrolling speed of the alert marquee</div>
                                </div>
                                <input type="hidden" name="enable_scroll_animations" id="enable_scroll_animations" value="<?php echo htmlspecialchars($customization['enable_scroll_animations'] ?? '1'); ?>">
                                <div class="form-text" id="scrollAnimationsStatus">Loading...</div>
                                    <label class="form-check-label" for="enableButtonEffects">Button Effects</label>
                                    <input type="hidden" name="enable_button_effects" id="enable_button_effects" value="<?php echo htmlspecialchars($customization['enable_button_effects'] ?? '1'); ?>">
                                    <div class="form-text" id="buttonEffectsStatus">Loading...</div>
                                </div>
                                
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input animation-control" type="checkbox" role="switch" id="enableMenuAnimations" data-target="enable_menu_animations">
                                    <label class="form-check-label" for="enableMenuAnimations">Menu Animations</label>
                                    <input type="hidden" name="enable_menu_animations" id="enable_menu_animations" value="<?php echo htmlspecialchars($customization['enable_menu_animations'] ?? '1'); ?>">
                                    <div class="form-text" id="menuAnimationsStatus">Loading...</div>
                                </div>
                            </div>
                            
                            <button type="submit" name="update_colors" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Colors
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Site Information -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-info-circle"></i> Site Information</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Site Title</label>
                                <input type="text" class="form-control" name="site_title" 
                                       value="<?php echo htmlspecialchars($customization['site_title'] ?? 'IRCTC Rail Connect'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contact Phone</label>
                                <input type="text" class="form-control" name="contact_phone" 
                                       value="<?php echo htmlspecialchars($customization['contact_phone'] ?? '139'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contact Email</label>
                                <input type="email" class="form-control" name="contact_email" 
                                       value="<?php echo htmlspecialchars($customization['contact_email'] ?? 'care@irctc.co.in'); ?>" required>
                            </div>
                            
                            <button type="submit" name="update_site_info" class="btn btn-success">
                                <i class="fas fa-save"></i> Update Information
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <!-- Carousel Management -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-images"></i> Carousel Slides</h5>
                    </div>
                    <div class="card-body">
                        <!-- Add New Slide Form -->
                        <form method="POST" enctype="multipart/form-data" class="mb-4">
                            <h6 class="mb-3">Add New Slide</h6>
                            
                            <div class="mb-3">
                                <label class="form-label">Slide Title</label>
                                <input type="text" class="form-control" name="slide_title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control" name="slide_description" rows="3" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Choose Image Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_method" id="upload_file" value="upload" checked>
                                    <label class="form-check-label" for="upload_file">Upload Image File</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="image_method" id="use_url" value="url">
                                    <label class="form-check-label" for="use_url">Use Image URL</label>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="file_upload_section">
                                <label class="form-label">Upload Image</label>
                                <input type="file" class="form-control" name="slide_image_file" id="slide_image_file" accept="image/*" required>
                                <small class="text-muted">Recommended size: 1920x800px. Max size: 5MB. Allowed formats: JPG, JPEG, PNG, GIF</small>
                                <div class="mt-2">
                                    <img id="image_preview" src="#" alt="Preview" style="max-width: 100%; max-height: 200px; display: none;" class="img-thumbnail">
                                </div>
                            </div>
                            
                            <div class="mb-3" id="url_input_section" style="display: none;">
                                <label class="form-label">Image URL</label>
                                <input type="url" class="form-control" name="slide_image_url" 
                                       placeholder="https://example.com/image.jpg">
                            </div>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="form-label">Button Text</label>
                                    <input type="text" class="form-control" name="slide_button_text" 
                                           placeholder="Learn More">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" name="sort_order" 
                                           value="<?php echo count($carousel_slides) + 1; ?>" min="1">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Button Link</label>
                                <input type="url" class="form-control" name="slide_button_link" 
                                       placeholder="https://example.com">
                            </div>
                            
                            <button type="submit" name="add_carousel_slide" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Slide
                            </button>
                        </form>

                        <!-- Existing Slides -->
                        <h4 class="mt-4">Current Carousel Slides</h4>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Preview</th>
                                        <th>Title</th>
                                        <th>Subtitle</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($carousel_slides as $slide): 
                                        $imageUrl = !empty($slide['image_url']) ? htmlspecialchars($slide['image_url']) : 'https://via.placeholder.com/200x80/666666/ffffff?text=No+Image';
                                        $isActive = $slide['is_active'];
                                    ?>
                                    <tr>
                                        <td style="width: 200px;">
                                            <div class="carousel-thumbnail" style="width: 100%; height: 80px; background-image: url('<?php echo $imageUrl; ?>'); background-size: cover; background-position: center; border-radius: 4px;"></div>
                                        </td>
                                        <td><?php echo htmlspecialchars($slide['title']); ?></td>
                                        <td><?php echo htmlspecialchars($slide['description']); ?></td>
                                        <td>
                                            <span id="status-badge-<?php echo $slide['id']; ?>" class="badge bg-<?php echo $isActive ? 'success' : 'secondary'; ?>">
                                                <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-<?php echo $isActive ? 'primary' : 'secondary'; ?> toggle-slide" 
                                                    data-id="<?php echo $slide['id']; ?>" 
                                                    data-status="<?php echo $isActive ? '1' : '0'; ?>">
                                                <i class="fas fa-<?php echo $isActive ? 'eye-slash' : 'eye'; ?>"></i> <?php echo $isActive ? 'Hide' : 'Show'; ?>
                                            </button>
                                            <button class="btn btn-sm btn-danger delete-slide" data-id="<?php echo $slide['id']; ?>">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Live Preview Section -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-eye"></i> Live Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="preview-section">
                            <h6>Navbar Preview</h6>
                            <div class="navbar-preview" style="--primary-color: <?php echo $customization['primary_color'] ?? '#1e3a8a'; ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong><?php echo htmlspecialchars($customization['site_title'] ?? 'IRCTC Rail Connect'); ?></strong>
                                    </div>
                                    <div>
                                        <span class="badge" style="background-color: <?php echo $customization['secondary_color'] ?? '#f97316'; ?>">
                                            DAILY DEALS
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <h6 class="mt-3">Color Scheme</h6>
                            <div class="d-flex gap-3">
                                <div class="text-center">
                                    <div class="color-preview" style="background-color: <?php echo $customization['primary_color'] ?? '#1e3a8a'; ?>"></div>
                                    <small>Primary</small>
                                </div>
                                <div class="text-center">
                                    <div class="color-preview" style="background-color: <?php echo $customization['secondary_color'] ?? '#f97316'; ?>"></div>
                                    <small>Secondary</small>
                                </div>
                                <div class="text-center">
                                    <div class="color-preview" style="background-color: <?php echo $customization['accent_color'] ?? '#059669'; ?>"></div>
                                    <small>Accent</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Initialize alert ticker
        document.addEventListener('DOMContentLoaded', function() {
            const tickerTracks = document.querySelectorAll('.ticker-track');
            
            tickerTracks.forEach(track => {
                // Pause animation on hover
                track.addEventListener('mouseenter', function() {
                    this.style.animationPlayState = 'paused';
                });
                
                // Resume animation when mouse leaves
                track.addEventListener('mouseleave', function() {
                    this.style.animationPlayState = 'running';
                });
                
                // Clone ticker items only once for infinite scrolling effect
                if (!track.hasAttribute('data-cloned')) {
                    const tickerItems = track.querySelectorAll('.ticker-item');
                    const tickerItemsArray = Array.from(tickerItems);
                    
                    // Clone each item once and add to the end
                    tickerItemsArray.forEach(item => {
                        const clone = item.cloneNode(true);
                        track.appendChild(clone);
                    });
                    
                    // Mark as cloned to prevent duplicate cloning
                    track.setAttribute('data-cloned', 'true');
                    
                    // Restart animation to ensure smooth transition
                    track.style.animation = 'none';
                    track.offsetHeight; // Trigger reflow
                    track.style.animation = null;
                }
            });
        });

        // Toggle between file upload and URL input
        document.addEventListener('DOMContentLoaded', function() {
            // Handle image method toggle
            const uploadRadio = document.querySelector('input[value="upload"]');
            const urlRadio = document.querySelector('input[value="url"]');
            const fileUploadSection = document.getElementById('file_upload_section');
            const urlInputSection = document.getElementById('url_input_section');

            function toggleImageMethod() {
                if (uploadRadio.checked) {
                    fileUploadSection.style.display = 'block';
                    urlInputSection.style.display = 'none';
                    document.querySelector('input[name="slide_image_file"]').setAttribute('required', 'required');
                    document.querySelector('input[name="slide_image_url"]').removeAttribute('required');
                } else {
                    fileUploadSection.style.display = 'none';
                    urlInputSection.style.display = 'block';
                    document.querySelector('input[name="slide_image_file"]').removeAttribute('required');
                    document.querySelector('input[name="slide_image_url"]').setAttribute('required', 'required');
                }
            }

            uploadRadio.addEventListener('change', toggleImageMethod);
            urlRadio.addEventListener('change', toggleImageMethod);
            
            // Initialize on page load
            toggleImageMethod();
            
            // Image preview for file upload
            const imageInput = document.getElementById('slide_image_file');
            const imagePreview = document.getElementById('image_preview');
            
            if (imageInput && imagePreview) {
                imageInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreview.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Form validation
            const form = document.querySelector('form[action*="changing.php"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const fileInput = document.querySelector('input[name="slide_image_file"]');
                    const urlInput = document.querySelector('input[name="slide_image_url"]');
                    
                    if (uploadRadio.checked && !fileInput.files.length) {
                        e.preventDefault();
                        showAlert('Please select an image file to upload.', 'danger');
                        return false;
                    } else if (urlRadio.checked && !urlInput.value.trim()) {
                        e.preventDefault();
                        showAlert('Please enter an image URL.', 'danger');
                        return false;
                    }
                    
                    // Show loading state
                    const submitButton = this.querySelector('button[type="submit"]');
                    if (submitButton) {
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
                    }
                    
                    return true;
                });
            }
        });
        
        // Live color preview updates
        document.querySelectorAll('input[type="color"]').forEach(input => {
            // Initialize preview with current value
            const preview = input.parentElement.querySelector('.color-preview');
            if (preview) {
                preview.style.backgroundColor = input.value;
            }
            
            // Update preview on change
            input.addEventListener('input', function() {
                const target = this.getAttribute('data-target');
                document.documentElement.style.setProperty(target, this.value);
                
                // Update preview box
                const previewBox = this.closest('.color-picker-group').querySelector('.color-preview');
                if (previewBox) {
                    previewBox.style.backgroundColor = this.value;
                }
            });
        });
        
        // Initialize tooltips
            if (hiddenInput) {
                control.checked = hiddenInput.value === '1';
                const feedback = control.nextElementSibling;
                if (feedback && feedback.classList.contains('form-text')) {
                    feedback.textContent = control.checked ? 'Enabled' : 'Disabled';
                    feedback.style.color = control.checked ? 'green' : '#6c757d';
                }
            }
        });    // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Handle image preview for file upload
            const slideImageInput = document.getElementById('slide_image');
            const imagePreview = document.getElementById('image_preview');
            
            if (slideImageInput && imagePreview) {
                slideImageInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            imagePreview.src = event.target.result;
                            imagePreview.style.display = 'block';
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
            
            // Toggle active status for slides
            document.querySelectorAll('.slide-status-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    const slideId = this.dataset.id;
                    const isActive = this.dataset.active === '1';
                    const newStatus = isActive ? 0 : 1;
                    
                    // Send AJAX request to update status
                    fetch('changing.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `toggle_slide=1&id=${slideId}&status=${newStatus}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update UI
                            this.dataset.active = newStatus;
                            const icon = this.querySelector('i');
                            const badge = document.querySelector(`.status-badge-${slideId}`);
                            
                            if (newStatus == 1) {
                                icon.classList.remove('fa-toggle-off');
                                icon.classList.add('fa-toggle-on', 'text-success');
                                if (badge) {
                                    badge.classList.remove('bg-secondary');
                                    badge.classList.add('bg-success');
                                    badge.textContent = 'Active';
                                }
                            } else {
                                icon.classList.remove('fa-toggle-on', 'text-success');
                                icon.classList.add('fa-toggle-off');
                                if (badge) {
                                    badge.classList.remove('bg-success');
                                    badge.classList.add('bg-secondary');
                                    badge.textContent = 'Inactive';
                                }
                            }
                            
                            showAlert('Slide status updated successfully!', 'success');
                        } else {
                            throw new Error(data.message || 'Failed to update slide status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error: ' + error.message, 'danger');
                    });
                });
            });
            
            // Toggle slide active status
            document.addEventListener('click', function(e) {
                // Handle toggle slide
                if (e.target.closest('.toggle-slide')) {
                    const button = e.target.closest('.toggle-slide');
                    const slideId = button.getAttribute('data-id');
                    const currentStatus = button.getAttribute('data-status');
                    const newStatus = currentStatus === '1' ? '0' : '1';
                    
                    // Show loading state
                    const originalText = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                    button.disabled = true;
                    
                    // Send AJAX request to update status
                    fetch('changing.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `toggle_slide=1&id=${slideId}&status=${newStatus}`
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Update button and status badge
                            button.setAttribute('data-status', newStatus);
                            const statusBadge = document.getElementById(`status-badge-${slideId}`);
                            
                            if (newStatus === '1') {
                                button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide';
                                button.classList.remove('btn-outline-secondary');
                                button.classList.add('btn-outline-primary');
                                statusBadge.className = 'badge bg-success';
                                statusBadge.textContent = 'Active';
                            } else {
                                button.innerHTML = '<i class="fas fa-eye"></i> Show';
                                button.classList.remove('btn-outline-primary');
                                button.classList.add('btn-outline-secondary');
                                statusBadge.className = 'badge bg-secondary';
                                statusBadge.textContent = 'Inactive';
                            }
                            
                            // Show success message
                            showAlert('Slide status updated successfully!', 'success');
                        } else {
                            throw new Error(data.message || 'Failed to update slide status');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error: ' + (error.message || 'Failed to update slide status'), 'danger');
                        button.innerHTML = originalText;
                    })
                    .finally(() => {
                        button.disabled = false;
                    });
                }
                
                // Handle delete slide
                if (e.target.closest('.delete-slide')) {
                    e.preventDefault();
                    const button = e.target.closest('.delete-slide');
                    
                    if (confirm('Are you sure you want to delete this slide? This action cannot be undone.')) {
                        const slideId = button.getAttribute('data-id');
                        const row = button.closest('tr');
                        
                        // Show loading state
                        const originalContent = button.innerHTML;
                        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                        button.disabled = true;
                        
                        // Send AJAX request to delete
                        fetch('changing.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `delete_slide=1&id=${slideId}`
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                // Remove row from table with animation
                                row.style.opacity = '0';
                                setTimeout(() => {
                                    row.remove();
                                    showAlert('Slide deleted successfully!', 'success');
                                    
                                    // Update any carousels on the page
                                    const carousels = document.querySelectorAll('.carousel');
                                    carousels.forEach(carousel => {
                                        const bsCarousel = bootstrap.Carousel.getInstance(carousel);
                                        if (bsCarousel) {
                                            bsCarousel.dispose();
                                            new bootstrap.Carousel(carousel);
                                        }
                                    });
                                }, 300);
                            } else {
                                throw new Error(data.message || 'Failed to delete slide');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showAlert('Error: ' + (error.message || 'Failed to delete slide'), 'danger');
                            button.innerHTML = originalContent;
                        })
                        .finally(() => {
                            button.disabled = false;
                        });
                    }
                }
            });
            
            // Show alert message
            function showAlert(message, type = 'info') {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type} alert-dismissible fade show mt-3`;
                alertDiv.role = 'alert';
                alertDiv.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                const container = document.querySelector('.container');
                container.insertBefore(alertDiv, container.firstChild);
                
                // Auto-dismiss after 5 seconds
                setTimeout(() => {
                    const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
                    if (alert) alert.close();
                }, 5000);
            }
        });
    </script>
</body>
</html>
