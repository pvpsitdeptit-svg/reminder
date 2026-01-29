<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
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
