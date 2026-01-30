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
if ($id === '') {
    $_SESSION['error_message'] = 'Missing entry ID';
    header('Location: manage_leave_availed.php');
    exit();
}

try {
    $database->getReference('leave_ledger/' . $id)->set(null);
    $_SESSION['success_message'] = 'Availed leave entry deleted';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error deleting entry: ' . $e->getMessage();
}

header('Location: manage_leave_availed.php');
exit();
?>
