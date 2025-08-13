<?php
// Setup script for admin users table
include('connect/db.php');

$db = (new connect())->myconnect();

if (!$db) {
    die('Database connection failed: ' . mysqli_connect_error());
}

// Create admin_users table if it doesn't exist
$create_table_sql = "
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role VARCHAR(20) DEFAULT 'admin',
    profile_image VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($db, $create_table_sql) === TRUE) {
    echo "Admin users table created successfully<br>";
} else {
    echo "Error creating table: " . mysqli_error($db) . "<br>";
}

// Check if default admin exists
$check_admin = mysqli_query($db, "SELECT id FROM admin_users WHERE username = 'admin'");

if (mysqli_num_rows($check_admin) == 0) {
    // Create default admin user
    $default_username = 'admin';
    $default_password = 'admin123'; // Change this to a secure password
    $hashed_password = password_hash($default_password, PASSWORD_DEFAULT);
    
    $stmt = mysqli_prepare($db, "INSERT INTO admin_users (username, password, email, full_name, role, is_active) VALUES (?, ?, ?, ?, ?, ?)");
    $email = 'admin@railway.com';
    $full_name = 'System Administrator';
    $role = 'admin';
    $is_active = 1;
    
    mysqli_stmt_bind_param($stmt, "sssssi", $default_username, $hashed_password, $email, $full_name, $role, $is_active);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "Default admin user created successfully<br>";
        echo "Username: admin<br>";
        echo "Password: admin123<br>";
        echo "<strong>Please change the default password after first login!</strong><br>";
    } else {
        echo "Error creating admin user: " . mysqli_error($db) . "<br>";
    }
    mysqli_stmt_close($stmt);
} else {
    echo "Admin user already exists<br>";
}

mysqli_close($db);
echo "<br><a href='login.php'>Go to Login Page</a>";
?>
