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
    'date' => field('date'),
    'time' => field('time'),
    'faculty_id' => field('faculty_id'),
    'faculty_email' => field('faculty_email'),
    'exam' => field('exam'),
    'room' => field('room'),
];

// validate using existing invigilation validator with a single row
$errors = validateCSVData([$data], 'invigilation');

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    $redir = 'manage_invigilation.php' . ($id ? ('?id=' . urlencode($id)) : '');
    header('Location: ' . $redir);
    exit();
}

try {
    if ($id) {
        $database->getReference('invigilation/' . $id)->set($data);
        $_SESSION['success_message'] = 'Invigilation duty updated successfully';
    } else {
        $database->getReference('invigilation')->push($data);
        $_SESSION['success_message'] = 'Invigilation duty created successfully';
    }
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error saving invigilation: ' . $e->getMessage();
}

header('Location: manage_invigilation.php');
exit();
