<?php
include('connect/db.php');
$db = (new connect())->myconnect();

// Create website_customization table
$website_customization_sql = "CREATE TABLE IF NOT EXISTS website_customization (
    id INT AUTO_INCREMENT PRIMARY KEY,
    primary_color VARCHAR(7) DEFAULT '#1e3a8a',
    secondary_color VARCHAR(7) DEFAULT '#f97316',
    accent_color VARCHAR(7) DEFAULT '#dc2626',
    logo_url VARCHAR(255) DEFAULT 'assets/images/irctc-logo.png',
    hero_image VARCHAR(255) DEFAULT 'assets/images/train-hero.jpg',
    site_title VARCHAR(100) DEFAULT 'Indian Railways - IRCTC',
    contact_phone VARCHAR(20) DEFAULT '139',
    contact_email VARCHAR(100) DEFAULT 'care@irctc.co.in',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Create carousel_slides table
$carousel_slides_sql = "CREATE TABLE IF NOT EXISTS carousel_slides (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255) NOT NULL,
    button_text VARCHAR(50) DEFAULT 'Learn More',
    button_link VARCHAR(255) DEFAULT '#',
    status ENUM('active', 'inactive') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

// Execute table creation
if (mysqli_query($db, $website_customization_sql)) {
    echo "✅ Website customization table created successfully<br>";
} else {
    echo "❌ Error creating website_customization table: " . mysqli_error($db) . "<br>";
}

if (mysqli_query($db, $carousel_slides_sql)) {
    echo "✅ Carousel slides table created successfully<br>";
} else {
    echo "❌ Error creating carousel_slides table: " . mysqli_error($db) . "<br>";
}

// Insert default customization settings
$default_customization = "INSERT IGNORE INTO website_customization (id, primary_color, secondary_color, accent_color, logo_url, hero_image, site_title, contact_phone, contact_email) 
VALUES (1, '#1e3a8a', '#f97316', '#dc2626', 'assets/images/irctc-logo.png', 'assets/images/train-hero.jpg', 'Indian Railways - IRCTC', '139', 'care@irctc.co.in')";

if (mysqli_query($db, $default_customization)) {
    echo "✅ Default customization settings inserted<br>";
} else {
    echo "❌ Error inserting default customization: " . mysqli_error($db) . "<br>";
}

// Insert default carousel slides
$default_slides = [
    ['Welcome to Indian Railways', 'Book your journey with ease and comfort', 'assets/images/train1.jpg', 'Book Now', '#booking', 1],
    ['Comfortable Travel Experience', 'Experience luxury and comfort in every journey', 'assets/images/train2.jpg', 'Explore', '#features', 2],
    ['Safe & Secure Journey', 'Your safety is our top priority', 'assets/images/train3.jpg', 'Learn More', '#features', 3]
];

foreach ($default_slides as $slide) {
    $slide_sql = "INSERT IGNORE INTO carousel_slides (title, description, image, button_text, button_link, sort_order) 
                  VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($db, $slide_sql);
    mysqli_stmt_bind_param($stmt, "sssssi", $slide[0], $slide[1], $slide[2], $slide[3], $slide[4], $slide[5]);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "✅ Default carousel slide '{$slide[0]}' inserted<br>";
    } else {
        echo "❌ Error inserting carousel slide: " . mysqli_error($db) . "<br>";
    }
    mysqli_stmt_close($stmt);
}

echo "<br><h3>🎉 Database setup completed successfully!</h3>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ul>";
echo "<li>Visit <a href='admin_customization.php'>Website Customization Panel</a> to manage colors and images</li>";
echo "<li>Visit <a href='carousel_management.php'>Carousel Management</a> to manage slides</li>";
echo "<li>Visit <a href='index/index.php'>Frontend Website</a> to see the IRCTC-themed site</li>";
echo "</ul>";

mysqli_close($db);
?>
