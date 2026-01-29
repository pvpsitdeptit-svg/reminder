<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

function field($name) { return isset($_POST[$name]) ? trim((string)$_POST[$name]) : ''; }

$originalEmail = strtolower(field('original_email'));
$email = strtolower(field('faculty_email'));

$data = [
    'employee_id' => field('employee_id'),
    'name' => field('name'),
    'department' => field('department'),
    'faculty_email' => $email,
    'total_leaves' => field('total_leaves'),
    'cl' => field('cl'),
    'el' => field('el'),
    'ml' => field('ml'),
];

$errors = validateCSVData([$data], 'faculty_leave_master');
if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    $redir = 'manage_faculty_leaves.php' . ($originalEmail ? ('?email=' . urlencode($originalEmail)) : '');
    header('Location: ' . $redir);
    exit();
}

try {
    $now = time();
    $uploadBatchId = date('Ymd_His');

    $originalKey = $originalEmail ? firebaseKeyFromEmail($originalEmail) : '';
    $key = firebaseKeyFromEmail($email);

    $payload = [
        'employee_id' => $data['employee_id'],
        'name' => $data['name'],
        'department' => $data['department'],
        'faculty_email' => $email,
        'total_leaves' => (float)$data['total_leaves'],
        'cl' => (float)$data['cl'],
        'el' => (float)$data['el'],
        'ml' => (float)$data['ml'],
        'uploadBatchId' => $uploadBatchId,
        'updatedAt' => $now,
    ];

    $existingSnap = $database->getReference('faculty_leave_master/' . ($originalKey ?: $key))->getSnapshot();
    if ($existingSnap->exists()) {
        $existing = $existingSnap->getValue();
        if (is_array($existing) && isset($existing['createdAt'])) {
            $payload['createdAt'] = $existing['createdAt'];
        } else {
            $payload['createdAt'] = $now;
        }
    } else {
        $payload['createdAt'] = $now;
    }

    if ($originalEmail && $originalEmail !== $email) {
        $database->getReference('faculty_leave_master/' . $originalKey)->set(null);
    }

    $database->getReference('faculty_leave_master/' . $key)->set($payload);
    $_SESSION['success_message'] = 'Faculty leave master saved successfully';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error saving faculty leave master: ' . $e->getMessage();
}

header('Location: manage_faculty_leaves.php');
exit();
