<?php
require_once 'firebase_auth.php';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Debug logging
    error_log("Firebase login attempt - Email: " . $email);
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        error_log("Calling signInWithEmail for: " . $email);
        $result = signInWithEmail($email, $password);
        error_log("Firebase result: " . print_r($result, true));
        
        if ($result && isset($result['idToken'])) {
            // Store user session
            $_SESSION['user'] = [
                'uid' => $result['localId'],
                'email' => $result['email'],
                'idToken' => $result['idToken'],
                'refreshToken' => $result['refreshToken'],
                'displayName' => $result['displayName'] ?? $email
            ];
            
            error_log("Firebase login successful for: " . $email);
            
            // Redirect to intended page or dashboard
            $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirect_url);
            exit();
        } else {
            $error = 'Invalid email or password';
            error_log("Firebase login failed for: " . $email);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Faculty Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 900px;
            width: 100%;
        }
        .login-form {
            padding: 40px;
        }
        .login-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }
        .tab-content {
            padding-top: 20px;
        }
        .nav-tabs .nav-link.active {
            color: #667eea;
            border-color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="row g-0">
                <div class="col-md-6">
                    <div class="login-info">
                        <h2 class="mb-4">
                            <i class="bi bi-calendar-check"></i> Faculty Management System
                        </h2>
                        <div class="text-center mb-4">
                            <h3>Welcome Back</h3>
                            <p class="text-muted">Sign in to access your faculty management dashboard</p>
                        </div>
                        <div class="features">
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-shield-check me-3"></i>
                                <span>Secure Firebase Authentication</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="bi bi-database me-3"></i>
                                <span>Real-time Data Sync</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-graph-up me-3"></i>
                                <span>Advanced Leave Management</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="login-form">
                        <h3 class="text-center mb-4">Faculty Login</h3>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="mb-3">
                                <label for="login_email" class="form-label">
                                    <i class="bi bi-envelope"></i> Email Address
                                </label>
                                <input type="email" class="form-control" id="login_email" name="email" required 
                                       placeholder="Enter your email" value="<?php echo $_POST['email'] ?? ''; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="login_password" class="form-label">
                                    <i class="bi bi-lock"></i> Password
                                </label>
                                <input type="password" class="form-control" id="login_password" name="password" required 
                                       placeholder="Enter your password">
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                Powered by <i class="bi bi-google"></i> Firebase Authentication
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
