<?php
require_once 'admin_firebase_auth.php';

$error = '';
$success = '';

// Handle login attempt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password';
    } else {
        if (loginFirebaseAdmin($email, $password)) {
            $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
            unset($_SESSION['redirect_url']);
            header('Location: ' . $redirect_url);
            exit();
        } else {
            $error = 'Invalid email/password or not authorized for admin access';
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
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-app-compat.js"></script>
    <script src="https://www.gstatic.com/firebasejs/9.6.1/firebase-auth-compat.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
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
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
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
            border-color: #4ecdc4;
            box-shadow: 0 0 0 0.2rem rgba(78, 205, 196, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #ff6b6b 0%, #4ecdc4 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #4ecdc4 0%, #ff6b6b 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 205, 196, 0.3);
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
        .firebase-badge {
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
        .google-btn {
            background: white;
            border: 1px solid #ddd;
            color: #333;
            padding: 10px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: all 0.3s;
        }
        .google-btn:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
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
                        <i class="bi bi-person-badge"></i> FIREBASE ADMIN
                    </div>
                    <h2 class="mb-4">Faculty Management System</h2>
                    <p class="mb-4">
                        Secure Firebase-powered authentication for administrators. 
                        Access your faculty management dashboard with enterprise-grade security.
                    </p>
                    
                    <div class="features">
                        <div class="feature-item">
                            <i class="bi bi-google"></i>
                            <span>Google Sign-In Ready</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-shield-check"></i>
                            <span>Enterprise Security</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-database"></i>
                            <span>Real-time Authentication</span>
                        </div>
                        <div class="feature-item">
                            <i class="bi bi-person-check"></i>
                            <span>Role-Based Access</span>
                        </div>
                    </div>
                    
                    <div class="firebase-badge">
                        <h6 class="mb-3">
                            <i class="bi bi-google"></i> Powered by Firebase
                        </h6>
                        <div class="small">
                            <div class="mb-2">
                                <strong>Authentication:</strong> Firebase Auth
                            </div>
                            <div class="mb-2">
                                <strong>Security:</strong> ID Token Verification
                            </div>
                            <div>
                                <strong>Admin Emails:</strong> Pre-authorized
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="login-right">
                    <div class="text-center mb-4">
                        <h3>Admin Login</h3>
                        <p class="text-muted">Sign in with your Firebase admin account</p>
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

                    <form method="post" id="loginForm">
                        <div class="mb-4">
                            <label for="email" class="form-label">
                                <i class="bi bi-envelope-fill"></i> Admin Email
                            </label>
                            <input type="email" class="form-control form-control-lg" id="email" name="email" 
                                   required placeholder="Enter your admin email" 
                                   value="<?php echo h($_POST['email'] ?? ''); ?>">
                            <div class="form-text">Must be pre-authorized admin email</div>
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
                                Remember me
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-login btn-lg w-100 mb-3">
                            <i class="bi bi-box-arrow-in-right"></i> Sign In with Firebase
                        </button>
                    </form>
                    
                    <div class="text-center mb-3">
                        <small class="text-muted">OR</small>
                    </div>
                    
                    <div class="text-center mb-4">
                        <button class="google-btn w-100" onclick="signInWithGoogle()">
                            <i class="bi bi-google me-2"></i> Sign in with Google
                        </button>
                    </div>
                    
                    <div class="text-center mt-4">
                        <small class="text-muted">
                            <i class="bi bi-shield-check"></i> 
                            Secured by Firebase Authentication
                        </small>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="text-center">
                        <a href="admin_login.php" class="btn btn-outline-secondary me-2">
                            <i class="bi bi-person"></i> Local Admin
                        </a>
                        <a href="firebase_login.php" class="btn btn-outline-info">
                            <i class="bi bi-person"></i> User Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize Firebase
        const firebaseConfig = {
            apiKey: "AIzaSyCpm5OhNvWksaGGot76Bwr9EpYb1CH4FvY",
            authDomain: "reminder-c0728.firebaseapp.com",
            projectId: "reminder-c0728",
            storageBucket: "reminder-c0728.firebasestorage.app",
            messagingSenderId: "987181259638",
            appId: "1:987181259638:android:283e678c075059f9d7b857"
        };
        
        firebase.initializeApp(firebaseConfig);
        const auth = firebase.auth();
        
        // Google Sign-In
        function signInWithGoogle() {
            const provider = new firebase.auth.GoogleAuthProvider();
            auth.signInWithPopup(provider)
                .then((result) => {
                    const user = result.user;
                    // Send user data to server for verification
                    const formData = new FormData();
                    formData.append('google_signin', 'true');
                    formData.append('email', user.email);
                    formData.append('idToken', user.getIdToken());
                    formData.append('displayName', user.displayName);
                    
                    fetch('admin_firebase_login.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.text())
                    .then(data => {
                        if (data.includes('Location:')) {
                            window.location.href = data.split('Location: ')[1].split('\n')[0];
                        } else {
                            document.getElementById('loginForm').insertAdjacentHTML('beforebegin', 
                                '<div class="alert alert-danger">Authentication failed or not authorized</div>');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Authentication failed');
                    });
                })
                .catch((error) => {
                    console.error('Google Sign-In Error:', error);
                    alert('Google Sign-In failed: ' + error.message);
                });
        }
        
        // Handle Google Sign-In POST
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_signin'])): ?>
        <?php
        $email = $_POST['email'] ?? '';
        $idToken = $_POST['idToken'] ?? '';
        
        if (!empty($email) && !empty($idToken)) {
            // Verify the token and check if admin
            $user = verifyFirebaseAdminToken($idToken);
            if ($user) {
                $_SESSION['firebase_admin_user'] = [
                    'uid' => $user['localId'],
                    'email' => $user['email'],
                    'idToken' => $idToken,
                    'displayName' => $_POST['displayName'] ?? $user['email'],
                    'isAdmin' => true
                ];
                $redirect_url = $_SESSION['redirect_url'] ?? 'index.php';
                unset($_SESSION['redirect_url']);
                header('Location: ' . $redirect_url);
                exit();
            } else {
                $error = 'Not authorized for admin access';
            }
        }
        ?>
        <?php endif; ?>
    </script>
</body>
</html>
