<?php
include('connect/db.php');

$db = (new connect())->myconnect();

// Create admin users table
$create_admin_users = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default-admin.png',
    role ENUM('Super Admin', 'Admin', 'Manager', 'Staff') DEFAULT 'Admin',
    status ENUM('Active', 'Inactive', 'Suspended') DEFAULT 'Active',
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (mysqli_query($db, $create_admin_users)) {
    echo "✅ Admin users table created successfully!<br>";
} else {
    echo "❌ Error creating admin users table: " . mysqli_error($db) . "<br>";
}

// Insert default admin user
$default_password = password_hash('admin123', PASSWORD_DEFAULT);
$insert_admin = "INSERT IGNORE INTO admin_users (username, email, password, full_name, role) VALUES 
    ('admin', 'admin@railway.com', '$default_password', 'Railway Administrator', 'Super Admin'),
    ('manager', 'manager@railway.com', '" . password_hash('manager123', PASSWORD_DEFAULT) . "', 'Railway Manager', 'Manager'),
    ('staff', 'staff@railway.com', '" . password_hash('staff123', PASSWORD_DEFAULT) . "', 'Railway Staff', 'Staff')";

if (mysqli_query($db, $insert_admin)) {
    echo "✅ Default admin users created successfully!<br>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🔑 Default Login Credentials:</h4>";
    echo "<strong>Super Admin:</strong> admin / admin123<br>";
    echo "<strong>Manager:</strong> manager / manager123<br>";
    echo "<strong>Staff:</strong> staff / staff123<br>";
    echo "</div>";
} else {
    echo "❌ Error creating default admin users: " . mysqli_error($db) . "<br>";
}

// Create admin sessions table for better session management
$create_admin_sessions = "CREATE TABLE IF NOT EXISTS admin_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    session_id VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
)";

if (mysqli_query($db, $create_admin_sessions)) {
    echo "✅ Admin sessions table created successfully!<br>";
} else {
    echo "❌ Error creating admin sessions table: " . mysqli_error($db) . "<br>";
}

echo "<br><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";
?>
