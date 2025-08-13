<?php
// Database setup for IRCTC website customization
include('connect/db.php');

try {
    $db = (new connect())->myconnect();
    echo "<h2>Setting up IRCTC Website Tables...</h2>";
    
    // Create website_customization table
    $create_customization_table = "CREATE TABLE IF NOT EXISTS website_customization (
        id INT PRIMARY KEY AUTO_INCREMENT,
        primary_color VARCHAR(7) DEFAULT '#1e3a8a',
        secondary_color VARCHAR(7) DEFAULT '#f97316',
        accent_color VARCHAR(7) DEFAULT '#059669',
        site_title VARCHAR(255) DEFAULT 'IRCTC Rail Connect',
        logo_url VARCHAR(500) DEFAULT 'IRCTC-logo1.png',
        hero_image_url VARCHAR(500) DEFAULT 'assets/images/slider/slider1.jpg',
        contact_phone VARCHAR(20) DEFAULT '139',
        contact_email VARCHAR(100) DEFAULT 'care@irctc.co.in',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($db, $create_customization_table)) {
        echo "<p>✅ Website customization table created successfully!</p>";
    } else {
        echo "<p>❌ Error creating website customization table: " . mysqli_error($db) . "</p>";
    }
    
    // Create carousel_slides table
    $create_carousel_table = "CREATE TABLE IF NOT EXISTS carousel_slides (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        image_url VARCHAR(500),
        button_text VARCHAR(100),
        button_link VARCHAR(500),
        sort_order INT DEFAULT 1,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if (mysqli_query($db, $create_carousel_table)) {
        echo "<p>✅ Carousel slides table created successfully!</p>";
    } else {
        echo "<p>❌ Error creating carousel slides table: " . mysqli_error($db) . "</p>";
    }
    
    // Insert default customization settings
    $check_customization = "SELECT COUNT(*) as count FROM website_customization WHERE id = 1";
    $result = mysqli_query($db, $check_customization);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        $insert_default = "INSERT INTO website_customization (id, primary_color, secondary_color, accent_color, site_title, logo_url, contact_phone, contact_email) 
                          VALUES (1, '#1e3a8a', '#f97316', '#059669', 'IRCTC Rail Connect', 'IRCTC-logo1.png', '139', 'care@irctc.co.in')";
        
        if (mysqli_query($db, $insert_default)) {
            echo "<p>✅ Default customization settings inserted!</p>";
        } else {
            echo "<p>❌ Error inserting default settings: " . mysqli_error($db) . "</p>";
        }
    } else {
        echo "<p>ℹ️ Default customization settings already exist.</p>";
    }
    
    // Insert default carousel slides if none exist
    $check_slides = "SELECT COUNT(*) as count FROM carousel_slides";
    $result = mysqli_query($db, $check_slides);
    $row = mysqli_fetch_assoc($result);
    
    if ($row['count'] == 0) {
        $default_slides = [
            [
                'title' => 'Welcome to IRCTC',
                'description' => 'Book your train tickets online with ease and comfort',
                'image_url' => 'assets/images/train1.jpg',
                'button_text' => 'Book Now',
                'button_link' => '#booking',
                'sort_order' => 1
            ],
            [
                'title' => 'Comfortable Journey',
                'description' => 'Experience the best of Indian Railways with modern amenities',
                'image_url' => 'assets/images/train2.jpg',
                'button_text' => 'Explore',
                'button_link' => '#services',
                'sort_order' => 2
            ],
            [
                'title' => 'Holiday Packages',
                'description' => 'Discover amazing destinations with our special holiday packages',
                'image_url' => 'assets/images/holiday.jpg',
                'button_text' => 'View Packages',
                'button_link' => '#holidays',
                'sort_order' => 3
            ]
        ];
        
        foreach ($default_slides as $slide) {
            $insert_slide = "INSERT INTO carousel_slides (title, description, image_url, button_text, button_link, sort_order) 
                            VALUES ('{$slide['title']}', '{$slide['description']}', '{$slide['image_url']}', '{$slide['button_text']}', '{$slide['button_link']}', {$slide['sort_order']})";
            
            if (mysqli_query($db, $insert_slide)) {
                echo "<p>✅ Default slide '{$slide['title']}' inserted!</p>";
            } else {
                echo "<p>❌ Error inserting slide '{$slide['title']}': " . mysqli_error($db) . "</p>";
            }
        }
    } else {
        echo "<p>ℹ️ Carousel slides already exist.</p>";
    }
    
    echo "<h3>Setup Complete!</h3>";
    echo "<p><a href='changing.php' class='btn btn-primary'>Go to Website Customization</a></p>";
    echo "<p><a href='irctc_website.php' class='btn btn-success'>View Website</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRCTC Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            padding: 50px 0;
        }
        .container {
            max-width: 800px;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .btn {
            border-radius: 10px;
            padding: 12px 25px;
            font-weight: 500;
            margin: 10px 5px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3a8a, #1e40af);
            border: none;
        }
        .btn-success {
            background: linear-gradient(135deg, #059669, #047857);
            border: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- PHP output will be displayed here -->
    </div>
</body>
</html>
