<?php
require_once 'config/firebase.php';

echo "<h2>FCM Token Debug Tool</h2>";

if (isset($database)) {
    echo "<p><strong>Database connection:</strong> ✅ Connected</p>";
    
    // Check if fcm_tokens node exists
    try {
        $fcmTokensRef = $database->getReference('fcm_tokens')->getSnapshot();
        $allTokens = $fcmTokensRef->getValue();
        
        echo "<h3>All FCM Tokens in Database:</h3>";
        if ($allTokens) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Sanitized Email</th><th>Token (first 50 chars)</th><th>Full Token Length</th></tr>";
            
            foreach ($allTokens as $email => $token) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($email) . "</td>";
                echo "<td>" . htmlspecialchars(substr($token, 0, 50)) . "...</td>";
                echo "<td>" . strlen($token) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ No FCM tokens found in database</p>";
        }
        
        // Test specific email
        if (isset($_GET['email'])) {
            $testEmail = $_GET['email'];
            $sanitizedEmail = str_replace('.', '_', $testEmail);
            $sanitizedEmail = str_replace('@', '_', $sanitizedEmail);
            
            echo "<h3>Test for Email: " . htmlspecialchars($testEmail) . "</h3>";
            echo "<p>Sanitized: " . htmlspecialchars($sanitizedEmail) . "</p>";
            
            $tokenSnapshot = $database->getReference('fcm_tokens/' . $sanitizedEmail)->getSnapshot();
            $token = $tokenSnapshot->getValue();
            
            if ($token) {
                echo "<p style='color: green;'>✅ Token found!</p>";
                echo "<p>Token length: " . strlen($token) . "</p>";
                echo "<p>Token preview: " . htmlspecialchars(substr($token, 0, 100)) . "...</p>";
            } else {
                echo "<p style='color: red;'>❌ No token found for this email</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error accessing database: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
}

?>

<form method="get">
    <h3>Test Specific Email:</h3>
    <input type="email" name="email" placeholder="Enter email to test" required>
    <button type="submit">Test</button>
</form>
