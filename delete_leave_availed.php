<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
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
