<?php
session_start();

// Use the same authentication check as other pages
require_once 'firebase_auth.php';

if (!isAuthenticated()) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: firebase_login.php');
    exit();
}

require_once 'config/firebase.php';

$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
if (!$id) {
    $_SESSION['error_message'] = 'Missing template ID';
    header('Location: manage_lectures.php');
    exit();
}

try {
    $database->getReference('lecture_templates/' . $id)->set(null);
    $_SESSION['success_message'] = 'Lecture template deleted successfully';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error deleting template: ' . $e->getMessage();
}

header('Location: manage_lectures.php');
exit();
