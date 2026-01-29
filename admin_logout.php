<?php
require_once 'admin_auth.php';

// Logout admin
logoutAdmin();

// Redirect to admin login page
header('Location: admin_login.php');
exit();
?>
