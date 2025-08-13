<?php
session_start();
include('connect/db.php');

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit();
}

$db = (new connect())->myconnect();
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = 'Please enter both username and password.';
    } else {
        $stmt = mysqli_prepare($db, "SELECT id, username, email, full_name, role, profile_image, password FROM admin_users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $admin = mysqli_fetch_assoc($result);
            if (password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_email'] = $admin['email'];
                $_SESSION['admin_full_name'] = $admin['full_name'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['admin_profile_image'] = $admin['profile_image'] ?: 'default-admin.png';

                header('Location: index.php');
                exit();
            } else {
                $error_message = 'Invalid password.';
            }
        } else {
            $error_message = 'Invalid username or password.';
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Railway Admin - Animated Portal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(45deg, #1e3c72, #2a5298, #667eea, #764ba2);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Railway Track */
        .railway-track {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 80px;
            background: repeating-linear-gradient(90deg, #8B4513 0px, #8B4513 10px, transparent 10px, transparent 30px);
            z-index: 0;
            opacity: 0.3;
        }

        .railway-track::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 0;
            width: 100%;
            height: 4px;
            background: #C0C0C0;
            box-shadow: 0 20px 0 #C0C0C0;
        }

        /* Animated Train */
        .train {
            position: absolute;
            bottom: 25px;
            font-size: 3rem;
            animation: trainMove 20s linear infinite;
            z-index: 1;
            filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
        }

        @keyframes trainMove {
            0% { left: -200px; }
            100% { left: calc(100% + 200px); }
        }

        /* Floating Robots */
        .floating-robot {
            position: absolute;
            font-size: 2rem;
            animation: robotFloat 8s ease-in-out infinite;
            z-index: 0;
            opacity: 0.7;
        }

        .robot1 { top: 10%; left: 5%; animation-delay: 0s; }
        .robot2 { top: 60%; right: 10%; animation-delay: 3s; }
        .robot3 { bottom: 30%; left: 15%; animation-delay: 6s; }

        @keyframes robotFloat {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.7; }
            25% { transform: translateY(-20px) rotate(5deg); opacity: 1; }
            50% { transform: translateY(-40px) rotate(-5deg); opacity: 0.8; }
            75% { transform: translateY(-20px) rotate(3deg); opacity: 1; }
        }

        /* Stars */
        .star {
            position: absolute;
            color: #FFD700;
            animation: starTwinkle 3s ease-in-out infinite;
            z-index: 0;
        }

        .star:nth-child(1) { top: 15%; left: 20%; animation-delay: 0s; }
        .star:nth-child(2) { top: 25%; right: 25%; animation-delay: 1s; }
        .star:nth-child(3) { bottom: 40%; left: 30%; animation-delay: 2s; }

        @keyframes starTwinkle {
            0%, 100% { opacity: 0.3; transform: scale(1) rotate(0deg); }
            50% { opacity: 1; transform: scale(1.3) rotate(180deg); }
        }

        /* Main Container */
        .visme_d {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(25px);
            border-radius: 25px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2), 0 0 0 1px rgba(255, 255, 255, 0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            position: relative;
            z-index: 10;
            animation: containerEntrance 1.2s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 550px;
        }

        @keyframes containerEntrance {
            0% { opacity: 0; transform: translateY(100px) scale(0.8); }
            50% { opacity: 0.8; transform: translateY(-10px) scale(1.05); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Header */
        .login-header {
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            background-size: 300% 300%;
            animation: headerGradient 8s ease infinite;
            padding: 50px 30px;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }

        @keyframes headerGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .logo-container {
            position: relative;
            margin-bottom: 20px;
            z-index: 2;
        }

        .main-logo {
            font-size: 4.5rem;
            animation: logoAnimation 4s ease-in-out infinite;
            filter: drop-shadow(0 6px 12px rgba(0,0,0,0.3));
        }

        @keyframes logoAnimation {
            0%, 100% { transform: translateY(0px) scale(1); }
            25% { transform: translateY(-8px) scale(1.05); }
            50% { transform: translateY(-12px) scale(1.1); }
            75% { transform: translateY(-6px) scale(1.03); }
        }

        .login-title {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 10px 0;
            text-shadow: 0 3px 6px rgba(0,0,0,0.2);
            animation: titleGlow 3s ease-in-out infinite alternate;
        }

        @keyframes titleGlow {
            0% { text-shadow: 0 3px 6px rgba(0,0,0,0.2); }
            100% { text-shadow: 0 3px 6px rgba(0,0,0,0.2), 0 0 20px rgba(255,255,255,0.3); }
        }

        /* Form */
        .login-form {
            padding: 45px 35px;
        }

        .alert {
            padding: 18px;
            border-radius: 15px;
            margin-bottom: 30px;
            font-weight: 600;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .alert-error {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            animation: errorShake 0.8s ease-in-out;
        }

        @keyframes errorShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }

        .form-group {
            margin-bottom: 30px;
            position: relative;
            animation: formGroupSlide 0.8s ease-out forwards;
            opacity: 0;
            transform: translateX(-50px);
        }

        .form-group:nth-child(1) { animation-delay: 0.2s; }
        .form-group:nth-child(2) { animation-delay: 0.4s; }
        .form-group:nth-child(3) { animation-delay: 0.6s; }

        @keyframes formGroupSlide {
            100% { opacity: 1; transform: translateX(0); }
        }

        .form-input {
            width: 100%;
            padding: 20px 25px 20px 55px;
            border: 3px solid #e1e8ed;
            border-radius: 15px;
            font-size: 16px;
            transition: all 0.4s ease;
            background: linear-gradient(135deg, #f8f9fa, #ffffff);
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: linear-gradient(135deg, #ffffff, #f0f8ff);
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
            transform: translateY(-3px) scale(1.02);
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #8e9aaf;
            font-size: 18px;
            transition: all 0.3s ease;
        }

        .form-group:focus-within .input-icon {
            color: #667eea;
            transform: translateY(-50%) scale(1.2);
            animation: iconPulse 0.6s ease;
        }

        @keyframes iconPulse {
            0%, 100% { transform: translateY(-50%) scale(1.2); }
            50% { transform: translateY(-50%) scale(1.4); }
        }

        .login-btn {
            width: 100%;
            padding: 20px;
            background: linear-gradient(135deg, #667eea, #764ba2, #f093fb);
            background-size: 200% 200%;
            border: none;
            border-radius: 15px;
            color: white;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            animation: buttonGradient 3s ease infinite;
        }

        @keyframes buttonGradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-btn:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.4);
        }

        .loading-spinner {
            display: none;
            width: 22px;
            height: 22px;
            border: 3px solid rgba(255,255,255,0.3);
            border-top: 3px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% { transform: rotate(360deg); }
        }

        /* Responsive */
        @media (max-width: 480px) {
            .visme_d { margin: 10px; max-width: calc(100% - 20px); }
            .login-header { padding: 40px 25px; }
            .login-form { padding: 35px 25px; }
            .main-logo { font-size: 3.5rem; }
            .login-title { font-size: 1.6rem; }
        }
    </style>
</head>
<body>
    <!-- Animated Background Elements -->
    <div class="railway-track"></div>
    <div class="train">🚂💨</div>
    
    <div class="floating-robot robot1">🤖</div>
    <div class="floating-robot robot2">🦾</div>
    <div class="floating-robot robot3">🛸</div>
    
    <div class="star">⭐</div>
    <div class="star">✨</div>
    <div class="star">🌟</div>

    <!-- Main Login Container -->
    <div class="visme_d" 
         data-title="Railway Admin Animated Login" 
         data-url="railway-admin-animated" 
         data-domain="forms" 
         data-full-page="true" 
         data-form-id="railway-animated-133190">
        
        <div class="login-header">
            <div class="logo-container">
                <div class="main-logo">🚂</div>
            </div>
            <h1 class="login-title">Railway Command Center</h1>
            <p class="login-subtitle">🤖 AI-Powered Admin Portal 🚀</p>
        </div>

        <div class="login-form">
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="adminLoginForm">
                <div class="form-group">
                    <i class="fas fa-user-astronaut input-icon"></i>
                    <input type="text" 
                           name="username" 
                           class="form-input" 
                           placeholder="🚀 Commander Username" 
                           required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="form-group">
                    <i class="fas fa-key input-icon"></i>
                    <input type="password" 
                           name="password" 
                           class="form-input" 
                           placeholder="🔐 Secret Access Code" 
                           required>
                </div>

                <div class="form-group">
                    <button type="submit" class="login-btn" id="loginBtn">
                        <div class="loading-spinner" id="loadingSpinner"></div>
                        <i class="fas fa-rocket" id="loginIcon"></i>
                        <span id="btnText">🚀 Launch Control Panel</span>
                    </button>
                </div>
            </form>

            <div style="text-align: center; margin-top: 25px; color: #8e9aaf;">
                <p>🤖 AI-Enhanced Security • 🛡️ Quantum Encryption</p>
                <p style="margin-top: 8px;">© 2024 Railway Command Center</p>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('adminLoginForm');
            const loginBtn = document.getElementById('loginBtn');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const loginIcon = document.getElementById('loginIcon');
            const btnText = document.getElementById('btnText');

            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => {
                        alert.style.display = 'none';
                    }, 300);
                }, 5000);
            });

            // Form submission with loading animation
            form.addEventListener('submit', function(e) {
                loginBtn.disabled = true;
                loadingSpinner.style.display = 'inline-block';
                loginIcon.style.display = 'none';
                btnText.textContent = '🚀 Launching...';
                loginBtn.style.background = 'linear-gradient(45deg, #667eea 0%, #764ba2 100%)';
            });

            // Keyboard shortcut: Ctrl+Enter to submit
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey && e.key === 'Enter') {
                    form.submit();
                }
            });

            // Input focus animations
            const inputs = document.querySelectorAll('.form-input');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'scale(1.02)';
                    this.parentElement.style.boxShadow = '0 8px 25px rgba(102, 126, 234, 0.3)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'scale(1)';
                    this.parentElement.style.boxShadow = '0 4px 15px rgba(0, 0, 0, 0.1)';
                });
            });

            // Add sparkle effect on container hover
            const container = document.querySelector('.visme_d');
            container.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 20px 60px rgba(102, 126, 234, 0.4), 0 0 30px rgba(255, 255, 255, 0.1)';
            });
            
            container.addEventListener('mouseleave', function() {
                this.style.boxShadow = '0 15px 40px rgba(0, 0, 0, 0.3)';
            });

            // Random twinkling for stars
            const stars = document.querySelectorAll('.star');
            stars.forEach((star, index) => {
                setInterval(() => {
                    star.style.opacity = Math.random() > 0.5 ? '1' : '0.3';
                    star.style.transform = `scale(${0.8 + Math.random() * 0.4})`;
                }, 1000 + index * 500);
            });

            console.log('🚂 Railway Command Center - Initialized');
            console.log('🤖 AI Systems: Online');
            console.log('🚀 Launch Sequence: Ready');
        });

        // Visme-style form enhancements
        (function() {
            // Add Visme-like tracking and behavior
            const vismeForm = document.querySelector('.visme_d');
            if (vismeForm) {
                vismeForm.setAttribute('data-visme-loaded', 'true');
                vismeForm.setAttribute('data-form-type', 'admin-login');
                vismeForm.setAttribute('data-security-level', 'high');
                vismeForm.setAttribute('data-version', '2.1.0');
                
                // Simulate form interaction tracking
                const trackInteraction = (event) => {
                    console.log('Visme Form Interaction:', event.type);
                };
                
                vismeForm.addEventListener('click', trackInteraction);
                vismeForm.addEventListener('focus', trackInteraction, true);
                
                console.log('Visme Forms Embed Script - Loaded Successfully');
            }
        })();

        // Smooth page transitions
        window.addEventListener('beforeunload', function() {
            document.body.style.opacity = '0.8';
            document.body.style.transform = 'scale(0.98)';
        });
    </script>
</body>
</html>
