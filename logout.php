<?php
session_start();
include('connect/db.php');

$db = (new connect())->myconnect();

// Mark session as inactive in database
if (isset($_SESSION['admin_id'])) {
    $session_id = session_id();
    mysqli_query($db, "UPDATE admin_sessions SET is_active = FALSE WHERE session_id = '$session_id'");
}

// Clear remember me cookie
if (isset($_COOKIE['remember_admin'])) {
    setcookie('remember_admin', '', time() - 3600, '/');
}

// Store admin name for goodbye message
$admin_name = $_SESSION['admin_full_name'] ?? 'Admin';

// Destroy session
session_destroy();

// Start new session for flash message
session_start();
$_SESSION['logout_message'] = "Goodbye, $admin_name! You have been successfully logged out.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <title>Logged Out - Railway Admin</title>
    <meta content='width=device-width, initial-scale=1.0, shrink-to-fit=no' name='viewport' />
    <link rel="icon" href="../assets/img/kaiadmin/favicon.ico" type="image/x-icon"/>
    
    <!-- Fonts and icons -->
    <script src="../assets/js/plugin/webfont/webfont.min.js"></script>
    <script>
        WebFont.load({
            google: {"families":["Public Sans:300,400,500,600,700"]},
            custom: {"families":["Font Awesome 5 Solid", "Font Awesome 5 Regular", "Font Awesome 5 Brands", "simple-line-icons"], urls: ['../assets/css/fonts.min.css']},
            active: function() {
                sessionStorage.fonts = true;
            }
        });
    </script>

    <!-- CSS Files -->
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="../assets/css/plugins.min.css">
    <link rel="stylesheet" href="../assets/css/kaiadmin.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Public Sans', sans-serif;
        }
        
        .logout-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            overflow: hidden;
            max-width: 400px;
            width: 100%;
            margin: 20px;
            text-align: center;
        }
        
        .logout-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 20px;
        }
        
        .logout-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.9;
        }
        
        .logout-body {
            padding: 40px 30px;
        }
        
        .btn-login-again {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 10px;
        }
        
        .btn-login-again:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .btn-home {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 25px;
            font-size: 16px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            margin: 10px;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(108, 117, 125, 0.3);
            color: white;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="logout-container">
        <div class="logout-header">
            <div class="logout-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h2>Successfully Logged Out</h2>
            <p>Thank you for using Railway Admin System</p>
        </div>
        
        <div class="logout-body">
            <h4>Goodbye, <?= htmlspecialchars($admin_name) ?>!</h4>
            <p class="text-muted mb-4">You have been safely logged out of the system. Your session has been terminated and all data has been secured.</p>
            
            <div class="mt-4">
                <a href="login.php" class="btn-login-again">
                    <i class="fas fa-sign-in-alt"></i> Login Again
                </a>
                <a href="../index.php" class="btn-home">
                    <i class="fas fa-home"></i> Go to Homepage
                </a>
            </div>
            
            <div class="mt-4">
                <small class="text-muted">
                    <i class="fas fa-shield-alt"></i> Your session was securely terminated at <?= date('Y-m-d H:i:s') ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Auto redirect to login after 10 seconds -->
    <script>
        let countdown = 10;
        const redirectTimer = setInterval(() => {
            countdown--;
            if (countdown <= 0) {
                window.location.href = 'login.php';
                clearInterval(redirectTimer);
            }
        }, 1000);
        
        // Show countdown in console
        console.log('Redirecting to login page in 10 seconds...');
    </script>
</body>
</html>
