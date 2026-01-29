<?php
require_once 'config/firebase.php';

echo "<h2>FCM Connectivity Test</h2>";

if (isset($messaging)) {
    echo "<p>✅ Firebase Messaging initialized</p>";
    
    // Test with a known invalid token to see the exact error
    $testToken = "invalid_token_test";
    
    try {
        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification(\Kreait\Firebase\Messaging\Notification::create('Test', 'Test message'))
            ->withData(['test' => 'data']);
            
        $report = $messaging->sendMulticast($message, [$testToken]);
        
        echo "<p>❌ Unexpected success with invalid token</p>";
        
    } catch (Exception $e) {
        echo "<p>✅ Expected error with invalid token: " . htmlspecialchars($e->getMessage()) . "</p>";
        
        // Check if it's the same error as with your real token
        if (strpos($e->getMessage(), 'NotFound') !== false) {
            echo "<p>🔍 This confirms the issue is with token validity</p>";
        }
    }
    
} else {
    echo "<p>❌ Firebase Messaging not initialized</p>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Clear app data and reinstall</li>";
echo "<li>Login again to get fresh token</li>";
echo "<li>Test with new token</li>";
echo "</ol>";
?>
