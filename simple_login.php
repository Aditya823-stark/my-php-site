<?php
session_start();
include('connect/db.php');

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$db = (new connect())->myconnect();
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = mysqli_real_escape_string($db, $_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $query = "SELECT * FROM admin_users WHERE (username = '$username' OR email = '$username') AND is_active = 1";
        $result = mysqli_query($db, $query);
        
        if (mysqli_num_rows($result) == 1) {
            $admin = mysqli_fetch_assoc($result);
            
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_profile_image'] = $admin['profile_image'] ?? 'default-admin.png';
                
                header('Location: index.php');
                exit();
            } else {
                $error_message = 'Invalid password.';
            }
        } else {
            $error_message = 'Invalid username or password.';
        }
    }
}
?>

<!-- Note :- if the code is not run just open simple_login.php into your browser 
 and scroll on white screen using mouse and wait for 3 seconds. You can 
 give the specific size to div for solve this problem or correct if needed -->

<!-- This form was created for Railway Admin System. You can design more forms 
 like this and embed them directly into your website! -->

<!-- Railway Admin Interactive Login Form with Character Animation -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway Admin Login</title>

    <style>
    body {
      margin: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #f0f0f0;
    }

    div{
        height: 200px;
    }
  </style>

</head>
<body>

 <!-- Railway Admin Login Embed -->
<div class="visme_d" 
    data-title="Railway Admin Login Form" 
    data-url="railway-admin-login?fullPage=true" 
    data-domain="forms" 
    data-full-page="true" 
    data-min-height="100vh" 
    data-form-id="133190">
</div>

<script>
// Simple Railway Admin Login Script
function initRailwayLogin() {
    console.log('Railway Admin Login Initialized');
}

// Initialize on load
window.addEventListener('load', initRailwayLogin);
</script>

</body>
</html>
