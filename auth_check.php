<?php
// Authentication middleware - Include this at the top of protected pages
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Store the current page URL for redirect after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit();
}

// Check session expiry (optional - 24 hours)
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > (24 * 60 * 60)) {
    session_destroy();
    header('Location: login.php?expired=1');
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
if (!isset($_SESSION['login_time'])) {
    $_SESSION['login_time'] = time();
}

// Function to check if user has specific role
function hasRole($required_role) {
    $roles = ['Staff' => 1, 'Manager' => 2, 'Admin' => 3, 'Super Admin' => 4];
    $user_role_level = $roles[$_SESSION['admin_role']] ?? 0;
    $required_role_level = $roles[$required_role] ?? 0;
    
    return $user_role_level >= $required_role_level;
}

// Function to get admin profile image path
function getProfileImagePath() {
    $image = $_SESSION['admin_profile_image'] ?? 'default-admin.png';
    $image_path = 'images/profiles/' . $image;
    
    // Check if custom image exists, otherwise use default
    if (!file_exists($image_path)) {
        return 'images/profiles/default-admin.png';
    }
    
    return $image_path;
}

// Function to get admin initials for avatar
function getAdminInitials() {
    $name = $_SESSION['admin_full_name'] ?? 'Admin User';
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        if (!empty($word)) {
            $initials .= strtoupper($word[0]);
        }
    }
    
    return substr($initials, 0, 2);
}
?>
