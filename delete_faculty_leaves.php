<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

$email = isset($_GET['email']) ? strtolower(trim((string)$_GET['email'])) : '';
if (!$email) {
    $_SESSION['error_message'] = 'Missing faculty email';
    header('Location: manage_faculty_leaves.php');
    exit();
}

try {
    $key = firebaseKeyFromEmail($email);
    $database->getReference('faculty_leave_master/' . $key)->set(null);
    $_SESSION['success_message'] = 'Faculty leave master record deleted successfully';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error deleting faculty leave master: ' . $e->getMessage();
}

header('Location: manage_faculty_leaves.php');
exit();
?>
