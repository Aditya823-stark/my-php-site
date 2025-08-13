<?php
// Fix database tables for IRCTC website
include('connect/db.php');

try {
    $db = (new connect())->myconnect();
    echo "<h2>Fixing IRCTC Database Tables...</h2>";
    
    // Check if carousel_slides table exists and its structure
    $check_table = "SHOW TABLES LIKE 'carousel_slides'";
    $result = mysqli_query($db, $check_table);
    
    if (mysqli_num_rows($result) > 0) {
        echo "<p>✅ carousel_slides table exists</p>";
        
        // Check table structure
        $describe = "DESCRIBE carousel_slides";
        $desc_result = mysqli_query($db, $describe);
        
        echo "<h3>Current table structure:</h3><ul>";
        $columns = [];
        while ($row = mysqli_fetch_assoc($desc_result)) {
            $columns[] = $row['Field'];
            echo "<li>{$row['Field']} - {$row['Type']}</li>";
        }
        echo "</ul>";
        
        // Check if image_url column exists
        if (!in_array('image_url', $columns)) {
            echo "<p>❌ image_url column missing. Adding it...</p>";
            $add_column = "ALTER TABLE carousel_slides ADD COLUMN image_url VARCHAR(500) AFTER description";
            if (mysqli_query($db, $add_column)) {
                echo "<p>✅ image_url column added successfully!</p>";
            } else {
                echo "<p>❌ Error adding image_url column: " . mysqli_error($db) . "</p>";
            }
        } else {
            echo "<p>✅ image_url column exists</p>";
        }
        
        // Check other required columns
        $required_columns = ['button_text', 'button_link', 'sort_order', 'is_active'];
        foreach ($required_columns as $col) {
            if (!in_array($col, $columns)) {
                echo "<p>❌ $col column missing. Adding it...</p>";
                $column_def = '';
                switch ($col) {
                    case 'button_text':
                        $column_def = "ADD COLUMN button_text VARCHAR(100)";
                        break;
                    case 'button_link':
                        $column_def = "ADD COLUMN button_link VARCHAR(500)";
                        break;
                    case 'sort_order':
                        $column_def = "ADD COLUMN sort_order INT DEFAULT 1";
                        break;
                    case 'is_active':
                        $column_def = "ADD COLUMN is_active BOOLEAN DEFAULT TRUE";
                        break;
                }
                
                $add_col_query = "ALTER TABLE carousel_slides $column_def";
                if (mysqli_query($db, $add_col_query)) {
                    echo "<p>✅ $col column added successfully!</p>";
                } else {
                    echo "<p>❌ Error adding $col column: " . mysqli_error($db) . "</p>";
                }
            } else {
                echo "<p>✅ $col column exists</p>";
            }
        }
        
    } else {
        echo "<p>❌ carousel_slides table doesn't exist. Creating it...</p>";
        
        // Create carousel_slides table with all required columns
        $create_table = "CREATE TABLE carousel_slides (
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
        
        if (mysqli_query($db, $create_table)) {
            echo "<p>✅ carousel_slides table created successfully!</p>";
        } else {
            echo "<p>❌ Error creating carousel_slides table: " . mysqli_error($db) . "</p>";
        }
    }
    
    // Check website_customization table
    $check_custom = "SHOW TABLES LIKE 'website_customization'";
    $custom_result = mysqli_query($db, $check_custom);
    
    if (mysqli_num_rows($custom_result) == 0) {
        echo "<p>❌ website_customization table doesn't exist. Creating it...</p>";
        
        $create_custom = "CREATE TABLE website_customization (
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
        
        if (mysqli_query($db, $create_custom)) {
            echo "<p>✅ website_customization table created successfully!</p>";
            
            // Insert default values
            $insert_default = "INSERT INTO website_customization (id, primary_color, secondary_color, accent_color, site_title, logo_url, contact_phone, contact_email) 
                              VALUES (1, '#1e3a8a', '#f97316', '#059669', 'IRCTC Rail Connect', 'IRCTC-logo1.png', '139', 'care@irctc.co.in')";
            
            if (mysqli_query($db, $insert_default)) {
                echo "<p>✅ Default customization settings inserted!</p>";
            } else {
                echo "<p>❌ Error inserting default settings: " . mysqli_error($db) . "</p>";
            }
        } else {
            echo "<p>❌ Error creating website_customization table: " . mysqli_error($db) . "</p>";
        }
    } else {
        echo "<p>✅ website_customization table exists</p>";
    }
    
    // Insert some default carousel slides if table is empty
    $count_slides = "SELECT COUNT(*) as count FROM carousel_slides";
    $count_result = mysqli_query($db, $count_slides);
    $count_row = mysqli_fetch_assoc($count_result);
    
    if ($count_row['count'] == 0) {
        echo "<p>Adding default carousel slides...</p>";
        
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
        echo "<p>ℹ️ Carousel slides already exist ({$count_row['count']} slides found)</p>";
    }
    
    echo "<h3>✅ Database Fix Complete!</h3>";
    echo "<p><a href='changing.php' style='background: #1e3a8a; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>Go to Customization Panel</a></p>";
    echo "<p><a href='irctc_website.php' style='background: #059669; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>View Website</a></p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database connection error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IRCTC Database Fix</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            padding: 50px 20px;
        }
        .container {
            max-width: 800px;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin: 0 auto;
        }
        p { margin: 10px 0; }
        h2, h3 { color: #1e3a8a; }
        ul { background: #f8fafc; padding: 20px; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- PHP output will be displayed here -->
    </div>
</body>
</html>
