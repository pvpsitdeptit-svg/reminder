<?php
require_once 'firebase_auth.php';

// Check authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser();

// Include the original index.php content but with Firebase auth
require_once 'config/firebase.php';

$uploads = [
    ['Faculty Leave Master', 'upload_faculty_leaves.php', 'success'],
    ['Lecture Timetable', 'upload_lecture.php', 'primary'],
    ['Invigilation Duty', 'upload_invigilation.php', 'info'],
];

$master = [];
$ledger = [];
try {
    $mSnap = $database->getReference('faculty_leave_master')->getSnapshot();
    if ($mSnap->exists()) {
        $master = $mSnap->getValue();
        if (!is_array($master)) $master = [];
    }

    $lSnap = $database->getReference('leave_ledger')->getSnapshot();
    if ($lSnap->exists()) {
        $ledger = $lSnap->getValue();
        if (!is_array($ledger)) $ledger = [];
    }
} catch (Exception $e) {
    $error = 'Error loading data: ' . $e->getMessage();
}

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Faculty Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .card {
            transition: transform 0.2s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
        }
        .upload-area:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index_firebase.php">
            <i class="bi bi-calendar-check"></i> Faculty Management System
        </a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link text-white" href="templates.php">
                <i class="bi bi-file-earmark-text"></i> Templates
            </a>
            <a class="nav-link text-white" href="manage_faculty_leaves.php">
                <i class="bi bi-person-lines-fill"></i> Faculty Leaves
            </a>
            <a class="nav-link text-white" href="leave_balance_report.php">
                <i class="bi bi-graph-up"></i> Balance Report
            </a>
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle"></i> <?php echo h($currentUser['displayName']); ?>
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">
                        <i class="bi bi-person"></i> Profile
                    </a></li>
                    <li><a class="dropdown-item" href="#">
                        <i class="bi bi-gear"></i> Settings
                    </a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="firebase_logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-speedometer2"></i> Dashboard
        </h1>
        <div class="text-muted">
            <i class="bi bi-person"></i> Logged in as: <?php echo h($currentUser['email']); ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo h($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo h($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-people"></i> Faculty Members
                    </h5>
                    <h2 class="mb-0"><?php echo count($master); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-journal-check"></i> Leave Records
                    </h5>
                    <h2 class="mb-0"><?php echo count($ledger); ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-calendar-check"></i> Active Today
                    </h5>
                    <h2 class="mb-0">
                        <?php 
                        $today = date('Y-m-d');
                        $todayLeaves = array_filter($ledger, function($record) use ($today) {
                            return ($record['date'] ?? '') === $today;
                        });
                        echo count($todayLeaves);
                        ?>
                    </h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-graph-up"></i> This Month
                    </h5>
                    <h2 class="mb-0">
                        <?php 
                        $thisMonth = date('Y-m');
                        $monthLeaves = array_filter($ledger, function($record) use ($thisMonth) {
                            return strpos($record['date'] ?? '', $thisMonth) === 0;
                        });
                        echo count($monthLeaves);
                        ?>
                    </h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Section -->
    <div class="row">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="bi bi-cloud-upload"></i> Upload Data
            </h4>
        </div>
        <?php foreach ($uploads as $u): ?>
        <div class="col-md-4">
            <div class="card shadow-soft">
                <div class="card-header bg-<?= $u[2]; ?> text-white"><?= $u[0]; ?></div>
                <div class="card-body">
                    <form action="<?= $u[1]; ?>" method="post" enctype="multipart/form-data">
                        <div class="upload-area mb-3">
                            <i class="bi bi-file-earmark-csv fs-1 text-muted"></i>
                            <p class="mb-2">Upload CSV File</p>
                            <input type="file" class="form-control" name="<?= $u[0] === 'Faculty Leave Master' ? 'faculty_leaves_csv' : 'csv_file'; ?>" required>
                        </div>
                        <button class="btn btn-<?= $u[2]; ?> w-100">
                            <i class="bi bi-cloud-upload"></i> Upload
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <h4 class="mb-3">
                <i class="bi bi-lightning"></i> Quick Actions
            </h4>
        </div>
        <div class="col-md-3">
            <a href="manage_leave_availed.php" class="btn btn-outline-primary w-100 mb-2">
                <i class="bi bi-plus-circle"></i> Add Leave Record
            </a>
        </div>
        <div class="col-md-3">
            <a href="leave_balance_report.php" class="btn btn-outline-success w-100 mb-2">
                <i class="bi bi-graph-up"></i> View Reports
            </a>
        </div>
        <div class="col-md-3">
            <a href="templates.php" class="btn btn-outline-info w-100 mb-2">
                <i class="bi bi-download"></i> Download Templates
            </a>
        </div>
        <div class="col-md-3">
            <a href="manage_faculty_leaves.php" class="btn btn-outline-warning w-100 mb-2">
                <i class="bi bi-people"></i> Manage Faculty
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
