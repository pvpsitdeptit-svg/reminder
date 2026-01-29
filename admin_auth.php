<?php
session_start();

// Admin credentials (you can change these)
$ADMIN_CREDENTIALS = [
    'username' => 'admin',
    'password' => 'admin123',
    'email' => 'admin@reminder.com',
    'name' => 'System Administrator'
];

// Alternative: You can add multiple admins
$ADMIN_USERS = [
    'admin' => [
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'email' => 'admin@reminder.com',
        'name' => 'System Administrator',
        'role' => 'super_admin'
    ],
    'admin@reminder.com' => [
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'name' => 'System Administrator',
        'role' => 'super_admin'
    ],
    // Add more admins like this:
    // 'dean' => [
    //     'password' => password_hash('dean123', PASSWORD_DEFAULT),
    //     'email' => 'dean@university.edu',
    //     'name' => 'Dean',
    //     'role' => 'admin'
    // ]
];

// Function to verify admin credentials (accepts both username and email)
function verifyAdmin($login, $password) {
    global $ADMIN_USERS, $ADMIN_CREDENTIALS;
    
    // First check the simple credentials (backward compatibility)
    if (($login === $ADMIN_CREDENTIALS['username'] || $login === $ADMIN_CREDENTIALS['email']) 
        && $password === $ADMIN_CREDENTIALS['password']) {
        return true;
    }
    
    // Then check hashed passwords
    if (isset($ADMIN_USERS[$login])) {
        return password_verify($password, $ADMIN_USERS[$login]['password']);
    }
    
    return false;
}

// Function to get admin user info
function getAdminUser($login) {
    global $ADMIN_USERS, $ADMIN_CREDENTIALS;
    
    if (isset($ADMIN_USERS[$login])) {
        return [
            'login' => $login,
            'email' => $ADMIN_USERS[$login]['email'] ?? $login,
            'name' => $ADMIN_USERS[$login]['name'],
            'role' => $ADMIN_USERS[$login]['role']
        ];
    }
    
    // Fallback
    if ($login === $ADMIN_CREDENTIALS['username'] || $login === $ADMIN_CREDENTIALS['email']) {
        return [
            'login' => $ADMIN_CREDENTIALS['username'],
            'email' => $ADMIN_CREDENTIALS['email'],
            'name' => $ADMIN_CREDENTIALS['name'],
            'role' => 'super_admin'
        ];
    }
    
    return null;
}

// Function to check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Function to get current admin
function getCurrentAdmin() {
    return $_SESSION['admin_user'] ?? null;
}

// Function to require admin authentication
function requireAdminAuth() {
    if (!isAdminLoggedIn()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: login.php'); // Use original login.php for compatibility
        exit();
    }
}

// Function to login admin
function loginAdmin($login, $password) {
    if (verifyAdmin($login, $password)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = getAdminUser($login);
        $_SESSION['username'] = getAdminUser($login)['login']; // For compatibility
        $_SESSION['admin_email'] = getAdminUser($login)['email'];
        return true;
    }
    return false;
}

// Function to logout admin
function logoutAdmin() {
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    unset($_SESSION['username']);
    unset($_SESSION['admin_email']);
    session_destroy();
}

// Function to hash password (for creating new admins)
function hashAdminPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to add new admin (utility function)
function addAdmin($login, $email, $password, $name, $role = 'admin') {
    global $ADMIN_USERS;
    $ADMIN_USERS[$login] = [
        'password' => hashAdminPassword($password),
        'email' => $email,
        'name' => $name,
        'role' => $role
    ];
    
    // Also add email as key for email login
    $ADMIN_USERS[$email] = [
        'password' => hashAdminPassword($password),
        'name' => $name,
        'role' => $role
    ];
    
    // In a real application, you'd save this to a database or file
    file_put_contents(__DIR__ . '/admin_users.json', json_encode($ADMIN_USERS));
}
?>
