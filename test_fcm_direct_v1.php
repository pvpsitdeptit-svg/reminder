<?php
// Direct FCM v1 API test using OAuth2

echo "<h2>Direct FCM v1 API Test</h2>";

// Project info
$projectId = "reminder-c0728";
$serviceAccountEmail = "firebase-adminsdk-fbsvc@reminder-c0728.iam.gserviceaccount.com";

// Private key (same as in firebase.php) - using proper variable from config
require_once 'config/firebase.php';
$privateKey = $serviceAccount['private_key'];

// Convert private key to proper format for OpenSSL
$privateKeyResource = openssl_pkey_get_private($privateKey);
if ($privateKeyResource === false) {
    die("<p>❌ Invalid private key format</p>");
}

// Get OAuth2 token
function getAccessToken($privateKeyResource, $serviceAccountEmail) {
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $claimSet = json_encode([
        'iss' => $serviceAccountEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => time() + 3600,
        'iat' => time()
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaimSet = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claimSet));
    
    $signature = '';
    openssl_sign($base64UrlHeader . '.' . $base64UrlClaimSet, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    $jwt = $base64UrlHeader . '.' . $base64UrlClaimSet . '.' . $base64UrlSignature;
    
    $post = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    return $data['access_token'] ?? null;
}

$accessToken = getAccessToken($privateKeyResource, $serviceAccountEmail);

if ($accessToken) {
    echo "<p>✅ Got OAuth2 token</p>";
    
    // Send FCM v1 message
    $token = "dQQrev2nSwSn6DJkZCkK3s:APA91bENp-3b8E_VTZN-NU3MNJ-jCBVzeCXB_kXxE7iSV5bFEGTAXxliEtxxw6vnipofiktikpd7bCDmiyLAvbtfrngy-aZ5n72TmqLfYORIJ2mCDUHeP34"; // Update with fresh token from Android
    
    $message = [
        'message' => [
            'token' => $token,
            'notification' => [
                'title' => 'Direct v1 Test',
                'body' => 'Testing direct FCM v1 API'
            ],
            'data' => [
                'type' => 'direct_v1_test',
                'timestamp' => (string)time()
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    echo "<p><strong>Response:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($httpCode == 200) {
        echo "<p>✅ Direct FCM v1 message sent!</p>";
    } else {
        echo "<p>❌ Direct FCM v1 failed</p>";
    }
    
} else {
    echo "<p>❌ Failed to get OAuth2 token</p>";
}
?>
