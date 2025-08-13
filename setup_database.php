<?php
// Database configuration
$db_host = 'localhost';
$db_username = 'root';
$db_password = '';

// Create connection without selecting a database
$conn = new mysqli($db_host, $db_username, $db_password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS irctc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully or already exists<br>";
} else {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db('irctc');

// Create carousel_slides table
$sql = "CREATE TABLE IF NOT EXISTS carousel_slides (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subtitle VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    image_url VARCHAR(512) NOT NULL,
    button_text VARCHAR(100) DEFAULT NULL,
    button_link VARCHAR(512) DEFAULT NULL,
    sort_order INT(11) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table 'carousel_slides' created successfully or already exists<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Create website_customization table
$sql = "CREATE TABLE IF NOT EXISTS website_customization (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    primary_color VARCHAR(7) DEFAULT '#0d6efd',
    secondary_color VARCHAR(7) DEFAULT '#6c757d',
    accent_color VARCHAR(7) DEFAULT '#fd7e14',
    site_title VARCHAR(255) DEFAULT 'IRCTC',
    contact_phone VARCHAR(20) DEFAULT '',
    contact_email VARCHAR(255) DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table 'website_customization' created successfully or already exists<br>";
} else {
    die("Error creating table: " . $conn->error);
}

// Insert default customization if not exists
$sql = "INSERT IGNORE INTO website_customization (id) VALUES (1)";
if ($conn->query($sql) === TRUE) {
    echo "Default customization settings initialized<br>";
}

echo "<br>Database setup completed successfully!";

$conn->close();
?>

<p><a href="changing.php">Go to Admin Panel</a></p>
