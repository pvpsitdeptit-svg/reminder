<?php
// Comprehensive Localhost Debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Localhost Firebase Debug</h1>";

// Step 1: Check vendor directory
echo "<h2>Step 1: Vendor Directory Check</h2>";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<br>✅ vendor/autoload.php exists";
    
    // Check file size
    $filesize = filesize(__DIR__ . '/vendor/autoload.php');
    echo "<br>📄 autoload.php size: " . $filesize . " bytes";
    
    if ($filesize > 1000) {
        echo "<br>✅ autoload.php looks substantial";
    } else {
        echo "<br>❌ autoload.php seems too small";
    }
} else {
    echo "<br>❌ vendor/autoload.php NOT FOUND";
    echo "<br>💡 Run 'composer install' to install dependencies";
    exit;
}

// Step 2: Try to load autoload
echo "<h2>Step 2: Autoload Test</h2>";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<br>✅ autoload.php loaded successfully";
} catch (Exception $e) {
    echo "<br>❌ Error loading autoload.php: " . $e->getMessage();
    exit;
}

// Step 3: Check Firebase SDK classes
echo "<h2>Step 3: Firebase SDK Classes</h2>";
$requiredClasses = [
    'Kreait\Firebase\Factory',
    'Kreait\Firebase\Database',
    'Firebase\Auth\Token\Cache\InMemoryCache'
];

foreach ($requiredClasses as $class) {
    if (class_exists($class)) {
        echo "<br>✅ $class - Found";
    } else {
        echo "<br>❌ $class - Missing";
    }
}

// Step 4: Check Firebase configuration
echo "<h2>Step 4: Firebase Configuration</h2>";
require_once 'config/firebase.php';

echo "<br>🔗 Database URL: " . htmlspecialchars($firebaseConfig['databaseURL']);
echo "<br>📱 Project ID: " . htmlspecialchars($firebaseConfig['projectId']);

if (isset($serviceAccount) && is_array($serviceAccount)) {
    echo "<br>✅ Service account loaded";
    echo "<br>📧 Service email: " . htmlspecialchars($serviceAccount['client_email']);
} else {
    echo "<br>❌ Service account not loaded";
}

// Step 5: Test Firebase connection
echo "<h2>Step 5: Firebase Connection Test</h2>";
try {
    if (isset($database) && $database) {
        echo "<br>✅ Database object created";
        
        // Test basic connection
        $testRef = $database->getReference('.info/connected');
        $snapshot = $testRef->getSnapshot();
        $connected = $snapshot->getValue();
        
        echo "<br>🔗 Firebase connected: " . ($connected ? "YES" : "NO");
        
        if ($connected) {
            echo "<br>✅ Firebase connection successful!";
            
            // Test data retrieval
            echo "<h2>Step 6: Data Retrieval Test</h2>";
            $ref = $database->getReference('faculty_leave_master');
            $snapshot = $ref->getSnapshot();
            
            if ($snapshot->exists()) {
                $data = $snapshot->getValue();
                $count = is_array($data) ? count($data) : 0;
                echo "<br>✅ Data found in faculty_leave_master: $count records";
                
                if ($count > 0) {
                    $firstKey = array_key_first($data);
                    echo "<br><strong>Sample record key:</strong> " . htmlspecialchars($firstKey);
                }
            } else {
                echo "<br>❌ No data found in faculty_leave_master";
                echo "<br>💡 Check if data exists in your Firebase database";
            }
        } else {
            echo "<br>❌ Firebase connection failed";
        }
        
    } else {
        echo "<br>❌ Database object not created";
    }
} catch (Exception $e) {
    echo "<br>❌ Firebase connection error: " . $e->getMessage();
    echo "<br><strong>Error details:</strong>";
    echo "<br><pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

// Step 7: Check if using mock
echo "<h2>Step 7: Implementation Type</h2>";
if (isset($database)) {
    $dbClass = get_class($database);
    echo "<br>📊 Database class: " . htmlspecialchars($dbClass);
    
    if ($dbClass === 'MockDatabase') {
        echo "<br>⚠️  Using MockDatabase - Firebase not working";
    } elseif (strpos($dbClass, 'Firebase') !== false) {
        echo "<br>✅ Using Firebase SDK";
    } elseif (strpos($dbClass, 'Simple') !== false) {
        echo "<br>🔧 Using Simple Firebase implementation";
    } else {
        echo "<br>❓ Unknown implementation";
    }
} else {
    echo "<br>❌ No database object found";
}

// Step 8: Environment check
echo "<h2>Step 8: Environment Check</h2>";
echo "<br>🐘 PHP Version: " . PHP_VERSION;
echo "<br>📁 Working directory: " . __DIR__;
echo "<br>🌐 Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown');

// Check required extensions
$extensions = ['curl', 'openssl', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "<br>$status $ext extension";
}

echo "<h2>Recommendations:</h2>";
echo "<br>1. If vendor/autoload.php is missing, run: composer install";
echo "<br>2. If Firebase classes are missing, run: composer require kreait/firebase-php";
echo "<br>3. If connection fails, check Firebase database rules";
echo "<br>4. If no data found, verify data exists in Firebase console";
?>
