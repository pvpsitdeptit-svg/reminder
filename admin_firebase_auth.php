<?php
// Firebase Admin Authentication
session_start();

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

// Admin users list (you can add more admin emails here)
$ADMIN_EMAILS = [
    'admin@reminder.com',
    'your-email@domain.com', // Add your admin email here
    // Add more admin emails as needed
];

// Function to verify Firebase ID token and check if user is admin
function verifyFirebaseAdminToken($idToken) {
    global $firebaseAPIKey, $ADMIN_EMAILS;
    
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
            $user = $result['users'][0];
            $email = $user['email'] ?? '';
            
            // Check if user's email is in admin list
            if (in_array($email, $ADMIN_EMAILS)) {
                return $user;
            }
        }
    }
    
    return false;
}

// Function to sign in with email and password using Firebase
function signInAdminWithFirebase($email, $password) {
    global $firebaseAuthAPI, $firebaseAPIKey, $ADMIN_EMAILS;
    
    // First check if email is in admin list
    if (!in_array($email, $ADMIN_EMAILS)) {
        return ['error' => 'This email is not authorized for admin access'];
    }
    
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
    } else {
        $errorData = json_decode($response, true);
        return ['error' => $errorData['error']['message'] ?? 'Login failed'];
    }
}

// Function to check if admin is authenticated
function isFirebaseAdminLoggedIn() {
    if (isset($_SESSION['firebase_admin_user']) && isset($_SESSION['firebase_admin_user']['idToken'])) {
        // Verify token is still valid and user is still admin
        $user = verifyFirebaseAdminToken($_SESSION['firebase_admin_user']['idToken']);
        if ($user) {
            return true;
        } else {
            // Token expired or user no longer admin, clear session
            unset($_SESSION['firebase_admin_user']);
            return false;
        }
    }
    return false;
}

// Function to get current admin user
function getCurrentFirebaseAdmin() {
    return $_SESSION['firebase_admin_user'] ?? null;
}

// Function to require Firebase admin authentication
function requireFirebaseAdminAuth() {
    if (!isFirebaseAdminLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: admin_firebase_login.php');
        exit();
    }
}

// Function to login Firebase admin
function loginFirebaseAdmin($email, $password) {
    $result = signInAdminWithFirebase($email, $password);
    
    if (isset($result['idToken'])) {
        // Verify the token and get user info
        $user = verifyFirebaseAdminToken($result['idToken']);
        if ($user) {
            $_SESSION['firebase_admin_user'] = [
                'uid' => $result['localId'],
                'email' => $result['email'],
                'idToken' => $result['idToken'],
                'refreshToken' => $result['refreshToken'],
                'displayName' => $result['displayName'] ?? $result['email'],
                'isAdmin' => true
            ];
            return true;
        }
    }
    
    return false;
}

// Function to logout Firebase admin
function logoutFirebaseAdmin() {
    unset($_SESSION['firebase_admin_user']);
    session_destroy();
}

// Function to add admin email (utility)
function addFirebaseAdminEmail($email) {
    global $ADMIN_EMAILS;
    if (!in_array($email, $ADMIN_EMAILS)) {
        $ADMIN_EMAILS[] = $email;
        // In production, save this to database or config file
        file_put_contents(__DIR__ . '/firebase_admin_emails.json', json_encode($ADMIN_EMAILS));
        return true;
    }
    return false;
}

// Function to remove admin email
function removeFirebaseAdminEmail($email) {
    global $ADMIN_EMAILS;
    $key = array_search($email, $ADMIN_EMAILS);
    if ($key !== false) {
        unset($ADMIN_EMAILS[$key]);
        $ADMIN_EMAILS = array_values($ADMIN_EMAILS); // Re-index array
        // In production, save this to database or config file
        file_put_contents(__DIR__ . '/firebase_admin_emails.json', json_encode($ADMIN_EMAILS));
        return true;
    }
    return false;
}
?>
