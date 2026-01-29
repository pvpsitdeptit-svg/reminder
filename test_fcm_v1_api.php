<?php
// Test FCM using v1 API (the correct way)

require_once 'config/firebase.php';

echo "<h2>FCM v1 API Test</h2>";

try {
    if (isset($messaging)) {
        echo "<p>✅ Firebase Messaging initialized</p>";
        
        // Use your current FCM token
        $token = "dQQrev2nSwSn6DJkZCkK3s:APA91bE...";
        
        // Create message using v1 API format
        $message = \Kreait\Firebase\Messaging\CloudMessage::new()
            ->withNotification(\Kreait\Firebase\Messaging\Notification::create('Test v1', 'Testing FCM v1 API'))
            ->withData([
                'type' => 'test_v1',
                'timestamp' => (string)time(),
                'source' => 'php_test'
            ]);
        
        // Send to specific token using v1 API
        $report = $messaging->sendMulticast($message, [$token]);
        
        $successCount = $report->successes()->count();
        $failureCount = $report->failures()->count();
        
        echo "<p>📊 Results: {$successCount} success, {$failureCount} failures</p>";
        
        if ($successCount > 0) {
            echo "<p>✅ FCM v1 message sent successfully!</p>";
            foreach ($report->successes() as $success) {
                echo "<p>Message ID: " . htmlspecialchars($success->messageId()) . "</p>";
            }
        }
        
        if ($failureCount > 0) {
            echo "<p>❌ Some messages failed:</p>";
            foreach ($report->failures() as $failure) {
                echo "<p>Error: " . htmlspecialchars($failure->error()->getMessage()) . "</p>";
            }
        }
        
    } else {
        echo "<p>❌ Firebase Messaging not initialized</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If successful, check Android logs for FCM reception</li>";
echo "<li>If failed, check service account permissions</li>";
echo "<li>Monitor: adb logcat | grep FCMService</li>";
echo "</ol>";
?>
