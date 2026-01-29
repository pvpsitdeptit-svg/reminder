<?php
// Debug OAuth2 Authentication for Firebase
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Firebase OAuth2 Debug</h1>";

// Load service account from environment
$serviceAccountJson = $_ENV['FIREBASE_SERVICE_ACCOUNT'] ?? null;
if ($serviceAccountJson) {
    $serviceAccount = json_decode($serviceAccountJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "<br>❌ Invalid FIREBASE_SERVICE_ACCOUNT JSON: " . json_last_error_msg();
        exit;
    }
} else {
    echo "<br>❌ FIREBASE_SERVICE_ACCOUNT not set";
    exit;
}

echo "<h2>Service Account Details:</h2>";
echo "<br><strong>Type:</strong> " . htmlspecialchars($serviceAccount['type']);
echo "<br><strong>Project ID:</strong> " . htmlspecialchars($serviceAccount['project_id']);
echo "<br><strong>Client Email:</strong> " . htmlspecialchars($serviceAccount['client_email']);
echo "<br><strong>Private Key ID:</strong> " . htmlspecialchars($serviceAccount['private_key_id']);

// Test JWT creation
echo "<h2>JWT Token Creation:</h2>";

try {
    $privateKey = $serviceAccount['private_key'];
    $serviceAccountEmail = $serviceAccount['client_email'];
    
    echo "<br>✅ Private key loaded";
    echo "<br>✅ Service account email: " . htmlspecialchars($serviceAccountEmail);
    
    // Create JWT
    $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
    $claimSet = json_encode([
        'iss' => $serviceAccountEmail,
        'scope' => 'https://www.googleapis.com/auth/firebase.database',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => time() + 3600,
        'iat' => time()
    ]);
    
    $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64UrlClaimSet = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($claimSet));
    
    echo "<br>✅ JWT header and claims created";
    
    // Test private key
    $privateKeyResource = openssl_pkey_get_private($privateKey);
    if ($privateKeyResource === false) {
        echo "<br>❌ Failed to load private key";
        exit;
    }
    
    echo "<br>✅ Private key resource created";
    
    $signature = '';
    $result = openssl_sign($base64UrlHeader . '.' . $base64UrlClaimSet, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
    
    if ($result === false) {
        echo "<br>❌ Failed to sign JWT";
        exit;
    }
    
    echo "<br>✅ JWT signed successfully";
    
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $base64UrlHeader . '.' . $base64UrlClaimSet . '.' . $base64UrlSignature;
    
    echo "<br>✅ JWT created: " . substr($jwt, 0, 50) . "...";
    
    // Get access token
    echo "<h2>OAuth2 Token Request:</h2>";
    
    $post = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);
    
    echo "<br>✅ Token request prepared";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    echo "<br>✅ cURL configured";
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<br><strong>HTTP Code:</strong> " . $httpCode;
    echo "<br><strong>cURL Error:</strong> " . ($curlError ?: 'None');
    echo "<br><strong>Response:</strong> " . htmlspecialchars($response);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'] ?? null;
        
        if ($accessToken) {
            echo "<br>✅ Access token obtained: " . substr($accessToken, 0, 20) . "...";
            
            // Test Firebase API call
            echo "<h2>Firebase API Test:</h2>";
            
            $databaseUrl = 'https://reminder-c0728-default-rtdb.firebaseio.com';
            $testUrl = $databaseUrl . '/.info/connected.json';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $testUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $accessToken
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $apiResponse = curl_exec($ch);
            $apiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $apiCurlError = curl_error($ch);
            curl_close($ch);
            
            echo "<br><strong>API HTTP Code:</strong> " . $apiHttpCode;
            echo "<br><strong>API cURL Error:</strong> " . ($apiCurlError ?: 'None');
            echo "<br><strong>API Response:</strong> " . htmlspecialchars($apiResponse);
            
            if ($apiHttpCode === 200) {
                echo "<br>✅ Firebase API connection successful!";
            } else {
                echo "<br>❌ Firebase API connection failed";
            }
        } else {
            echo "<br>❌ No access token in response";
        }
    } else {
        echo "<br>❌ Failed to get access token";
    }
    
} catch (Exception $e) {
    echo "<br>❌ Exception: " . $e->getMessage();
    echo "<br><strong>Trace:</strong> " . $e->getTraceAsString();
}
?>
