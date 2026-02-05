<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

echo "<h1>Authentication Test</h1>";

echo "<h2>Current User:</h2>";
$user = getCurrentUser();
echo "<pre>" . print_r($user, true) . "</pre>";

echo "<h2>Is Authenticated:</h2>";
echo isAuthenticated() ? "Yes" : "No";

echo "<h2>Is Admin:</h2>";
echo isAdmin() ? "Yes" : "No";

echo "<h2>User Role:</h2>";
echo getUserRole();

echo "<h2>Session Data:</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Test requireFaculty
echo "<h2>Testing requireFaculty:</h2>";
try {
    requireFaculty();
    echo "requireFaculty: SUCCESS";
} catch (Exception $e) {
    echo "requireFaculty: ERROR - " . $e->getMessage();
}
?>
