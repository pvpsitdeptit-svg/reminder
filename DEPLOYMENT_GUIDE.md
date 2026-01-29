# Render Deployment Guide for FMS

## 🔧 What's Missing for Production

The issue is that Firebase configuration needs to be properly set up for production deployment on Render.

## 📋 Required Steps for Render

### 1. Environment Variables

Add these environment variables in your Render dashboard:

```bash
# Firebase Configuration
FIREBASE_PROJECT_ID=reminder-c0728
FIREBASE_DATABASE_URL=https://reminder-c0728-default-rtdb.firebaseio.com/
FIREBASE_API_KEY=AIzaSyCpm5OhNvWksaGGot76Bwr9EpYb1CH4FvY
FIREBASE_AUTH_DOMAIN=reminder-c0728.firebaseapp.com
FIREBASE_STORAGE_BUCKET=reminder-c0728.firebasestorage.app
FIREBASE_MESSAGING_SENDER_ID=987181259638
FIREBASE_APP_ID=1:987181259638:android:283e678c075059f9d7b857

# Firebase Service Account (JSON format as single line)
FIREBASE_SERVICE_ACCOUNT={"type":"service_account","project_id":"reminder-c0728","private_key_id":"c3963d6947a947c3f0627bf29f460f83a285ff8e","private_key":"-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDNjXPGnNQFt2+1\nyGI6amOjqokx+wJGugJlUkEQb3IRPIkofj+UFd5DYULs4LLZpwNmY/eTCC74QO34\n872RHvYtObJ2bNKWoq0pdXr2mCd12uHM7VYBXH7+Lz6pRpHOTQtFUsRwVw+1Okju\nG0f3FVP4x1DkK8y8IotMrbtvyW6jJqlNeYWhD82dmzIcAHtQLw+I25jq6oli7uAV\nw1eMWxZVbuDqjtp8+bc1RxMM+l9Yn9yarFhoZoMpYX+kiIsAvmNQ/hh8m8KpxWEV\nSrD4tdXFiCqNe+ViZYhRR9xc6UuMqdDNO6f0EsCnG+alyE9mGqUOn7ZO1Idqi5BA\n0lrpXKo1AgMBAAECggEAB9sQwlXFYfU2wb3Hc0VpoBxTuKNH3P1y5f2/cCoHiVJv\nniqPsiXRJBttfAbCZGsWSC194r1R/jmNCWdXUVqGPLLod8+E5NjmwUo0yYPbI41F\nSphaIccv20sMRq/ka/v/IvEc13ucUBLK2BQW1gHsqaSvGhP1HnjmK1ILHc9Bo96J\ngoKSc0BpEN2D+58idOTwRJ3EkI3lkkZpUL/fiIrXVIQDVkvPXp0L+wqr4XYUYa/O\nFEatF6JxGIKDFcaGPDJZoGTiElL8MNNsrcfp9K0tFeSOWe0llOvwL2FCE8ZxWPuP\n2Xf8fXg4s4zti9lugIiasG+E4pyQAaeWD53QU4FagQKBgQDtdC7yJcw8b/1AdLC+\nLw/tbZiJgZx2F/wTnr+D/yHFoz79YEquA/tTgF1u3HfpN5EypFcN+PqH9U6rDFV6\nZYYgzIBiOwKBwPzGhfJJt+6M+kQ/DAeiiGGzQz0l7FSSfvoytaQkrbbDCtHQ4W66\n7kNhL0FdbipYpASlAMT3UGvmsQKBgQDdm2mMr4HQauIQm+LYCqEXugX0ZSthKJuf\nPOgiAX/rtgB6kt7z36MofASHwwN3LNOK81NyiaKN8lvq2MR5MeJsEx9+3ypWh2Is\nL+O1E3x91WL3cuxw+ZzUGjpC5+h84HwWUjVlas8ycSB52kJO+Cuzg9YAWd+KeBJt\n9H+Yv2dkxQKBgEPXQeJk8ikCRfS4Yha0E3TeLwp6QV1sFNT2MflgVyHENibl7/Av\nqwp8TjVyP8Ad5Bn34fdX/xwA9ezgpTtG7j9IrhVijqDLpmyBsGtnZXxZtE3e/f9t\nv5wbxcij8LW6GXmLc84W43RuDuwCvEQj9pQ5kA9Ffku88KbDxYJzM6DBAoGAIoD8\nIieBcs3xfNyIqVKeWm9gVfkak/oaoOR+0CyjmjOwR2VuyVHcuYT1v52hgIC+Pzg7\nme3MHYXKwfoWPTiDJIilsr9UfDyAEJk0PxFVpNIAor6GCeEThgK/Z4NsM2VQbLlI\nDw5eTGBIyjAtetYxF7ZDL7LOl2SymeQjqcjDdHECgYAYlYu13zUlYnUfK5Tx8UXG\nnYV3LNdBmFnMp8nkm/Z8jo9IfKK8WypQIIGDgbrvNiW+1LnNhmUqbcBblSAkB7ik\nVCsfOCV6nV7P/pHXtxeW3qn5iW/GAvgEkXp0ViNhQ3EW4P6HdSr1bPrShwZ0qgs1\nDonICKNWoLnnuMpBXXoU+w==\n-----END PRIVATE KEY-----\n","client_email":"firebase-adminsdk-fbsvc@reminder-c0728.iam.gserviceaccount.com","client_id":"108955683686626709101","auth_uri":"https://accounts.google.com/o/oauth2/auth","token_uri":"https://oauth2.googleapis.com/token"}

# Application Environment
APP_ENV=production
```

