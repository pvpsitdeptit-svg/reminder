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
