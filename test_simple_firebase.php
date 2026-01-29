<?php
// Test Simple Firebase Implementation OAuth2
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple Firebase OAuth2 Test</h1>";

// Load service account from environment (like in production)
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

echo "<br>✅ Service account loaded";

// Test JWT creation and OAuth2
try {
    $privateKey = $serviceAccount['private_key'];
    $serviceAccountEmail = $serviceAccount['client_email'];
    
    echo "<br>✅ Private key and email extracted";
    
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
    
    // Test private key
    $privateKeyResource = openssl_pkey_get_private($privateKey);
    if ($privateKeyResource === false) {
        echo "<br>❌ Failed to load private key";
        echo "<br><strong>OpenSSL Error:</strong> " . openssl_error_string();
        exit;
    }
    
    echo "<br>✅ Private key resource created";
    
    $signature = '';
    $result = openssl_sign($base64UrlHeader . '.' . $base64UrlClaimSet, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
    
    if ($result === false) {
        echo "<br>❌ Failed to sign JWT";
        echo "<br><strong>OpenSSL Error:</strong> " . openssl_error_string();
        exit;
    }
    
    echo "<br>✅ JWT signed successfully";
    
    $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    $jwt = $base64UrlHeader . '.' . $base64UrlClaimSet . '.' . $base64UrlSignature;
    
    echo "<br>✅ JWT created (length: " . strlen($jwt) . ")";
    
    // Get access token
    $post = http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt
    ]);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    echo "<br><strong>OAuth2 Response:</strong>";
    echo "<br>HTTP Code: " . $httpCode;
    echo "<br>cURL Error: " . ($curlError ?: 'None');
    echo "<br>Response: " . htmlspecialchars($response);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'] ?? null;
        
        if ($accessToken) {
            echo "<br>✅ Access token obtained: " . substr($accessToken, 0, 30) . "...";
            
            // Test Firebase API call
            $databaseUrl = 'https://reminder-c0728-default-rtdb.firebaseio.com';
            $testUrl = $databaseUrl . '/faculty_leave_master.json';
            
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
            
            echo "<br><strong>Firebase API Test:</strong>";
            echo "<br>API HTTP Code: " . $apiHttpCode;
            echo "<br>API cURL Error: " . ($apiCurlError ?: 'None');
            echo "<br>API Response: " . htmlspecialchars(substr($apiResponse, 0, 500));
            
            if ($apiHttpCode === 200) {
                $data = json_decode($apiResponse, true);
                $count = is_array($data) ? count($data) : 0;
                echo "<br>✅ Firebase data retrieved: $count records";
            } else {
                echo "<br>❌ Firebase API failed";
            }
        } else {
            echo "<br>❌ No access token in response";
        }
    } else {
        echo "<br>❌ OAuth2 token request failed";
    }
    
} catch (Exception $e) {
    echo "<br>❌ Exception: " . $e->getMessage();
    echo "<br><strong>Trace:</strong> " . $e->getTraceAsString();
}
?>
