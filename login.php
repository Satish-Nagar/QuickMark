<?php
session_start();
require_once 'config/database.php';
require_once 'includes/password_reset.php';

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($_SESSION['user_type'] === 'om') {
        header('Location: om/dashboard.php');
        exit;
    }
}

$login_error = '';
$reset_error = '';
$reset_success = '';
$show_forgot = isset($_GET['forgot']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['login'])) {
        $email = trim(strtolower($_POST['email']));
        $password = $_POST['password'];
        
        if (empty($email) || empty($password)) {
            $login_error = 'Please enter both email and password.';
        } else {
            $database = new Database();
            $pdo = $database->getConnection();
            
            // Check in admins table first
            $stmt = $pdo->prepare("SELECT id, username, email, password FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['user_id'] = $admin['id'];
                $_SESSION['user_type'] = 'admin';
                $_SESSION['username'] = $admin['username'];
                $_SESSION['email'] = $admin['email'];
                header('Location: admin/dashboard.php');
                exit;
            } else {
                // Check in operation_managers table
                $stmt = $pdo->prepare("SELECT id, name, email, password, college FROM operation_managers WHERE email = ? AND is_active = TRUE");
                $stmt->execute([$email]);
                $om = $stmt->fetch();
                
                if ($om && password_verify($password, $om['password'])) {
                    $_SESSION['user_id'] = $om['id'];
                    $_SESSION['user_type'] = 'om';
                    $_SESSION['username'] = $om['name'];
                    $_SESSION['email'] = $om['email'];
                    $_SESSION['college'] = $om['college'];
                    header('Location: om/dashboard.php');
                    exit;
                } else {
                    $login_error = 'Invalid email or password.';
                }
            }
        }
    } elseif (isset($_POST['reset'])) {
        $reset_email = trim(strtolower($_POST['reset_email']));
        
        if (empty($reset_email)) {
            $reset_error = 'Please enter your email address.';
        } else {
            $passwordReset = new PasswordReset();
            
            // Check if email exists
            if ($passwordReset->checkEmailExists($reset_email)) {
                // Send reset email
                if ($passwordReset->sendResetEmail($reset_email)) {
                    $reset_success = 'Password reset link has been sent to your email address. Please check your inbox and spam folder.';
                } else {
                    $reset_error = 'Failed to send reset email. Please try again later.';
                }
    } else {
                // Don't reveal if email exists or not for security
                $reset_success = 'If your email is registered, you will receive a password reset link shortly.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance Automation System - Login</title>
    
    <!-- Favicon and App Icons -->
    <link rel="icon" type="image/x-icon" href="download.png">
    <link rel="shortcut icon" type="image/x-icon" href="download.png">
    <link rel="apple-touch-icon" sizes="180x180" href="download.png">
    <link rel="icon" type="image/png" sizes="32x32" href="download.png">
    <link rel="icon" type="image/png" sizes="16x16" href="download.png">
    
    <!-- Web App Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#7f8ff4">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="QuickMark">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #7f8ff4 0%, #6ad6e8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            animation: slideInUp 0.8s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo {
            width: 80px;
            height: 80px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            animation: logoGlow 2s ease-in-out infinite alternate;
        }

        @keyframes logoGlow {
            0% { box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15); }
            100% { box-shadow: 0 12px 35px rgba(127, 143, 244, 0.3); }
        }

        .title {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            text-align: center;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            text-align: center;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-control {
            border: 2px solid #e1e5e9;
            border-radius: 12px;
            padding: 15px 20px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.9);
        }

        .form-control:focus {
            border-color: #7f8ff4;
            box-shadow: 0 0 0 0.2rem rgba(127, 143, 244, 0.25);
            background: white;
        }

        .input-group-text {
            background: transparent;
            border: 2px solid #e1e5e9;
            border-right: none;
            border-radius: 12px 0 0 12px;
            color: #7f8ff4;
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 12px 12px 0;
        }

        .btn-login {
            background: linear-gradient(135deg, #7f8ff4 0%, #6ad6e8 100%);
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(127, 143, 244, 0.3);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .forgot-link {
            color: #7f8ff4;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-link:hover {
            color: #6ad6e8;
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
            margin-bottom: 20px;
        }

        .alert-danger {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
        }

        .reset-form {
            display: none;
        }

        .reset-form.show {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .back-to-login {
            color: #7f8ff4;
            text-decoration: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
            transition: color 0.3s ease;
        }

        .back-to-login:hover {
            color: #6ad6e8;
        }

        .back-to-login i {
            margin-right: 5px;
        }

        /* Floating Animation */
        .floating {
            animation: floating 3s ease-in-out infinite;
        }

        @keyframes floating {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        /* Responsive Design */
        @media (max-width: 480px) {
            .login-container {
                margin: 20px;
                padding: 30px 20px;
            }
            
            .title {
                font-size: 1.5rem;
            }
            
            .logo {
                width: 60px;
                height: 60px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container floating">
        <div class="logo-container">
            <img src="download.png" alt="QuickMark Logo" class="logo">
        </div>
        
        <h1 class="title">QuickMark</h1>
        <p class="subtitle">Smart Attendance Automation System</p>

        <?php if ($login_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $login_error; ?>
            </div>
        <?php endif; ?>

        <?php if ($reset_error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i><?php echo $reset_error; ?>
            </div>
        <?php endif; ?>

        <?php if ($reset_success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i><?php echo $reset_success; ?>
            </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div id="loginForm" class="<?php echo $show_forgot ? 'reset-form' : ''; ?>">
            <form method="POST" action="">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="email" placeholder="Enter your email" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control" name="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <button type="submit" name="login" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <div class="text-center mt-3">
                <a href="?forgot=1" class="forgot-link" id="forgotLink">
                    <i class="fas fa-key me-1"></i>Forgot Password?
                </a>
            </div>
        </div>

        <!-- Reset Password Form -->
        <div id="resetForm" class="reset-form <?php echo $show_forgot ? 'show' : ''; ?>">
            <a href="?" class="back-to-login">
                <i class="fas fa-arrow-left"></i>Back to Login
            </a>
            
            <form method="POST" action="">
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control" name="reset_email" placeholder="Enter your email address" required>
                    </div>
                </div>
                
                <button type="submit" name="reset" class="btn btn-login">
                    <i class="fas fa-paper-plane me-2"></i>Send Reset Link
                </button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle between login and reset forms
        document.getElementById('forgotLink').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('loginForm').classList.add('reset-form');
            document.getElementById('resetForm').classList.add('show');
        });

        // Add some interactive effects
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html> 