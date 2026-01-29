<?php
// Debug Firebase Connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Firebase Connection Debug</h1>";

// Check environment variables
echo "<h2>Environment Variables:</h2>";
$env_vars = [
    'FIREBASE_PROJECT_ID',
    'FIREBASE_DATABASE_URL', 
    'FIREBASE_API_KEY',
    'FIREBASE_SERVICE_ACCOUNT'
];

foreach ($env_vars as $var) {
    $value = $_ENV[$var] ?? 'NOT SET';
    echo "<br><strong>$var:</strong> " . htmlspecialchars($value);
}

// Load Firebase config
echo "<h2>Loading Firebase Configuration:</h2>";
try {
    require_once 'config/firebase.php';
    echo "<br>✅ Firebase config loaded successfully";
} catch (Exception $e) {
    echo "<br>❌ Error loading Firebase config: " . $e->getMessage();
    exit;
}

// Check vendor/autoload.php
echo "<h2>Vendor Dependencies:</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<br>✅ vendor/autoload.php exists";
} else {
    echo "<br>❌ vendor/autoload.php NOT FOUND";
}

// Test Firebase connection
echo "<h2>Firebase Connection Test:</h2>";
try {
    if (isset($database) && $database) {
        echo "<br>✅ Database object created";
        
        // Test basic connection
        $testRef = $database->getReference('.info/connected');
        $snapshot = $testRef->getSnapshot();
        $connected = $snapshot->getValue();
        
        echo "<br>🔗 Firebase connected: " . ($connected ? "YES" : "NO");
        
        // Test data retrieval
        echo "<h2>Data Retrieval Test:</h2>";
        $ref = $database->getReference('faculty_leave_master');
        $snapshot = $ref->getSnapshot();
        
        if ($snapshot->exists()) {
            $data = $snapshot->getValue();
            $count = is_array($data) ? count($data) : 0;
            echo "<br>✅ Data found in faculty_leave_master: $count records";
            
            if ($count > 0) {
                echo "<br><strong>Sample record:</strong>";
                $firstKey = array_key_first($data);
                echo "<pre>" . htmlspecialchars(json_encode($data[$firstKey], JSON_PRETTY_PRINT)) . "</pre>";
            }
        } else {
            echo "<br>❌ No data found in faculty_leave_master";
        }
        
    } else {
        echo "<br>❌ Database object not created";
    }
} catch (Exception $e) {
    echo "<br>❌ Firebase connection error: " . $e->getMessage();
    echo "<br><strong>Error details:</strong> " . $e->getTraceAsString();
}

// Check if using mock mode
echo "<h2>Mock Mode Check:</h2>";
if (isset($database) && get_class($database) === 'MockDatabase') {
    echo "<br>⚠️  Using MockDatabase - Firebase dependencies not working";
} else {
    echo "<br>✅ Using real Firebase database";
}

echo "<h2>PHP Extensions:</h2>";
$required_extensions = ['curl', 'openssl', 'json'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<br>✅ $ext extension loaded";
    } else {
        echo "<br>❌ $ext extension NOT loaded";
    }
}

echo "<h2>Next Steps:</h2>";
echo "<br>1. If FIREBASE_SERVICE_ACCOUNT is not set, add it to Render environment variables";
echo "<br>2. Check if vendor directory is uploaded to Render";
echo "<br>3. Verify Firebase database rules allow read access";
echo "<br>4. Check Render error logs for detailed error messages";
?>