### 2. Update config/firebase.php for Production

Replace the hardcoded config in `config/firebase.php` with environment variable support:

```php
<?php
// Firebase configuration
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Use environment variables or fallback to hardcoded values
$firebaseConfig = [
    'apiKey' => $_ENV['FIREBASE_API_KEY'] ?? 'AIzaSyCpm5OhNvWksaGGot76Bwr9EpYb1CH4FvY',
    'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'] ?? 'reminder-c0728.firebaseapp.com',
    'databaseURL' => $_ENV['FIREBASE_DATABASE_URL'] ?? 'https://reminder-c0728-default-rtdb.firebaseio.com/',
    'projectId' => $_ENV['FIREBASE_PROJECT_ID'] ?? 'reminder-c0728',
    'storageBucket' => $_ENV['FIREBASE_STORAGE_BUCKET'] ?? 'reminder-c0728.firebasestorage.app',
    'messagingSenderId' => $_ENV['FIREBASE_MESSAGING_SENDER_ID'] ?? '987181259638',
    'appId' => $_ENV['FIREBASE_APP_ID'] ?? '1:987181259638:android:283e678c075059f9d7b857'
];

// Service account from environment
$serviceAccountJson = $_ENV['FIREBASE_SERVICE_ACCOUNT'] ?? null;
if ($serviceAccountJson) {
    $serviceAccount = json_decode($serviceAccountJson, true);
} else {
    // Fallback to hardcoded values
    $serviceAccount = [
        'type' => 'service_account',
        'project_id' => 'reminder-c0728',
        'private_key_id' => 'c3963d6947a947c3f0627bf29f460f83a285ff8e',
        'private_key' => '-----BEGIN PRIVATE KEY-----...' // (rest of the key)
    ];
}
```

### 3. Render Build Process

Ensure your `render.yaml` or build settings include:

```yaml
build:
  commands:
    - composer install --no-dev --optimize-autoloader
```

### 4. File Permissions

Make sure these files are uploaded to Render:
- ✅ `vendor/` directory (with all Firebase dependencies)
- ✅ `config/firebase.php`
- ✅ All PHP files including `includes/header.php` and `includes/footer.php`

## 🔍 Debugging Steps

### 1. Check Firebase Connection

Add this test script to verify Firebase connection:

```php
<?php
// test_firebase.php
require_once 'config/firebase.php';

try {
    $testRef = $database->getReference('.info/connected');
    $snapshot = $testRef->getSnapshot();
    $connected = $snapshot->getValue();
    
    echo "Firebase Connection Status: " . ($connected ? "Connected" : "Not Connected");
    echo "<br>Database URL: " . $firebaseConfig['databaseURL'];
    echo "<br>Project ID: " . $firebaseConfig['projectId'];
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

### 2. Check Error Logs

Look for these errors in Render logs:
- `vendor/autoload.php not found`
- `Firebase authentication failed`
- `Database connection refused`

### 3. Verify Data Retrieval

Test data retrieval:

```php
<?php
// test_data.php
require_once 'config/firebase.php';

try {
    $ref = $database->getReference('faculty_leave_master');
    $snapshot = $ref->getSnapshot();
    
    if ($snapshot->exists()) {
        $data = $snapshot->getValue();
        echo "Data found! Count: " . count($data);
    } else {
        echo "No data found in faculty_leave_master";
    }
} catch (Exception $e) {
    echo "Error retrieving data: " . $e->getMessage();
}
?>
```

## 🚀 Quick Fix

If you need a quick solution, temporarily modify `config/firebase.php` to use hardcoded values (already done) and ensure:

1. **Vendor directory uploaded** - Check that `vendor/autoload.php` exists on Render
2. **Firebase rules** - Ensure your Firebase database allows read access
3. **PHP extensions** - Ensure `curl`, `openssl`, and `json` extensions are enabled

## 📞 Support

If issues persist:
1. Check Render build logs for vendor installation errors
2. Verify Firebase database rules allow read access
3. Test with the debugging scripts above
4. Check that all files are properly deployed to Render
