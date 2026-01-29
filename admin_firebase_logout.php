<?php
require_once 'admin_firebase_auth.php';

// Logout Firebase admin
logoutFirebaseAdmin();

// Redirect to Firebase admin login page
header('Location: admin_firebase_login.php');
exit();
?>
