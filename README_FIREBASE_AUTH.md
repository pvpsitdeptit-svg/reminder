# Firebase Authentication Integration

## Overview
This document explains the Firebase Authentication system integrated into the Faculty Management System.

## Files Added

### 1. `firebase_auth.php`
Core authentication library with functions:
- `verifyFirebaseToken()` - Verify Firebase ID tokens
- `signInWithEmail()` - Sign in users with email/password
- `createUser()` - Create new user accounts
- `isAuthenticated()` - Check if user is logged in
- `getCurrentUser()` - Get current user details
- `firebaseLogout()` - Logout user
- `requireAuth()` - Protect pages requiring authentication

### 2. `firebase_login.php`
Beautiful login/register page with:
- Tab-based interface (Login/Register)
- Firebase Authentication integration
- Modern UI with Bootstrap 5
- Form validation
- Error handling

### 3. `firebase_logout.php`
Simple logout handler that clears session and redirects to login.

### 4. `index_firebase.php`
Protected dashboard that:
- Requires authentication to access
- Shows user information in navbar
- Includes all original dashboard functionality
- Displays user statistics

## How to Use

### 1. Setup Firebase Authentication
1. Go to Firebase Console: https://console.firebase.google.com
2. Select your project: `reminder-c0728`
3. Go to Authentication → Sign-in method
4. Enable **Email/Password** provider
5. Save settings

### 2. Update Existing Pages
To protect existing pages, add this at the top:

```php
<?php
require_once 'firebase_auth.php';
requireAuth(); // This will redirect to login if not authenticated
?>
```

### 3. Update Navigation
Replace login/logout links:
```php
<?php if (isAuthenticated()): ?>
    <a href="firebase_logout.php" class="nav-link">
        <i class="bi bi-box-arrow-right"></i> Logout
    </a>
<?php else: ?>
    <a href="firebase_login.php" class="nav-link">
        <i class="bi bi-box-arrow-in-right"></i> Login
    </a>
<?php endif; ?>
```

## Authentication Flow

### Login Process
1. User enters email/password
2. Firebase REST API validates credentials
3. If successful, receives ID token and refresh token
4. Tokens stored in PHP session
5. User redirected to dashboard

### Token Verification
1. Each page load checks session for valid token
2. Token verified with Firebase API
3. If expired, user logged out automatically
4. Fresh tokens maintain session validity

### Registration Process
1. User fills registration form
2. Firebase creates new user account
3. Success message shown
4. User can then login

## Security Features

### Token Validation
- ID tokens verified on each page load
- Automatic logout on token expiration
- Secure session management

### Password Security
- Passwords handled by Firebase (never stored locally)
- Minimum 6 character requirement
- Firebase handles password hashing

### Session Security
- PHP session for token storage
- Automatic session cleanup on logout
- Redirect protection for authenticated pages

## User Roles (Future Enhancement)

The current system supports basic authentication. To add roles:

```php
// After successful login, check user role
$userData = $database->getReference('users/' . $result['localId'])->getValue();
$_SESSION['user']['role'] = $userData['role'] ?? 'user';

// Protect admin pages
function requireAdmin() {
    requireAuth();
    if ($_SESSION['user']['role'] !== 'admin') {
        header('Location: index_firebase.php');
        exit();
    }
}
```

## Migration from Simple Auth

To migrate existing admin authentication:

1. Create admin accounts in Firebase Auth
2. Update all protected pages to use `requireAuth()`
3. Replace `$_SESSION['admin_logged_in']` with `isAuthenticated()`
4. Update navigation to use Firebase user info

## Testing

### Test Accounts
Create test accounts in Firebase Console or via registration:
- Admin: admin@test.com / password123
- User: user@test.com / password123

### Test Scenarios
1. Valid login → Dashboard access
2. Invalid credentials → Error message
3. Registration → Account creation
4. Token expiration → Automatic logout
5. Protected page access → Redirect to login

## Troubleshooting

### Common Issues

**"Invalid API key" Error**
- Check Firebase project configuration
- Verify API key in `firebase_auth.php`

**"Invalid email or password"**
- Verify Firebase Auth is enabled
- Check email/password format
- Ensure user exists in Firebase

**Token verification fails**
- Check network connectivity to Firebase
- Verify API key is correct
- Check session configuration

### Debug Mode
Add to `firebase_auth.php` for debugging:
```php
// Add after curl_exec
error_log("Firebase Response: " . $response);
error_log("HTTP Code: " . $httpCode);
```

## Benefits

### Security
- Industry-standard authentication
- No password storage in application
- Automatic token management
- Secure session handling

### User Experience
- Single sign-on capability
- Modern login interface
- Automatic session management
- Mobile-friendly design

### Scalability
- Firebase handles authentication load
- Easy user management
- Integration with other Firebase services
- Support for social login providers

## Next Steps

1. **Enable Firebase Authentication** in your project
2. **Test the login system** with test accounts
3. **Update existing pages** to use Firebase auth
4. **Consider adding user roles** for different access levels
5. **Implement social login** options (Google, Facebook, etc.)

Your Faculty Management System now has enterprise-grade authentication! 🚀
