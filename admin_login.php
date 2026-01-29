<?php
require_once 'admin_auth.php';

$error = '';
$success = '';

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? ''; // Can be username or email
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Please enter both username/email and password';
    } else {
        if (loginAdmin($login, $password)) {
            $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirect_url);
            exit();
        } else {
            $error = 'Invalid username/email or password';
        }
    }
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Faculty Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            margin: 20px;
        }
        .login-left {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-right {
            padding: 60px 40px;
        }
        .form-control:focus {
            border-color: #2a5298;
            box-shadow: 0 0 0 0.2rem rgba(42, 82, 152, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #2a5298 0%, #1e3c72 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(42, 82, 152, 0.3);
        }
        .admin-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }
        .feature-item i {
            margin-right: 15px;
            font-size: 1.2em;
        }
        .default-credentials {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        .logo {
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .login-left {
                display: none;
            }
            .login-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="row g-0 h-100">
            <div class="col-lg-6">
                <div class="login-left h-100">
                    <div class="logo">
                        <i class="bi bi-shield-lock"></i>
                    </div>
                    <div class="admin-badge">
                        <i class="bi bi-person-badge"></i> ADMIN ACCESS
                    </div>
                    <h2 class="mb-4">Faculty Management System</h2>
                    <p class="mb-4">
                        Secure administrative access for managing faculty records, 
                        leave balances, and system configurations.
                    </p>
                    
                    <div class="features">
                        <div class="feature-item">
                            <i class="bi bi-people-fill"></i>
                            <span>Faculty Management</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-calendar-check"></i>
                            <span>Leave Tracking</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-graph-up"></i>
                            <span>Reports & Analytics</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-shield-check"></i>
                            <span>Secure Data Access</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="login-right">
                    <div class="text-center mb-4">
                        <h3>Admin Login</h3>
                        <p class="text-muted">Enter your credentials to access the admin panel</p>
                    </div>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo h($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo h($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post">
                        <div class="mb-4">
                            <label for="login" class="form-label">
                                <i class="bi bi-person-fill"></i> Username or Email
                            </label>
                            <input type="text" class="form-control form-control-lg" id="login" name="login" 
                                   required placeholder="Enter username or email" 
                                   value="<?php echo h($_POST['login'] ?? ''); ?>">
                            <div class="form-text">You can use either username or email</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">
                                <i class="bi bi-lock-fill"></i> Password
                            </label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" 
                                   required placeholder="Enter your password">
                        </div>
                        
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">
                                Remember me for 30 days
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login btn-lg w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In
                        </button>
                    </form>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i> 
                            Secure Admin Access - Authorized Personnel Only
                        </small>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="firebase_login.php" class="btn btn-outline-secondary">
                            <i class="bi bi-person"></i> User Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-focus on email field
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('email').focus();
        });
        
        // Clear form on back button
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                document.querySelector('form').reset();
            }
        });
    </script>
</body>
</html>
