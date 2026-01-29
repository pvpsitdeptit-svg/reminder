<?php
session_start();
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
$_SESSION['success_message'] = 'Session cleared successfully!';
header('Location: manage_leave_availed.php');
exit();
?>
