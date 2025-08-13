<?php
include('connect/db.php');
include('connect/fun.php');

$db = (new connect())->myconnect();
$fun = new fun($db);

// Create tables if they don't exist
$create_customization_table = "CREATE TABLE IF NOT EXISTS website_customization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    primary_color VARCHAR(7) DEFAULT '#1e3a8a',
    secondary_color VARCHAR(7) DEFAULT '#f97316',
    accent_color VARCHAR(7) DEFAULT '#dc2626',
    logo_url VARCHAR(255) DEFAULT 'assets/images/irctc-logo.png',
    hero_image VARCHAR(255) DEFAULT 'assets/images/train-hero.jpg',
    site_title VARCHAR(100) DEFAULT 'Indian Railways - IRCTC',
    contact_phone VARCHAR(20) DEFAULT '139',
    contact_email VARCHAR(100) DEFAULT 'care@irctc.co.in',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_customization_table);

$create_carousel_table = "CREATE TABLE IF NOT EXISTS carousel_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    button_text VARCHAR(50) DEFAULT 'Learn More',
    button_link VARCHAR(255) DEFAULT '#booking',
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_carousel_table);

$create_notifications_table = "CREATE TABLE IF NOT EXISTS website_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    status ENUM('active', 'inactive') DEFAULT 'active',
    start_date DATE DEFAULT (CURRENT_DATE),
    end_date DATE,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_notifications_table);

$create_gallery_table = "CREATE TABLE IF NOT EXISTS gallery_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
mysqli_query($db, $create_gallery_table);

// Insert default data if tables are empty
$check_customization = mysqli_query($db, "SELECT COUNT(*) as count FROM website_customization");
if ($check_customization && mysqli_fetch_assoc($check_customization)['count'] == 0) {
    mysqli_query($db, "INSERT INTO website_customization (id) VALUES (1)");
}

