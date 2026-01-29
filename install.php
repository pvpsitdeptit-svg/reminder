<?php
// Installation guide page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Guide - Faculty Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3><i class="bi bi-gear"></i> Installation Guide</h3>
                    </div>
                    <div class="card-body">
                        <h5>Step 1: Install Dependencies</h5>
                        <p>Run this command in your project directory:</p>
                        <pre><code>composer install</code></pre>
                        
                        <h5>Step 2: Configure Firebase</h5>
                        <p>Update the Firebase configuration in <code>config/firebase.php</code>:</p>
                        <ul>
                            <li>Replace Firebase project details</li>
                            <li>Add your service account credentials</li>
                            <li>Update database URL</li>
                        </ul>
                        
                        <h5>Step 3: Set Up Database Structure</h5>
                        <p>Your Firebase Realtime Database should have these paths:</p>
                        <ul>
                            <li><code>/lectures/</code> - Store lecture schedules</li>
                            <li><code>/invigilation/</code> - Store invigilation duties</li>
                        </ul>
                        
                        <h5>Step 4: CSV File Format</h5>
                        <p><strong>Lecture CSV Format:</strong></p>
                        <pre><code>date,time,faculty_id,subject,room
2024-01-15,09:00,FAC001,Mathematics,Room101
2024-01-15,10:00,FAC002,Physics,Room102</code></pre>
                        
                        <p><strong>Invigilation CSV Format:</strong></p>
                        <pre><code>date,time,faculty_id,exam,room
2024-01-20,09:00,FAC001,Mid-Term,ExamHall1
2024-01-20,14:00,FAC002,Final,ExamHall2</code></pre>
                        
                        <h5>Step 5: Access the System</h5>
                        <p>Default login credentials:</p>
                        <ul>
                            <li>Username: <code>admin</code></li>
                            <li>Password: <code>admin123</code></li>
                        </ul>
                        
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> 
                            The system will work with mock data if Firebase is not configured.
                        </div>
                        
                        <div class="mt-4">
                            <a href="login.php" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right"></i> Go to Login
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
