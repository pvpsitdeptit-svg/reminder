<?php
// Helper function for HTML escaping
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Check if user is logged in with Firebase
require_once 'firebase_auth.php';

if (!isAuthenticated()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: firebase_login.php');
    exit();
}

// Get current user info
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FMS - Faculty Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { 
            background: #f8f9fa; 
            min-height: 100vh;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.3rem;
        }
        .navbar-nav .nav-link {
            transition: all 0.3s ease;
            border-radius: 8px;
            margin: 0 4px;
            padding: 10px 16px;
            font-weight: 500;
        }
        .navbar-nav .nav-link:hover {
            background-color: rgba(255,255,255,0.15);
            transform: translateY(-1px);
        }
        .navbar-nav .nav-link.active {
            background-color: rgba(255,255,255,0.25);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .dropdown-menu {
            min-width: 220px;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            margin-top: 8px;
        }
        .dropdown-item {
            padding: 12px 20px;
            border-radius: 4px;
            margin: 2px 8px;
            transition: all 0.2s ease;
        }
        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(4px);
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .user-info:hover {
            background-color: rgba(255,255,255,0.1);
        }
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .main-content {
            padding-top: 70px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .card-header {
            border-radius: 12px 12px 0 0;
            border-bottom: none;
        }
    </style>
    
    <!-- External JavaScript for AI interactions -->
    <script src="assets/js/ai-optimization.js"></script>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="bi bi-calendar-check"></i> FMS
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo (basename($_SERVER['PHP_SELF']) === 'index.php' || basename($_SERVER['PHP_SELF']) === 'faculty_dashboard.php') ? 'active' : ''; ?>" href="<?php echo isAdmin() ? 'index.php' : 'faculty_dashboard.php'; ?>">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a>
                    </li>
                    
                    <!-- Admin-only menus -->
                    <?php if (isAdmin()): ?>
                    <!-- Faculty Management -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-people"></i> Faculty
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="manage_faculty_leaves.php">
                                <i class="bi bi-person-lines-fill"></i> Faculty Leaves
                            </a></li>
                            <li><a class="dropdown-item" href="manage_leave_availed.php">
                                <i class="bi bi-journal-check"></i> Leave Management
                            </a></li>
                            <li><a class="dropdown-item" href="manage_leave_requests.php">
                                <i class="bi bi-clipboard-check"></i> Leave Requests
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Academic Management -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-book"></i> Academic
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="manage_lectures.php">
                                <i class="bi bi-journal-text"></i> Manage Lectures
                            </a></li>
                            <li><a class="dropdown-item" href="manage_invigilation.php">
                                <i class="bi bi-clipboard-check"></i> Invigilation
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Reports -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-graph-up"></i> Reports
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="reports.php">
                                <i class="bi bi-file-earmark-bar-graph"></i> Generate Reports
                            </a></li>
                            <li><a class="dropdown-item" href="analytics.php">
                                <i class="bi bi-graph-up-arrow"></i> Analytics
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Templates -->
                    <li class="nav-item">
                        <a class="nav-link text-white <?php echo basename($_SERVER['PHP_SELF']) === 'templates.php' ? 'active' : ''; ?>" href="templates.php">
                            <i class="bi bi-file-earmark-text"></i> Templates
                        </a>
                    </li>
                    
                    <!-- Messaging -->
                    <li class="nav-item">
                        <a class="nav-link text-white" href="messaging.php">
                            <i class="bi bi-chat-dots"></i> Messaging
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <!-- Faculty-only menus -->
                    <?php if (!isAdmin()): ?>
                    <!-- My Schedule -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="bi bi-calendar-week"></i> My Schedule
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="view_lectures.php">
                                <i class="bi bi-chalkboard"></i> My Lectures
                            </a></li>
                            <li><a class="dropdown-item" href="view_invigilation.php">
                                <i class="bi bi-clipboard-check"></i> My Invigilation
                            </a></li>
                            <li><a class="dropdown-item" href="view_leaves.php">
                                <i class="bi bi-calendar-x"></i> My Leave History
                            </a></li>
                        </ul>
                    </li>
                    
                    <!-- Leave Management -->
                    <li class="nav-item">
                        <a class="nav-link text-white" href="leave_request.php">
                            <i class="bi bi-calendar-plus"></i> Request Leave
                        </a>
                    </li>
                    
                    <!-- Profile -->
                    <li class="nav-item">
                        <a class="nav-link text-white" href="profile.php">
                            <i class="bi bi-person"></i> My Profile
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($userDisplayName, 0, 1)); ?>
                            </div>
                            <span class="d-none d-md-inline"><?php echo h($userDisplayName); ?></span>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white" href="firebase_logout.php">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
