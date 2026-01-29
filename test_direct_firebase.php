<?php
// Direct Firebase test without environment variables
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Direct Firebase Test (No Environment Variables)</h1>";

// Use hardcoded Firebase config (same as in firebase.php)
$firebaseConfig = [
    'apiKey' => 'AIzaSyCpm5OhNvWksaGGot76Bwr9EpYb1CH4FvY',
    'authDomain' => 'reminder-c0728.firebaseapp.com',
    'databaseURL' => 'https://reminder-c0728-default-rtdb.firebaseio.com/',
    'projectId' => 'reminder-c0728',
    'storageBucket' => 'reminder-c0728.firebasestorage.app',
    'messagingSenderId' => '987181259638',
    'appId' => '1:987181259638:android:283e678c075059f9d7b857'
];

$serviceAccount = [
    'type' => 'service_account',
    'project_id' => 'reminder-c0728',
    'private_key_id' => 'c3963d6947a947c3f0627bf29f460f83a285ff8e',
    'private_key' => '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDNjXPGnNQFt2+1
yGI6amOjqokx+wJGugJlUkEQb3IRPIkofj+UFd5DYULs4LLZpwNmY/eTCC74QO34
872RHvYtObJ2bNKWoq0pdXr2mCd12uHM7VYBXH7+Lz6pRpHOTQtFUsRwVw+1Okju
G0f3FVP4x1DkK8y8IotMrbtvyW6jJqlNeYWhD82dmzIcAHtQLw+I25jq6oli7uAV
w1eMWxZVbuDqjtp8+bc1RxMM+l9Yn9yarFhoZoMpYX+kiIsAvmNQ/hh8m8KpxWEV
SrD4tdXFiCqNe+ViZYhRR9xc6UuMqdDNO6f0EsCnG+alyE9mGqUOn7ZO1Idqi5BA
0lrpXKo1AgMBAAECggEAB9sQwlXFYfU2wb3Hc0VpoBxTuKNH3P1y5f2/cCoHiVJv
niqPsiXRJBttfAbCZGsWSC194r1R/jmNCWdXUVqGPLLod8+E5NjmwUo0yYPbI41F
SphaIccv20sMRq/ka/v/IvEc13ucUBLK2BQW1gHsqaSvGhP1HnjmK1ILHc9Bo96J
goKSc0BpEN2D+58idOTwRJ3EkI3lkkZpUL/fiIrXVIQDVkvPXp0L+wqr4XYUYa/O
FEatF6JxGIKDFcaGPDJZoGTiElL8MNNsrcfp9K0tFeSOWe0llOvwL2FCE8ZxWPuP
2Xf8fXg4s4zti9lugIiasG+E4pyQAaeWD53QU4FagQKBgQDtdC7yJcw8b/1AdLC+
Lw/tbZiJgZx2F/wTnr+D/yHFoz79YEquA/tTgF1u3HfpN5EypFcN+PqH9U6rDFV6
ZYYgzIBiOwKBwPzGhfJJt+6M+kQ/DAeiiGGzQz0l7FSSfvoytaQkrbbDCtHQ4W66
7kNhL0FdbipYpASlAMT3UGvmsQKBgQDdm2mMr4HQauIQm+LYCqEXugX0ZSthKJuf
POgiAX/rtgB6kt7z36MofASHwwN3LNOK81NyiaKN8lvq2MR5MeJsEx9+3ypWh2Is
L+O1E3x91WL3cuxw+ZzUGjpC5+h84HwWUjVlas8ycSB52kJO+Cuzg9YAWd+KeBJt
9H+Yv2dkxQKBgEPXQeJk8ikCRfS4Yha0E3TeLwp6QV1sFNT2MflgVyHENibl7/Av
qwp8TjVyP8Ad5Bn34fdX/xwA9ezgpTtG7j9IrhVijqDLpmyBsGtnZXxZtE3e/f9t
v5wbxcij8LW6GXmLc84W43RuDuwCvEQj9pQ5kA9Ffku88KbDxYJzM6DBAoGAIoD8
IieBcs3xfNyIqVKeWm9gVfkak/oaoOR+0CyjmjOwR2VuyVHcuYT1v52hgIC+Pzg7
me3MHYXKwfoWPTiDJIilsr9UfDyAEJk0PxFVpNIAor6GCeEThgK/Z4NsM2VQbLlI
Dw5eTGBIyjAtetYxF7ZDL7LOl2SymeQjqcjDdHECgYAYlYu13zUlYnUfK5Tx8UXG
nYV3LNdBmFnMp8nkm/Z8jo9IfKK8WypQIIGDgbrvNiW+1LnNhmUqbcBblSAkB7ik
VCsfOCV6nV7P/pHXtxeW3qn5iW/GAvgEkXp0ViNhQ3EW4P6HdSr1bPrShwZ0qgs1
DonICKNWoLnnuMpBXXoU+w==
-----END PRIVATE KEY-----',
    'client_email' => 'firebase-adminsdk-fbsvc@reminder-c0728.iam.gserviceaccount.com',
    'client_id' => '108955683686626709101',
    'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
    'token_uri' => 'https://oauth2.googleapis.com/token'
];

echo "<h2>Step 1: Load Firebase SDK</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<br>✅ Firebase SDK loaded";
} else {
    echo "<br>❌ Firebase SDK not found";
    exit;
}

echo "<h2>Step 2: Initialize Firebase</h2>";
try {
    $factory = new \Kreait\Firebase\Factory();
    $factory = $factory
        ->withServiceAccount($serviceAccount)
        ->withDatabaseUri($firebaseConfig['databaseURL']);
    
    $database = $factory->createDatabase();
    echo "<br>✅ Firebase initialized";
} catch (Exception $e) {
    echo "<br>❌ Firebase initialization failed: " . $e->getMessage();
    exit;
}

echo "<h2>Step 3: Test Connection</h2>";
try {
    $testRef = $database->getReference('.info/connected');
    $snapshot = $testRef->getSnapshot();
    $connected = $snapshot->getValue();
    
    echo "<br>🔗 Firebase connected: " . ($connected ? "YES" : "NO");
    
    if ($connected) {
        echo "<br>✅ Connection successful!";
        
        echo "<h2>Step 4: Test Data Retrieval</h2>";
        $ref = $database->getReference('faculty_leave_master');
        $snapshot = $ref->getSnapshot();
        
        if ($snapshot->exists()) {
            $data = $snapshot->getValue();
            $count = is_array($data) ? count($data) : 0;
            echo "<br>✅ Data found: $count records";
            
            if ($count > 0) {
                $firstKey = array_key_first($data);
                $firstRecord = $data[$firstKey];
                echo "<br><strong>Sample record:</strong>";
                echo "<pre>" . htmlspecialchars(json_encode($firstRecord, JSON_PRETTY_PRINT)) . "</pre>";
            }
        } else {
            echo "<br>❌ No data found in faculty_leave_master";
            
            // Try to list what's available
            echo "<h2>Step 5: List Available Nodes</h2>";
            $rootRef = $database->getReference('/');
            $rootSnapshot = $rootRef->getSnapshot();
            if ($rootSnapshot->exists()) {
                $rootData = $rootSnapshot->getValue();
                echo "<br>📁 Available nodes: " . implode(', ', array_keys($rootData));
            } else {
                echo "<br>❌ No data found at root level";
            }
        }
    } else {
        echo "<br>❌ Connection failed";
    }
} catch (Exception $e) {
    echo "<br>❌ Connection test failed: " . $e->getMessage();
    echo "<br><strong>Stack trace:</strong>";
    echo "<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
