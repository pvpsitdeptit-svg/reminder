<?php
require_once 'admin_auth.php';

// This is a utility script to create new admin users
// Remove or secure this file in production

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $name = $_POST['name'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    if (empty($email) || empty($password) || empty($name)) {
        $message = '<div class="alert alert-danger">All fields are required</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Invalid email address</div>';
    } elseif (strlen($password) < 6) {
        $message = '<div class="alert alert-danger">Password must be at least 6 characters</div>';
    } else {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Create admin user entry
        $adminUser = [
            'email' => $email,
            'password' => $hashedPassword,
            'name' => $name,
            'role' => $role,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        // In a real application, you'd save this to a database
        // For now, we'll show the configuration code
        $message = '<div class="alert alert-success">
            <h5>Admin User Created Successfully!</h5>
            <p><strong>Email:</strong> ' . htmlspecialchars($email) . '</p>
            <p><strong>Name:</strong> ' . htmlspecialchars($name) . '</p>
            <p><strong>Role:</strong> ' . htmlspecialchars($role) . '</p>
            <p><strong>Password:</strong> ' . htmlspecialchars($password) . '</p>
            
            <h6 class="mt-3">Add this to admin_auth.php:</h6>
            <pre><code>\'' . htmlspecialchars($email) . '\' => [
    \'password\' => \'' . $hashedPassword . '\',
    \'name\' => \'' . htmlspecialchars($name) . '\',
    \'role\' => \'' . htmlspecialchars($role) . '\'
],</code></pre>
        </div>';
    }
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - Faculty Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h4 class="mb-0">
                            <i class="bi bi-person-plus"></i> Create Admin User
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            <strong>Security Warning:</strong> This is a utility script. 
                            Remove or secure this file in production environment.
                        </div>
                        
                        <?php echo $message; ?>
                        
                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" required
                                       placeholder="admin@university.edu" value="<?php echo h($_POST['email'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required
                                       placeholder="John Doe" value="<?php echo h($_POST['name'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <select class="form-select" id="role" name="role">
                                    <option value="admin">Admin</option>
                                    <option value="super_admin">Super Admin</option>
                                    <option value="moderator">Moderator</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required
                                       placeholder="Minimum 6 characters" minlength="6">
                                <div class="form-text">Use a strong password with letters, numbers, and symbols.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-warning">
                                <i class="bi bi-person-plus"></i> Create Admin User
                            </button>
                            
                            <a href="admin_login.php" class="btn btn-secondary ms-2">
                                <i class="bi bi-arrow-left"></i> Back to Login
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });
    </script>
</body>
</html>
