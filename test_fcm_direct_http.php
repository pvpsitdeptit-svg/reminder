<?php
// Test FCM using HTTP API directly (bypassing service account)

$serverKey = "YOUR_ACTUAL_SERVER_KEY_HERE"; // Replace with your real server key

$fcmToken = "dQQrev2nSwSn6DJkZCkK3s:APA91bE...";

$data = [
    'to' => $fcmToken,
    'notification' => [
        'title' => 'Test Notification',
        'body' => 'This is a test message',
        'sound' => 'default'
    ],
    'data' => [
        'type' => 'test',
        'timestamp' => time()
    ]
];

$headers = [
    'Authorization: key=' . $serverKey,
    'Content-Type: application/json'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Direct FCM HTTP Test</h2>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($httpCode == 200) {
    echo "<p>✅ FCM message sent successfully!</p>";
} else {
    echo "<p>❌ FCM message failed</p>";
    echo "<p>Check server key and token validity</p>";
}
?>
