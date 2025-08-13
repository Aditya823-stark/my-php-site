<?php
include('connect/db.php');

$db = (new connect())->myconnect();

echo "<h2>🔍 Admin User Debug Information</h2>";

// Check if admin_users table exists
$table_check = mysqli_query($db, "SHOW TABLES LIKE 'admin_users'");
if (mysqli_num_rows($table_check) == 0) {
    echo "❌ <strong>admin_users table does not exist!</strong><br>";
    echo "Please run <a href='create_admin_table.php'>create_admin_table.php</a> first.<br>";
    exit;
} else {
    echo "✅ admin_users table exists<br><br>";
}

// Check table structure
echo "<h3>📋 Table Structure:</h3>";
$columns = mysqli_query($db, "DESCRIBE admin_users");
while ($column = mysqli_fetch_assoc($columns)) {
    echo "- {$column['Field']} ({$column['Type']})<br>";
}
echo "<br>";

// Check if aditya user exists
echo "<h3>👤 User Check:</h3>";
$user_check = mysqli_query($db, "SELECT * FROM admin_users WHERE username = 'aditya'");

if (mysqli_num_rows($user_check) == 0) {
    echo "❌ <strong>User 'aditya' does not exist!</strong><br>";
    echo "Creating user now...<br><br>";
    
    // Create the user (matching existing table structure) with profile image
    $admin_password = password_hash('Aditya@2299', PASSWORD_DEFAULT);
    
    // Check if profile_image column exists
    $profile_col_check = mysqli_query($db, "SHOW COLUMNS FROM admin_users LIKE 'profile_image'");
    if (mysqli_num_rows($profile_col_check) > 0) {
        $insert_query = "INSERT INTO admin_users (username, email, password, full_name, role, is_active, profile_image) VALUES 
            ('aditya', 'aditya@railway.com', '$admin_password', 'Aditya Railway Administrator', 'Super Admin', 1, 'aditya.avif')";
    } else {
        $insert_query = "INSERT INTO admin_users (username, email, password, full_name, role, is_active) VALUES 
            ('aditya', 'aditya@railway.com', '$admin_password', 'Aditya Railway Administrator', 'Super Admin', 1)";
    }
    
    if (mysqli_query($db, $insert_query)) {
        echo "✅ <strong>User 'aditya' created successfully with profile image!</strong><br>";
    } else {
        echo "❌ Error creating user: " . mysqli_error($db) . "<br>";
    }
} else {
    echo "✅ User 'aditya' exists<br>";
    $user = mysqli_fetch_assoc($user_check);
    echo "<strong>Details:</strong><br>";
    echo "- ID: {$user['id']}<br>";
    echo "- Username: {$user['username']}<br>";
    echo "- Email: {$user['email']}<br>";
    echo "- Full Name: {$user['full_name']}<br>";
    echo "- Role: {$user['role']}<br>";
    echo "- Status: " . (isset($user['is_active']) ? ($user['is_active'] ? 'Active' : 'Inactive') : (isset($user['status']) ? $user['status'] : 'No status column')) . "<br>";
    echo "- Profile Image: " . (isset($user['profile_image']) ? $user['profile_image'] : 'No profile image column') . "<br>";
    echo "- Password Hash: " . substr($user['password'], 0, 20) . "...<br>";
    
    // Update profile image if it's missing or different
    if (isset($user['profile_image']) && $user['profile_image'] !== 'aditya.avif') {
        echo "<br>🖼️ Updating profile image to 'aditya.avif'...<br>";
        $update_image = "UPDATE admin_users SET profile_image = 'aditya.avif' WHERE username = 'aditya'";
        if (mysqli_query($db, $update_image)) {
            echo "✅ Profile image updated successfully!<br>";
        } else {
            echo "❌ Error updating profile image: " . mysqli_error($db) . "<br>";
        }
    }
}

echo "<br><h3>🔐 Password Test:</h3>";
if (isset($user)) {
    $test_password = 'Aditya@2299';
    if (password_verify($test_password, $user['password'])) {
        echo "✅ <strong>Password 'Aditya@2299' is CORRECT!</strong><br>";
    } else {
        echo "❌ <strong>Password 'Aditya@2299' is INCORRECT!</strong><br>";
        echo "Updating password now...<br>";
        
        $new_hash = password_hash($test_password, PASSWORD_DEFAULT);
        $update_query = "UPDATE admin_users SET password = '$new_hash' WHERE username = 'aditya'";
        
        if (mysqli_query($db, $update_query)) {
            echo "✅ Password updated successfully!<br>";
        } else {
            echo "❌ Error updating password: " . mysqli_error($db) . "<br>";
        }
    }
}

echo "<br><h3>🚀 Next Steps:</h3>";
echo "1. If everything looks good above, try logging in again<br>";
echo "2. Use username: <strong>aditya</strong><br>";
echo "3. Use password: <strong>Aditya@2299</strong><br>";
echo "<br><a href='login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 Try Login Again</a>";
?>
