<?php
echo "<h2>🖼️ Profile Image Setup</h2>";

// Create profiles directory if it doesn't exist
$profiles_dir = 'images/profiles/';
if (!is_dir($profiles_dir)) {
    if (mkdir($profiles_dir, 0755, true)) {
        echo "✅ Created profiles directory: $profiles_dir<br>";
    } else {
        echo "❌ Failed to create profiles directory<br>";
    }
} else {
    echo "✅ Profiles directory exists: $profiles_dir<br>";
}

// Check if aditya.avif exists in the profiles directory
$image_path = $profiles_dir . 'aditya.avif';
if (file_exists($image_path)) {
    echo "✅ Profile image found: $image_path<br>";
    echo "📏 File size: " . formatBytes(filesize($image_path)) . "<br>";
} else {
    echo "❌ Profile image not found: $image_path<br>";
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>📋 Instructions to add your image:</strong><br>";
    echo "1. Copy your <strong>aditya.avif</strong> file<br>";
    echo "2. Paste it to: <code>admin/images/profiles/aditya.avif</code><br>";
    echo "3. Refresh this page to verify<br>";
    echo "</div>";
}

// Update database with profile image
include('connect/db.php');
$db = (new connect())->myconnect();

echo "<br><h3>🔄 Database Update:</h3>";

// Check if user exists and update profile image
$user_check = mysqli_query($db, "SELECT * FROM admin_users WHERE username = 'aditya'");
if (mysqli_num_rows($user_check) > 0) {
    $user = mysqli_fetch_assoc($user_check);
    
    // Check if profile_image column exists
    $profile_col_check = mysqli_query($db, "SHOW COLUMNS FROM admin_users LIKE 'profile_image'");
    if (mysqli_num_rows($profile_col_check) > 0) {
        // Update profile image
        $update_query = "UPDATE admin_users SET profile_image = 'aditya.avif' WHERE username = 'aditya'";
        if (mysqli_query($db, $update_query)) {
            echo "✅ Database updated with profile image 'aditya.avif'<br>";
        } else {
            echo "❌ Error updating database: " . mysqli_error($db) . "<br>";
        }
    } else {
        // Add profile_image column if it doesn't exist
        echo "🔧 Adding profile_image column to admin_users table...<br>";
        $add_column = "ALTER TABLE admin_users ADD COLUMN profile_image VARCHAR(255) DEFAULT 'default-admin.png'";
        if (mysqli_query($db, $add_column)) {
            echo "✅ Added profile_image column<br>";
            
            // Now update with the image
            $update_query = "UPDATE admin_users SET profile_image = 'aditya.avif' WHERE username = 'aditya'";
            if (mysqli_query($db, $update_query)) {
                echo "✅ Database updated with profile image 'aditya.avif'<br>";
            }
        } else {
            echo "❌ Error adding profile_image column: " . mysqli_error($db) . "<br>";
        }
    }
} else {
    echo "❌ User 'aditya' not found in database<br>";
    echo "Please run <a href='debug_admin.php'>debug_admin.php</a> first to create the user.<br>";
}

echo "<br><h3>🎯 Next Steps:</h3>";
echo "1. Make sure your <strong>aditya.avif</strong> image is in the profiles folder<br>";
echo "2. Run <a href='debug_admin.php'>debug_admin.php</a> to create/verify the admin user<br>";
echo "3. Login at <a href='login.php'>login.php</a> with username: <strong>aditya</strong><br>";
echo "4. Your profile image will appear in the admin dashboard!<br>";

echo "<br><div style='text-align: center; margin: 20px 0;'>";
echo "<a href='login.php' style='background: #007bff; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔐 Go to Login</a>";
echo "<a href='debug_admin.php' style='background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 5px;'>🔍 Debug Admin</a>";
echo "</div>";

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>
