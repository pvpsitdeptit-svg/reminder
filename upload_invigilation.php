<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Include Firebase configuration
require_once 'config/firebase.php';

$upload_status = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['invigilation_csv'])) {
    $file = $_FILES['invigilation_csv'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
    } elseif ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
        $errors[] = 'Please upload a CSV file';
    } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        $errors[] = 'File size too large. Maximum size is 5MB';
    } else {
        // Parse CSV
        $invigilation_data = parseCSVFile($file);
        
        if (empty($invigilation_data)) {
            $errors[] = 'CSV file is empty or invalid format';
        } else {
            // Validate data
            $validation_errors = validateCSVData($invigilation_data, 'invigilation');
            
            if (!empty($validation_errors)) {
                $errors = array_merge($errors, $validation_errors);
            } else {
                // Upload to Firebase
                try {
                    $invigilation_ref = $database->getReference('invigilation');
                    
                    // Clear existing data (optional - remove if you want to append)
                    $invigilation_ref->set([]);
                    
                    // Upload new data
                    foreach ($invigilation_data as $duty) {
                        $invigilation_ref->push($duty);
                    }
                    
                    $upload_status = 'success';
                    $_SESSION['success_message'] = count($invigilation_data) . ' invigilation duty records uploaded successfully!';
                    
                } catch (Exception $e) {
                    $errors[] = 'Error uploading to Firebase: ' . $e->getMessage();
                }
            }
        }
    }
}

// Redirect back to dashboard with status
if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
}

header('Location: index.php');
exit();
?>
