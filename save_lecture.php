<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

function field($name) { return isset($_POST[$name]) ? trim((string)$_POST[$name]) : ''; }

$id = isset($_POST['id']) ? trim((string)$_POST['id']) : '';
$data = [
    'day' => field('day'),
    'time' => field('time'),
    'name' => field('name'),
    'faculty_id' => field('faculty_id'),
    'faculty_email' => field('faculty_email'),
    'subject' => field('subject'),
    'room' => field('room'),
];

// Reuse CSV validator with a single row
$errors = validateCSVData([$data], 'lecture_template');

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    $redir = 'manage_lectures.php' . ($id ? ('?id=' . urlencode($id)) : '');
    header('Location: ' . $redir);
    exit();
}

try {
    if ($id) {
        // Update existing
        $database->getReference('lecture_templates/' . $id)->set($data);
        $_SESSION['success_message'] = 'Lecture template updated successfully';
    } else {
        // Create new
        $database->getReference('lecture_templates')->push($data);
        $_SESSION['success_message'] = 'Lecture template created successfully';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error saving template: ' . $e->getMessage();
}

header('Location: manage_lectures.php');
exit();