$check_carousel = mysqli_query($db, "SELECT COUNT(*) as count FROM carousel_slides");
if (mysqli_fetch_assoc($check_carousel)['count'] == 0) {
    $default_slides = [
        ['Welcome to Indian Railways', 'Book your journey with ease and comfort', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'],
        ['Comfortable Travel Experience', 'Experience luxury and comfort in every journey', 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'],
        ['Safe & Secure Journey', 'Your safety is our priority', 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80']
    ];
    
    foreach ($default_slides as $index => $slide) {
        mysqli_query($db, "INSERT INTO carousel_slides (title, description, image, sort_order) VALUES ('{$slide[0]}', '{$slide[1]}', '{$slide[2]}', $index)");
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_customization'])) {
        $primary_color = mysqli_real_escape_string($db, $_POST['primary_color']);
        $secondary_color = mysqli_real_escape_string($db, $_POST['secondary_color']);
        $accent_color = mysqli_real_escape_string($db, $_POST['accent_color']);
        $logo_url = mysqli_real_escape_string($db, $_POST['logo_url']);
        $hero_image = mysqli_real_escape_string($db, $_POST['hero_image']);
        $site_title = mysqli_real_escape_string($db, $_POST['site_title']);
        $contact_phone = mysqli_real_escape_string($db, $_POST['contact_phone']);
        $contact_email = mysqli_real_escape_string($db, $_POST['contact_email']);
        
        $update_query = "UPDATE website_customization SET 
            primary_color = '$primary_color',
            secondary_color = '$secondary_color',
            accent_color = '$accent_color',
            logo_url = '$logo_url',
            hero_image = '$hero_image',
            site_title = '$site_title',
            contact_phone = '$contact_phone',
            contact_email = '$contact_email'
            WHERE id = 1";
        
        if (mysqli_query($db, $update_query)) {
            $success_message = "Website customization updated successfully!";
        } else {
            $error_message = "Error updating customization: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['add_carousel'])) {
        $title = mysqli_real_escape_string($db, $_POST['title']);
        $description = mysqli_real_escape_string($db, $_POST['description']);
        $image = mysqli_real_escape_string($db, $_POST['image']);
        $button_text = mysqli_real_escape_string($db, $_POST['button_text']);
        $button_link = mysqli_real_escape_string($db, $_POST['button_link']);
        $sort_order = (int)$_POST['sort_order'];
        
        $insert_query = "INSERT INTO carousel_slides (title, description, image, button_text, button_link, sort_order) 
                        VALUES ('$title', '$description', '$image', '$button_text', '$button_link', $sort_order)";
        
        if (mysqli_query($db, $insert_query)) {
            $success_message = "Carousel slide added successfully!";
        } else {
            $error_message = "Error adding carousel slide: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['add_notification'])) {
        $title = mysqli_real_escape_string($db, $_POST['notification_title']);
        $message = mysqli_real_escape_string($db, $_POST['notification_message']);
        $type = mysqli_real_escape_string($db, $_POST['notification_type']);
        $start_date = !empty($_POST['start_date']) ? mysqli_real_escape_string($db, $_POST['start_date']) : date('Y-m-d');
        $end_date = !empty($_POST['end_date']) ? "'" . mysqli_real_escape_string($db, $_POST['end_date']) . "'" : 'NULL';
        $created_by = 'Admin';
        
        $insert_query = "INSERT INTO website_notifications (title, message, type, start_date, end_date, created_by, created_at) 
                        VALUES ('$title', '$message', '$type', '$start_date', $end_date, '$created_by', CURDATE())";
        
        if (mysqli_query($db, $insert_query)) {
            $success_message = "Notification added successfully!";
        } else {
            $error_message = "Error adding notification: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['toggle_carousel_status'])) {
        $slide_id = (int)$_POST['slide_id'];
        $new_status = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
        
        mysqli_query($db, "UPDATE carousel_slides SET status = '$new_status' WHERE id = $slide_id");
        $success_message = "Carousel slide status updated!";
    }
    
    if (isset($_POST['toggle_notification_status'])) {
        $notification_id = (int)$_POST['notification_id'];
        $new_status = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
        
        mysqli_query($db, "UPDATE website_notifications SET status = '$new_status' WHERE id = $notification_id");
        $success_message = "Notification status updated!";
    }
    
    if (isset($_POST['delete_carousel'])) {
        $slide_id = (int)$_POST['slide_id'];
        
        if (mysqli_query($db, "DELETE FROM carousel_slides WHERE id = $slide_id")) {
            $success_message = "Carousel slide deleted successfully!";
        } else {
            $error_message = "Error deleting carousel slide: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['delete_notification'])) {
        $notification_id = (int)$_POST['notification_id'];
        
        if (mysqli_query($db, "DELETE FROM website_notifications WHERE id = $notification_id")) {
            $success_message = "Notification deleted successfully!";
        } else {
            $error_message = "Error deleting notification: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['add_gallery'])) {
        $title = mysqli_real_escape_string($db, $_POST['gallery_title']);
        $description = mysqli_real_escape_string($db, $_POST['gallery_description']);
        $image = mysqli_real_escape_string($db, $_POST['gallery_image']);
        $sort_order = (int)$_POST['gallery_sort_order'];
        
        $insert_query = "INSERT INTO gallery_images (title, description, image, sort_order) 
                        VALUES ('$title', '$description', '$image', $sort_order)";
        
        if (mysqli_query($db, $insert_query)) {
            $success_message = "Gallery image added successfully!";
        } else {
            $error_message = "Error adding gallery image: " . mysqli_error($db);
        }
    }
    
    if (isset($_POST['toggle_gallery_status'])) {
        $gallery_id = (int)$_POST['gallery_id'];
        $new_status = $_POST['current_status'] === 'active' ? 'inactive' : 'active';
        
        mysqli_query($db, "UPDATE gallery_images SET status = '$new_status' WHERE id = $gallery_id");
        $success_message = "Gallery image status updated!";
    }
    
    if (isset($_POST['delete_gallery'])) {
        $gallery_id = (int)$_POST['gallery_id'];
        
        if (mysqli_query($db, "DELETE FROM gallery_images WHERE id = $gallery_id")) {
            $success_message = "Gallery image deleted successfully!";
        } else {
            $error_message = "Error deleting gallery image: " . mysqli_error($db);
        }
    }
}

// Get current customization settings
$customization_query = "SELECT * FROM website_customization WHERE id = 1";
$customization_result = mysqli_query($db, $customization_query);
$customization = mysqli_fetch_assoc($customization_result);

// Get carousel slides
$carousel_query = "SELECT * FROM carousel_slides ORDER BY sort_order ASC";
$carousel_result = mysqli_query($db, $carousel_query);

// Get notifications
$notifications_query = "SELECT * FROM website_notifications ORDER BY created_at DESC";
$notifications_result = mysqli_query($db, $notifications_query);

// Get gallery images
$gallery_query = "SELECT * FROM gallery_images ORDER BY sort_order ASC";
$gallery_result = mysqli_query($db, $gallery_query);
?>

<?php include 'inlude/header.php'; ?>
<?php include 'inlude/nav.php'; ?>
<?php include 'inlude/sidebar.php'; ?>

<style>
    .color-preview {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: 2px solid #ddd;
        display: inline-block;
        vertical-align: middle;
        margin-left: 10px;
    }
    .carousel-preview {
        max-width: 200px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
    }
    .notification-badge {
        font-size: 0.8rem;
        padding: 4px 8px;
    }
    .gallery-preview {
        max-width: 150px;
        height: 100px;
        object-fit: cover;
        border-radius: 8px;
    }
</style>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h3 class="fw-bold mb-3">Website Customization</h3>
            <ul class="breadcrumbs mb-3">
                <li class="nav-home">
                    <a href="dashboard.php"><i class="icon-home"></i></a>
                </li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item">Operations</li>
                <li class="separator"><i class="icon-arrow-right"></i></li>
                <li class="nav-item">Website Customization</li>
            </ul>
        </div>
        
        <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Website Appearance Settings -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-palette"></i> Website Appearance
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="primary_color">Primary Color</label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" name="primary_color" id="primary_color" class="form-control" value="<?php echo $customization['primary_color']; ?>" style="width: 60px; height: 40px; padding: 2px;">
                                            <div class="color-preview" style="background-color: <?php echo $customization['primary_color']; ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="secondary_color">Secondary Color</label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" name="secondary_color" id="secondary_color" class="form-control" value="<?php echo $customization['secondary_color']; ?>" style="width: 60px; height: 40px; padding: 2px;">
                                            <div class="color-preview" style="background-color: <?php echo $customization['secondary_color']; ?>"></div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="accent_color">Accent Color</label>
                                        <div class="d-flex align-items-center">
                                            <input type="color" name="accent_color" id="accent_color" class="form-control" value="<?php echo $customization['accent_color']; ?>" style="width: 60px; height: 40px; padding: 2px;">
                                            <div class="color-preview" style="background-color: <?php echo $customization['accent_color']; ?>"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="site_title">Site Title</label>
                                        <input type="text" name="site_title" id="site_title" class="form-control" value="<?php echo htmlspecialchars($customization['site_title']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="logo_url">Logo URL</label>
                                        <input type="url" name="logo_url" id="logo_url" class="form-control" value="<?php echo htmlspecialchars($customization['logo_url']); ?>" placeholder="https://example.com/logo.png">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="contact_phone">Contact Phone</label>
                                        <input type="text" name="contact_phone" id="contact_phone" class="form-control" value="<?php echo htmlspecialchars($customization['contact_phone']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="contact_email">Contact Email</label>
                                        <input type="email" name="contact_email" id="contact_email" class="form-control" value="<?php echo htmlspecialchars($customization['contact_email']); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="hero_image">Hero Background Image URL</label>
                                        <input type="url" name="hero_image" id="hero_image" class="form-control" value="<?php echo htmlspecialchars($customization['hero_image']); ?>" placeholder="https://example.com/hero.jpg">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" name="update_customization" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Website Settings
                                </button>
                                <a href="index/index.php" target="_blank" class="btn btn-info ms-2">
                                    <i class="fas fa-external-link-alt"></i> Preview Website
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Carousel Management -->
        <div class="row mb-4">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-images"></i> Add Carousel Slide
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="title">Slide Title</label>
                                <input type="text" name="title" id="title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea name="description" id="description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="image">Image URL</label>
                                <input type="url" name="image" id="image" class="form-control" required placeholder="https://example.com/image.jpg">
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="button_text">Button Text</label>
                                        <input type="text" name="button_text" id="button_text" class="form-control" value="Learn More">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="sort_order">Sort Order</label>
                                        <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="button_link">Button Link</label>
                                <input type="text" name="button_link" id="button_link" class="form-control" value="#booking">
                            </div>
                            <button type="submit" name="add_carousel" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Slide
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Carousel Slides
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($carousel_result) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No carousel slides found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Preview</th>
                                            <th>Title</th>
                                            <th>Order</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($slide = mysqli_fetch_assoc($carousel_result)): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $slide['image']; ?>" alt="Preview" class="carousel-preview" onerror="this.src='https://via.placeholder.com/200x120?text=No+Image'">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($slide['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($slide['description'], 0, 50)); ?>...</small>
                                            </td>
                                            <td><?php echo $slide['sort_order']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $slide['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($slide['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $slide['status']; ?>">
                                                    <button type="submit" name="toggle_carousel_status" class="btn btn-sm btn-<?php echo $slide['status'] === 'active' ? 'warning' : 'success'; ?>" title="Toggle Status">
                                                        <i class="fas fa-<?php echo $slide['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this slide?')">
                                                    <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                                                    <button type="submit" name="delete_carousel" class="btn btn-sm btn-danger ms-1" title="Delete Slide">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Notification Management -->
        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-bell"></i> Add Notification
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="notification_title">Notification Title</label>
                                <input type="text" name="notification_title" id="notification_title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="notification_message">Message</label>
                                <textarea name="notification_message" id="notification_message" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="notification_type">Type</label>
                                        <select name="notification_type" id="notification_type" class="form-select">
                                            <option value="info">Info</option>
                                            <option value="success">Success</option>
                                            <option value="warning">Warning</option>
                                            <option value="danger">Important</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date (Optional)</label>
                                <input type="date" name="end_date" id="end_date" class="form-control">
                                <small class="form-text text-muted">Leave empty for permanent notification</small>
                            </div>
                            <button type="submit" name="add_notification" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add Notification
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Active Notifications
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($notifications_result) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No notifications found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Type</th>
                                            <th>Period</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($notification = mysqli_fetch_assoc($notifications_result)): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($notification['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($notification['message'], 0, 50)); ?>...</small>
                                            </td>
                                            <td>
                                                <span class="badge notification-badge badge-<?php echo $notification['type']; ?>">
                                                    <?php echo ucfirst($notification['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo date('M j', strtotime($notification['start_date'])); ?>
                                                    <?php if ($notification['end_date']): ?>
                                                        - <?php echo date('M j', strtotime($notification['end_date'])); ?>
                                                    <?php else: ?>
                                                        - Permanent
                                                    <?php endif; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $notification['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($notification['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $notification['status']; ?>">
                                                    <button type="submit" name="toggle_notification_status" class="btn btn-sm btn-<?php echo $notification['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                        <i class="fas fa-<?php echo $notification['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?')">
                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                    <button type="submit" name="delete_notification" class="btn btn-sm btn-danger ms-1" title="Delete Notification">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gallery Management -->
        <div class="row">
            <div class="col-md-5">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-image"></i> Add Gallery Image
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <div class="form-group">
                                <label for="gallery_title">Image Title</label>
                                <input type="text" name="gallery_title" id="gallery_title" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="gallery_description">Description</label>
                                <textarea name="gallery_description" id="gallery_description" class="form-control" rows="3"></textarea>
                            </div>
                            <div class="form-group">
                                <label for="gallery_image">Image URL</label>
                                <input type="url" name="gallery_image" id="gallery_image" class="form-control" required placeholder="https://example.com/image.jpg">
                            </div>
                            <div class="form-group">
                                <label for="gallery_sort_order">Sort Order</label>
                                <input type="number" name="gallery_sort_order" id="gallery_sort_order" class="form-control" value="0">
                            </div>
                            <button type="submit" name="add_gallery" class="btn btn-success">
                                <i class="fas fa-plus"></i> Add Image
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-7">
                <div class="card">
                    <div class="card-header">
                        <div class="card-title">
                            <i class="fas fa-list"></i> Gallery Images
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (mysqli_num_rows($gallery_result) === 0): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle"></i> No gallery images found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Preview</th>
                                            <th>Title</th>
                                            <th>Order</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($image = mysqli_fetch_assoc($gallery_result)): ?>
                                        <tr>
                                            <td>
                                                <img src="<?php echo $image['image']; ?>" alt="Preview" class="gallery-preview" onerror="this.src='https://via.placeholder.com/150x100?text=No+Image'">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($image['title']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars(substr($image['description'], 0, 50)); ?>...</small>
                                            </td>
                                            <td><?php echo $image['sort_order']; ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $image['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($image['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" style="display: inline;">
                                                    <input type="hidden" name="gallery_id" value="<?php echo $image['id']; ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo $image['status']; ?>">
                                                    <button type="submit" name="toggle_gallery_status" class="btn btn-sm btn-<?php echo $image['status'] === 'active' ? 'warning' : 'success'; ?>">
                                                        <i class="fas fa-<?php echo $image['status'] === 'active' ? 'pause' : 'play'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this image?')">
                                                    <input type="hidden" name="gallery_id" value="<?php echo $image['id']; ?>">
                                                    <button type="submit" name="delete_gallery" class="btn btn-sm btn-danger ms-1" title="Delete Image">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'inlude/footer.php'; ?>

<!-- JS Files -->
<script src="assets/js/core/jquery-3.7.1.min.js"></script>
<script src="assets/js/core/popper.min.js"></script>
<script src="assets/js/core/bootstrap.min.js"></script>
<script src="assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>
<script src="assets/js/kaiadmin.min.js"></script>

<script>
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
    
    // Color picker change handler
    $('input[type="color"]').on('change', function() {
        const preview = $(this).siblings('.color-preview');
        preview.css('background-color', $(this).val());
    });
</script>
