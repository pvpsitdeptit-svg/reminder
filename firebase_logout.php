<?php
require_once 'firebase_auth.php';

// Logout user
firebaseLogout();

// Redirect to login page
header('Location: firebase_login.php');
exit();
?>
