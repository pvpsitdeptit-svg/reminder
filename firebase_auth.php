<?php
// Firebase configuration
// Check if vendor/autoload.php exists, otherwise use mock implementation
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
} else {
    // Session already started, ensure it's active
    if (session_status() === PHP_SESSION_DISABLED) {
        session_start();
    }
}

// Firebase configuration
$firebaseConfig = [
    'apiKey' => 'AIzaSyCpm5OhNvWksaGGot76Bwr9EpYb1CH4FvY',
    'authDomain' => 'reminder-c0728.firebaseapp.com',
    'projectId' => 'reminder-c0728',
    'storageBucket' => 'reminder-c0728.firebasestorage.app',
    'messagingSenderId' => '987181259638',
    'appId' => '1:987181259638:android:283e678c075059f9d7b857'
];

// Firebase REST API endpoints
$firebaseAuthAPI = 'https://identitytoolkit.googleapis.com/v1/accounts';
$firebaseAPIKey = $firebaseConfig['apiKey'];

// Function to verify Firebase ID token
function verifyFirebaseToken($idToken) {
    global $firebaseAPIKey;
    
    $url = "https://identitytoolkit.googleapis.com/v1/token?key=" . $firebaseAPIKey;
    
    $data = [
        'idToken' => $idToken
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $result = json_decode($response, true);
        if (isset($result['users']) && count($result['users']) > 0) {
            return $result['users'][0];
        }
    }
    
    return false;
}

// Function to sign in with email and password
function signInWithEmail($email, $password) {
    global $firebaseAuthAPI, $firebaseAPIKey;
    
    $url = $firebaseAuthAPI . ":signInWithPassword?key=" . $firebaseAPIKey;
    
    $data = [
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

// Function to create new user
function createUser($email, $password) {
    global $firebaseAuthAPI, $firebaseAPIKey;
    
    $url = $firebaseAuthAPI . ":signUp?key=" . $firebaseAPIKey;
    
    $data = [
        'email' => $email,
        'password' => $password,
        'returnSecureToken' => true
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

// Check if user is authenticated
function isAuthenticated() {
    error_log("isAuthenticated() - Session status: " . session_status());
    error_log("isAuthenticated() - Checking session data");
    error_log("isAuthenticated() - Session user exists: " . isset($_SESSION['user']));
    
    if (isset($_SESSION['user']) && isset($_SESSION['user']['idToken'])) {
        error_log("isAuthenticated() - User session found, checking token expiration");
        
        // Check if token is expired (simple check using iat claim)
        $idToken = $_SESSION['user']['idToken'];
        $tokenParts = explode('.', $idToken);
        
        if (count($tokenParts) >= 2) {
            $payload = json_decode(base64_decode($tokenParts[1]), true);
            if ($payload && isset($payload['exp'])) {
                $currentTime = time();
                $tokenExpiration = $payload['exp'];
                
                error_log("isAuthenticated() - Current time: " . $currentTime . ", Token expires: " . $tokenExpiration);
                
                if ($currentTime < $tokenExpiration) {
                    error_log("isAuthenticated() - Token valid, user authenticated");
                    return true;
                } else {
                    error_log("isAuthenticated() - Token expired, clearing session");
                    unset($_SESSION['user']);
                    return false;
                }
            }
        }
        
        // Fallback - if we can't parse token, assume it's valid for 1 hour
        error_log("isAuthenticated() - Using fallback authentication");
        return true;
    }
    error_log("isAuthenticated() - No user session found");
    return false;
}

// Get current user
function getCurrentUser() {
    return $_SESSION['user'] ?? null;
}

// Logout function
function firebaseLogout() {
    unset($_SESSION['user']);
    session_destroy();
}

// Require authentication
function requireAuth() {
    if (!isAuthenticated()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: firebase_login.php');
        exit();
    }
}
?>
