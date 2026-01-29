<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['faculty_leaves_csv'])) {
    $file = $_FILES['faculty_leaves_csv'];
    
    // Debug: Log file info
    error_log("Upload attempt - File error: " . $file['error']);
    error_log("Upload attempt - File type: " . $file['type']);
    error_log("Upload attempt - File size: " . $file['size']);

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'File upload error: ' . $file['error'];
    } elseif ($file['type'] !== 'text/csv' && $file['type'] !== 'application/vnd.ms-excel') {
        $errors[] = 'Please upload a CSV file. Current type: ' . $file['type'];
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $errors[] = 'File size too large. Maximum size is 5MB';
    } else {
        error_log("Parsing CSV file...");
        $rows = parseCSVFile($file);
        error_log("Parsed " . count($rows) . " rows");
        
        // Debug: Log first few rows to see structure
        if (!empty($rows)) {
            error_log("First row data: " . print_r($rows[0], true));
            error_log("Available headers: " . implode(', ', array_keys($rows[0])));
        }

        if (empty($rows)) {
            $errors[] = 'CSV file is empty or invalid format';
        } else {
            error_log("Validating CSV data...");
            $validation_errors = validateCSVData($rows, 'faculty_leave_master');
            if (!empty($validation_errors)) {
                $errors = array_merge($errors, $validation_errors);
                error_log("Validation errors: " . implode(', ', $validation_errors));
            } else {
                try {
                    error_log("Uploading to Firebase...");
                    $ref = $database->getReference('faculty_leave_master');

                    $uploadBatchId = date('Ymd_His');
                    $now = time();

                    $updated = 0;
                    foreach ($rows as $r) {
                        $email = strtolower(trim($r['faculty_email'] ?? ''));
                        if ($email === '') {
                            continue;
                        }

                        $key = firebaseKeyFromEmail($email);

                        $payload = [
                            'employee_id' => trim((string)($r['employee_id'] ?? '')),
                            'name' => trim((string)($r['name'] ?? '')),
                            'department' => trim((string)($r['department'] ?? '')),
                            'faculty_email' => $email,
                            'total_leaves' => (float)($r['total_leaves'] ?? 0),
                            'cl' => (float)($r['cl'] ?? 0),
                            'el' => (float)($r['el'] ?? 0),
                            'ml' => (float)($r['ml'] ?? 0),
                            'uploadBatchId' => $uploadBatchId,
                            'updatedAt' => $now,
                        ];

                        $existingSnap = $database->getReference('faculty_leave_master/' . $key)->getSnapshot();
                        if (!$existingSnap->exists()) {
                            $payload['createdAt'] = $now;
                        } else {
                            $existing = $existingSnap->getValue();
                            if (is_array($existing) && isset($existing['createdAt'])) {
                                $payload['createdAt'] = $existing['createdAt'];
                            } else {
                                $payload['createdAt'] = $now;
                            }
                        }

                        $database->getReference('faculty_leave_master/' . $key)->set($payload);
                        $updated++;
                        error_log("Updated record for: " . $email);
                    }

                    $_SESSION['success_message'] = $updated . ' faculty leave master records uploaded successfully!';
                    error_log("Upload completed: " . $updated . " records");
                } catch (Exception $e) {
                    $errors[] = 'Error uploading to Firebase: ' . $e->getMessage();
                    error_log("Firebase error: " . $e->getMessage());
                }
            }
        }
    }
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
}

header('Location: index.php');
exit();
?>
