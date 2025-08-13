<?php
session_start();
include('connect/db.php');
include('connect/fun.php');

// Redirect if already logged in as agent
if (isset($_SESSION['agent_logged_in']) && $_SESSION['agent_logged_in'] === true) {
    header('Location: agent-dashboard.php');
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
        $stmt = mysqli_prepare($db, "SELECT id, agent_id, name, email, phone, agency_name, commission_rate, is_active, password FROM agents WHERE (agent_id = ? OR email = ?) AND is_active = 1 LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $agent = mysqli_fetch_assoc($result);
            if (password_verify($password, $agent['password'])) {
                $_SESSION['agent_logged_in'] = true;
                $_SESSION['agent_id'] = $agent['id'];
                $_SESSION['agent_username'] = $agent['agent_id'];
                $_SESSION['agent_name'] = $agent['name'];
                $_SESSION['agent_email'] = $agent['email'];
                $_SESSION['agent_phone'] = $agent['phone'];
                $_SESSION['agency_name'] = $agent['agency_name'];
                $_SESSION['commission_rate'] = $agent['commission_rate'];

                header('Location: agent-dashboard.php');
                exit();
            } else {
                $error_message = 'Invalid username or password.';
            }
        } else {
            $error_message = 'No account found with these credentials or account is inactive.';
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
    <title>Agent Login - IRCTC Rail Connect</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #f97316;
            --accent-color: #059669;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(45deg, #1e3a8a, #2c5282, #4a5568, #2d3748);
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
        
        .login-container {
            background: rgba(255, 255, 255, 0.97);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
            position: relative;
            z-index: 10;
            animation: containerEntrance 1.2s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        @keyframes containerEntrance {
            0% { opacity: 0; transform: translateY(30px) scale(0.98); }
            100% { opacity: 1; transform: translateY(0) scale(1); }
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), #2c5282);
            padding: 2.5rem 2rem;
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-header h1 {
            font-weight: 700;
            margin: 1rem 0 0.5rem;
            font-size: 1.8rem;
        }
        
        .login-header p {
            opacity: 0.9;
            margin: 0;
            font-size: 0.95rem;
        }
        
        .login-form {
            padding: 2.5rem;
        }
        
        .form-control {
            height: 50px;
            border-radius: 8px;
            padding-left: 50px;
            border: 2px solid #e1e8ed;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(30, 58, 138, 0.25);
        }
        
        .input-group-text {
            position: absolute;
            left: 1px;
            top: 1px;
            bottom: 1px;
            width: 45px;
            background: #f8f9fa;
            border: none;
            border-radius: 8px 0 0 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
            color: var(--primary-color);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #2c5282);
            border: none;
            height: 50px;
            font-weight: 600;
            letter-spacing: 0.5px;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-size: 1.05rem;
            width: 100%;
        }
        
        .btn-login:hover {
            background: linear-gradient(135deg, #1e40af, #2c5282);
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: var(--primary-color);
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .login-footer a:hover {
            color: var(--secondary-color);
            text-decoration: underline;
        }
        
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
        
        .train {
            position: absolute;
            bottom: 40px;
            left: -100px;
            font-size: 2rem;
            z-index: 5;
            animation: trainMove 15s linear infinite;
        }
        
        @keyframes trainMove {
            0% { transform: translateX(-100px); }
            100% { transform: translateX(calc(100vw + 100px)); }
        }
        
        @media (max-width: 576px) {
            .login-container {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }
            
            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="railway-track"></div>
    <div class="train">🚂💨</div>
    
    <div class="login-container">
        <div class="login-header">
            <img src="IRCTC-logo1.png" alt="IRCTC Logo" style="max-width: 180px; filter: brightness(0) invert(1);">
            <h1>Agent Portal</h1>
            <p>Sign in to access your agent dashboard</p>
        </div>
        
        <div class="login-form">
            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="username" class="form-label">Agent ID or Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user-tie"></i></span>
                        <input type="text" class="form-control" id="username" name="username" required 
                               placeholder="Enter your agent ID or email">
                        <div class="invalid-feedback">
                            Please enter your agent ID or email.
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required 
                               placeholder="Enter your password">
                        <button class="btn btn-outline-secondary position-absolute end-0 top-0 h-100" type="button" id="togglePassword" style="z-index: 10;">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="invalid-feedback">
                            Please enter your password.
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2 mb-4">
                    <button type="submit" class="btn btn-primary btn-login">
                        <i class="fas fa-sign-in-alt me-2"></i>Sign In
                    </button>
                </div>
                
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                        <label class="form-check-label" for="rememberMe">Remember me</label>
                    </div>
                    <a href="forgot-password.php" class="text-decoration-none">Forgot Password?</a>
                </div>
            </form>
            
            <div class="login-footer">
                Not an agent? <a href="login.php">Passenger Login</a> | 
                <a href="index.php">Back to Home</a>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
        
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const password = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>
