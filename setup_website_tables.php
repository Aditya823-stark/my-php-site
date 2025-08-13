<?php
include('connect/db.php');

$db = (new connect())->myconnect();

echo "<h2>Setting up Website Customization Tables...</h2>";

// Create website_customization table
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

if (mysqli_query($db, $create_customization_table)) {
    echo "✅ website_customization table created successfully<br>";
} else {
    echo "❌ Error creating website_customization table: " . mysqli_error($db) . "<br>";
}

// Create carousel_slides table
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

if (mysqli_query($db, $create_carousel_table)) {
    echo "✅ carousel_slides table created successfully<br>";
} else {
    echo "❌ Error creating carousel_slides table: " . mysqli_error($db) . "<br>";
}

// Create website_notifications table
$create_notifications_table = "CREATE TABLE IF NOT EXISTS website_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'danger') DEFAULT 'info',
    status ENUM('active', 'inactive') DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    created_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($db, $create_notifications_table)) {
    echo "✅ website_notifications table created successfully<br>";
} else {
    echo "❌ Error creating website_notifications table: " . mysqli_error($db) . "<br>";
}

// Create gallery_images table
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

if (mysqli_query($db, $create_gallery_table)) {
    echo "✅ gallery_images table created successfully<br>";
} else {
    echo "❌ Error creating gallery_images table: " . mysqli_error($db) . "<br>";
}

// Insert default data if tables are empty
$check_customization = mysqli_query($db, "SELECT COUNT(*) as count FROM website_customization");
if (mysqli_fetch_assoc($check_customization)['count'] == 0) {
    if (mysqli_query($db, "INSERT INTO website_customization (id) VALUES (1)")) {
        echo "✅ Default website customization data inserted<br>";
    } else {
        echo "❌ Error inserting default customization data: " . mysqli_error($db) . "<br>";
    }
}

$check_carousel = mysqli_query($db, "SELECT COUNT(*) as count FROM carousel_slides");
if (mysqli_fetch_assoc($check_carousel)['count'] == 0) {
    $default_slides = [
        ['Welcome to Indian Railways', 'Book your journey with ease and comfort', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'],
        ['Comfortable Travel Experience', 'Experience luxury and comfort in every journey', 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'],
        ['Safe & Secure Journey', 'Your safety is our priority', 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80']
    ];
    
    foreach ($default_slides as $index => $slide) {
        $title = mysqli_real_escape_string($db, $slide[0]);
        $description = mysqli_real_escape_string($db, $slide[1]);
        $image = mysqli_real_escape_string($db, $slide[2]);
        
        if (mysqli_query($db, "INSERT INTO carousel_slides (title, description, image, sort_order) VALUES ('$title', '$description', '$image', $index)")) {
            echo "✅ Default carousel slide '$title' inserted<br>";
        } else {
            echo "❌ Error inserting carousel slide: " . mysqli_error($db) . "<br>";
        }
    }
}

$check_gallery = mysqli_query($db, "SELECT COUNT(*) as count FROM gallery_images");
if (mysqli_fetch_assoc($check_gallery)['count'] == 0) {
    $default_gallery_images = [
        ['Image 1', 'This is the first image in the gallery', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'],
        ['Image 2', 'This is the second image in the gallery', 'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'],
        ['Image 3', 'This is the third image in the gallery', 'https://images.unsplash.com/photo-1474487548417-781cb71495f3?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80']
    ];
    
    foreach ($default_gallery_images as $index => $image) {
        $title = mysqli_real_escape_string($db, $image[0]);
        $description = mysqli_real_escape_string($db, $image[1]);
        $image_url = mysqli_real_escape_string($db, $image[2]);
        
        if (mysqli_query($db, "INSERT INTO gallery_images (title, description, image, sort_order) VALUES ('$title', '$description', '$image_url', $index)")) {
            echo "✅ Default gallery image '$title' inserted<br>";
        } else {
            echo "❌ Error inserting gallery image: " . mysqli_error($db) . "<br>";
        }
    }
}

// Insert a sample notification
$check_notifications = mysqli_query($db, "SELECT COUNT(*) as count FROM website_notifications");
if (mysqli_fetch_assoc($check_notifications)['count'] == 0) {
    $sample_notification = "INSERT INTO website_notifications (title, message, type, start_date, created_by) 
                           VALUES ('Welcome to IRCTC', 'Experience seamless train booking with our enhanced platform!', 'info', CURDATE(), 'Admin')";
    
    if (mysqli_query($db, $sample_notification)) {
        echo "✅ Sample notification inserted<br>";
    } else {
        echo "❌ Error inserting sample notification: " . mysqli_error($db) . "<br>";
    }
}

echo "<br><h3>Setup Complete!</h3>";
echo "<p>You can now access:</p>";
echo "<ul>";
echo "<li><a href='website_customization.php'>Website Customization Panel</a></li>";
echo "<li><a href='index/index.php'>Frontend Website</a></li>";
echo "</ul>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 50px auto;
    padding: 20px;
    background: #f5f5f5;
}

h2, h3 {
    color: #1e3a8a;
}

ul {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

li {
    margin: 10px 0;
}

a {
    color: #f97316;
    text-decoration: none;
    font-weight: bold;
}

a:hover {
    text-decoration: underline;
}
</style>
