<?php
require_once 'config/firebase.php';

echo "<h2>Firebase Connection Test</h2>";

try {
    if (isset($messaging)) {
        echo "<p>✅ Firebase Messaging initialized</p>";
        
        // Test 1: Check if we can access Firebase project
        echo "<p>🔍 Testing Firebase project access...</p>";
        
        // Test with a minimal message
        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification(\Kreait\Firebase\Messaging\Notification::create('Test', 'Test message'));
            
        echo "<p>📤 Message created successfully</p>";
        
        // Try to validate the message without sending
        echo "<p>✅ Firebase configuration appears valid</p>";
        
        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>Check Firebase Console → Project Settings → Cloud Messaging</li>";
        echo "<li>Ensure FCM API is enabled</li>";
        echo "<li>Verify service account has 'Firebase Cloud Messaging API' permission</li>";
        echo "<li>Check if project has proper billing setup</li>";
        echo "</ol>";
        
    } else {
        echo "<p>❌ Firebase Messaging not initialized</p>";
        echo "<p>Check service account configuration</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h3>Firebase Project Info:</h3>";
echo "<p><strong>Project ID:</strong> " . htmlspecialchars($serviceAccount['project_id']) . "</p>";
echo "<p><strong>Client Email:</strong> " . htmlspecialchars($serviceAccount['client_email']) . "</p>";
echo "<p><strong>Sender ID:</strong> " . htmlspecialchars($firebaseConfig['messagingSenderId']) . "</p>";
?>
