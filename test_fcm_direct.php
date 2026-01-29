<?php
require_once 'config/firebase.php';

echo "<h2>FCM Direct Test</h2>";

if (isset($_POST['send_test']) && isset($_POST['token'])) {
    $token = $_POST['token'];
    $testEmail = $_POST['email'] ?? 'uk@gmail.com';
    
    echo "<h3>Testing FCM to Token: " . substr($token, 0, 30) . "...</h3>";
    
    if (isset($messaging)) {
        // Test 1: Simple notification
        $title = "Test FCM Message";
        $body = "This is a direct test notification";
        $data = [
            'type' => 'test_message',
            'timestamp' => time()
        ];
        
        echo "<p><strong>Test 1:</strong> Simple notification</p>";
        $result1 = sendFCMNotification($messaging, $title, $body, $data, [$token]);
        echo "<p>Result: " . ($result1 ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
        // Test 2: Leave availed notification (same format as your system)
        echo "<p><strong>Test 2:</strong> Leave availed notification</p>";
        $result2 = sendLeaveAvailedNotification($messaging, $database, $testEmail, 'CL', 1, '2026-01-13', '2026-01-13');
        echo "<p>Result: " . ($result2 ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
        // Test 3: Data-only message (silent push)
        echo "<p><strong>Test 3:</strong> Data-only message</p>";
        $data = [
            'type' => 'leave_availed',
            'faculty_email' => $testEmail,
            'leave_type' => 'CL',
            'days' => '1',
            'from_date' => '2026-01-13',
            'to_date' => '2026-01-13',
            'timestamp' => time()
        ];
        $result3 = sendFCMNotification($messaging, '', '', $data, [$token]);
        echo "<p>Result: " . ($result3 ? '✅ SUCCESS' : '❌ FAILED') . "</p>";
        
    } else {
        echo "<p style='color: red;'>❌ Firebase messaging not available</p>";
    }
}

// Get the token from database if email is provided
$token = '';
if (isset($_GET['email'])) {
    $email = $_GET['email'];
    $sanitizedEmail = str_replace('.', '_', $email);
    $sanitizedEmail = str_replace('@', '_', $sanitizedEmail);
    
    if (isset($database)) {
        $tokenSnapshot = $database->getReference('fcm_tokens/' . $sanitizedEmail)->getSnapshot();
        $token = $tokenSnapshot->getValue();
        
        if ($token) {
            echo "<p>✅ Found token for $email: " . substr($token, 0, 30) . "...</p>";
        } else {
            echo "<p style='color: red;'>❌ No token found for $email</p>";
        }
    }
}
?>

<form method="post">
    <h3>Send Test FCM Message</h3>
    
    <label>
        Token:<br>
        <input type="text" name="token" value="<?= htmlspecialchars($token) ?>" size="80" required>
    </label><br><br>
    
    <label>
        Email (for leave availed test):<br>
        <input type="email" name="email" value="uk@gmail.com" required>
    </label><br><br>
    
    <button type="submit" name="send_test">Send Test Messages</button>
</form>

<hr>

<h3>Instructions:</h3>
<ol>
    <li>Open Android app and login</li>
    <li>Check Android logs: <code>adb logcat | grep -E "(FCM|DashboardActivity|NotificationReceiver)"</code></li>
    <li>You should see test notifications appear</li>
    <li>If no notifications appear, check the logs for errors</li>
</ol>

<h3>Expected Android Logs:</h3>
<pre>
D/FCMService: Received FCM message
D/FCMService: Processing message type: leave_availed
D/NotificationReceiver: onReceive with action: FCM_LEAVE_AVAILED
</pre>
