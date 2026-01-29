<?php
// Firebase Setup Guide
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firebase Setup Guide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h3><i class="bi bi-gear"></i> Firebase Setup Instructions</h3>
                    </div>
                    <div class="card-body">
                        <h5>Step 1: Firebase Console Setup</h5>
                        <ol>
                            <li>Go to <a href="https://console.firebase.google.com/" target="_blank">Firebase Console</a></li>
                            <li>Create a new project or select existing one</li>
                            <li>Go to "Build" section → "Realtime Database"</li>
                            <li>Click "Create Database"</li>
                            <li>Choose test mode (for development) or locked mode (for production)</li>
                            <li>Select a database location</li>
                        </ol>

                        <h5>Step 2: Get Service Account Key</h5>
                        <ol>
                            <li>Go to Project Settings (gear icon)</li>
                            <li>Click "Service accounts" tab</li>
                            <li>Click "Generate new private key"</li>
                            <li>Download the JSON file</li>
                            <li>Open the downloaded JSON file</li>
                        </ol>

                        <h5>Step 3: Update Configuration</h5>
                        <p>Edit <code>config/firebase.php</code> and replace the placeholder values:</p>
                        
                        <div class="alert alert-info">
                            <h6>From your Firebase project settings, copy:</h6>
                            <ul>
                                <li><strong>Project ID:</strong> From Project Settings → General</li>
                                <li><strong>Database URL:</strong> From Realtime Database → Data tab (URL format: https://your-project.firebaseio.com)</li>
                            </ul>
                        </div>

                        <div class="alert alert-warning">
                            <h6>From your service account JSON file, copy:</h6>
                            <ul>
                                <li><strong>project_id</strong></li>
                                <li><strong>private_key</strong> (the entire key including BEGIN/END lines)</li>
                                <li><strong>client_email</strong></li>
                                <li><strong>private_key_id</strong></li>
                                <li><strong>client_id</strong></li>
                            </ul>
                        </div>

                        <h5>Step 4: Example Configuration</h5>
                        <pre><code>// Firebase configuration
$firebaseConfig = [
    'databaseURL' => 'https://your-project-name-default-rtdb.firebaseio.com'
];

// Service account configuration
$serviceAccount = [
    'type' => 'service_account',
    'project_id' => 'your-project-name',
    'private_key' => '-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC...\n-----END PRIVATE KEY-----\n',
    'client_email' => 'firebase-adminsdk-xxxxx@your-project-name.iam.gserviceaccount.com',
    'client_id' => '123456789012345678901',
    'private_key_id' => 'abcdef1234567890abcdef1234567890abcdef12'
];</code></pre>

                        <h5>Step 5: Test Connection</h5>
                        <p>After updating the configuration, test your upload functionality. The system will automatically use Firebase if configured correctly, or fall back to mock data if there are issues.</p>

                        <div class="mt-4">
                            <a href="index.php" class="btn btn-primary">
                                <i class="bi bi-arrow-left"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
