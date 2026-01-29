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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['lecture_csv'])) {
    $file = $_FILES['lecture_csv'];
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
    } elseif ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
        $errors[] = 'Please upload a CSV file';
    } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        $errors[] = 'File size too large. Maximum size is 5MB';
    } else {
        // Parse CSV
        $lecture_data = parseCSVFile($file);
        
        if (empty($lecture_data)) {
            $errors[] = 'CSV file is empty or invalid format';
        } else {
            // Validate weekly template data
            $validation_errors = validateCSVData($lecture_data, 'lecture_template');
            
            if (!empty($validation_errors)) {
                $errors = array_merge($errors, $validation_errors);
            } else {
                // Upload to Firebase as weekly templates
                try {
                    $lectures_ref = $database->getReference('lecture_templates');
                    
                    // Clear existing data (optional - remove if you want to append)
                    $lectures_ref->set([]);
                    
                    // Upload new data
                    foreach ($lecture_data as $lecture) {
                        $lectures_ref->push($lecture);
                    }
                    
                    $upload_status = 'success';
                    $_SESSION['success_message'] = count($lecture_data) . ' weekly lecture template records uploaded successfully!';
                    
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
