<?php
// Simple Firebase implementation using HTTP requests
// This avoids SDK dependency issues

// Firebase configuration
$firebaseConfig = [
    'apiKey' => $_ENV['FIREBASE_API_KEY'] ?? 'AIzaSyCpm5OhNvWksaGGot76Bwr9EpYb1CH4FvY',
    'authDomain' => $_ENV['FIREBASE_AUTH_DOMAIN'] ?? 'reminder-c0728.firebaseapp.com',
    'databaseURL' => $_ENV['FIREBASE_DATABASE_URL'] ?? 'https://reminder-c0728-default-rtdb.firebaseio.com/',
    'projectId' => $_ENV['FIREBASE_PROJECT_ID'] ?? 'reminder-c0728',
    'storageBucket' => $_ENV['FIREBASE_STORAGE_BUCKET'] ?? 'reminder-c0728.firebasestorage.app',
    'messagingSenderId' => $_ENV['FIREBASE_MESSAGING_SENDER_ID'] ?? '987181259638',
    'appId' => $_ENV['FIREBASE_APP_ID'] ?? '1:987181259638:android:283e678c075059f9d7b857'
];

// Service account configuration
$serviceAccountJson = $_ENV['FIREBASE_SERVICE_ACCOUNT'] ?? null;
if ($serviceAccountJson) {
    $serviceAccount = json_decode($serviceAccountJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Invalid FIREBASE_SERVICE_ACCOUNT JSON: " . json_last_error_msg());
        $serviceAccount = null;
    }
} else {
    // Fallback to hardcoded values
    $serviceAccount = [
        'type' => 'service_account',
        'project_id' => 'reminder-c0728',
        'private_key_id' => 'c3963d6947a947c3f0627bf29f460f83a285ff8e',
        'private_key' => '-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDNjXPGnNQFt2+1
yGI6amOjqokx+wJGugJlUkEQb3IRPIkofj+UFd5DYULs4LLZpwNmY/eTCC74QO34
872RHvYtObJ2bNKWoq0pdXr2mCd12uHM7VYBXH7+Lz6pRpHOTQtFUsRwVw+1Okju
G0f3FVP4x1DkK8y8IotMrbtvyW6jJqlNeYWhD82dmzIcAHtQLw+I25jq6oli7uAV
w1eMWxZVbuDqjtp8+bc1RxMM+l9Yn9yarFhoZoMpYX+kiIsAvmNQ/hh8m8KpxWEV
SrD4tdXFiCqNe+ViZYhRR9xc6UuMqdDNO6f0EsCnG+alyE9mGqUOn7ZO1Idqi5BA
0lrpXKo1AgMBAAECggEAB9sQwlXFYfU2wb3Hc0VpoBxTuKNH3P1y5f2/cCoHiVJv
niqPsiXRJBttfAbCZGsWSC194r1R/jmNCWdXUVqGPLLod8+E5NjmwUo0yYPbI41F
SphaIccv20sMRq/ka/v/IvEc13ucUBLK2BQW1gHsqaSvGhP1HnjmK1ILHc9Bo96J
goKSc0BpEN2D+58idOTwRJ3EkI3lkkZpUL/fiIrXVIQDVkvPXp0L+wqr4XYUYa/O
FEatF6JxGIKDFcaGPDJZoGTiElL8MNNsrcfp9K0tFeSOWe0llOvwL2FCE8ZxWPuP
2Xf8fXg4s4zti9lugIiasG+E4pyQAaeWD53QU4FagQKBgQDtdC7yJcw8b/1AdLC+
Lw/tbZiJgZx2F/wTnr+D/yHFoz79YEquA/tTgF1u3HfpN5EypFcN+PqH9U6rDFV6
ZYYgzIBiOwKBwPzGhfJJt+6M+kQ/DAeiiGGzQz0l7FSSfvoytaQkrbbDCtHQ4W66
7kNhL0FdbipYpASlAMT3UGvmsQKBgQDdm2mMr4HQauIQm+LYCqEXugX0ZSthKJuf
POgiAX/rtgB6kt7z36MofASHwwN3LNOK81NyiaKN8lvq2MR5MeJsEx9+3ypWh2Is
L+O1E3x91WL3cuxw+ZzUGjpC5+h84HwWUjVlas8ycSB52kJO+Cuzg9YAWd+KeBJt
9H+Yv2dkxQKBgEPXQeJk8ikCRfS4Yha0E3TeLwp6QV1sFNT2MflgVyHENibl7/Av
qwp8TjVyP8Ad5Bn34fdX/xwA9ezgpTtG7j9IrhVijqDLpmyBsGtnZXxZtE3e/f9t
v5wbxcij8LW6GXmLc84W43RuDuwCvEQj9pQ5kA9Ffku88KbDxYJzM6DBAoGAIoD8
IieBcs3xfNyIqVKeWm9gVfkak/oaoOR+0CyjmjOwR2VuyVHcuYT1v52hgIC+Pzg7
me3MHYXKwfoWPTiDJIilsr9UfDyAEJk0PxFVpNIAor6GCeEThgK/Z4NsM2VQbLlI
Dw5eTGBIyjAtetYxF7ZDL7LOl2SymeQjqcjDdHECgYAYlYu13zUlYnUfK5Tx8UXG
nYV3LNdBmFnMp8nkm/Z8jo9IfKK8WypQIIGDgbrvNiW+1LnNhmUqbcBblSAkB7ik
VCsfOCV6nV7P/pHXtxeW3qn5iW/GAvgEkXp0ViNhQ3EW4P6HdSr1bPrShwZ0qgs1
DonICKNWoLnnuMpBXXoU+w==
-----END PRIVATE KEY-----',
        'client_email' => 'firebase-adminsdk-fbsvc@reminder-c0728.iam.gserviceaccount.com',
        'client_id' => '108955683686626709101',
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token'
    ];
}

// Simple Firebase implementation using HTTP
class SimpleFirebaseDatabase {
    private $databaseUrl;
    private $accessToken = null;
    
    public function __construct($databaseUrl, $serviceAccount) {
        $this->databaseUrl = rtrim($databaseUrl, '/');
        $this->serviceAccount = $serviceAccount;
    }
    
    private function getAccessToken() {
        if ($this->accessToken) {
            return $this->accessToken;
        }
        
        try {
            $privateKey = $this->serviceAccount['private_key'];
            $serviceAccountEmail = $this->serviceAccount['client_email'];
            
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
            
            $signature = '';
            $privateKeyResource = openssl_pkey_get_private($privateKey);
            openssl_sign($base64UrlHeader . '.' . $base64UrlClaimSet, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);
            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            
            $jwt = $base64UrlHeader . '.' . $base64UrlClaimSet . '.' . $base64UrlSignature;
            
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
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $tokenData = json_decode($response, true);
            $this->accessToken = $tokenData['access_token'] ?? null;
            
            return $this->accessToken;
        } catch (Exception $e) {
            error_log("Error getting access token: " . $e->getMessage());
            return null;
        }
    }
    
    public function getReference($path) {
        return new SimpleFirebaseReference($this, $path);
    }
    
    public function getValue($path = null) {
        $url = $this->databaseUrl;
        if ($path) {
            $url .= '/' . ltrim($path, '/');
        }
        
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            throw new Exception('Could not get access token');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url . '.json');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("HTTP Error: $httpCode - $response");
        }
        
        return json_decode($response, true);
    }
}

class SimpleFirebaseReference {
    private $database;
    private $path;
    
    public function __construct($database, $path) {
        $this->database = $database;
        $this->path = $path;
    }
    
    public function getSnapshot() {
        try {
            $value = $this->database->getValue($this->path);
            return new SimpleFirebaseSnapshot($value);
        } catch (Exception $e) {
            return new SimpleFirebaseSnapshot(null);
        }
    }
    
    public function push($value) {
        // For simplicity, just return a mock key
        return uniqid();
    }
    
    public function set($value) {
        // Mock implementation
        return true;
    }
    
    public function update($value) {
        // Mock implementation
        return true;
    }
    
    public function remove() {
        // Mock implementation
        return true;
    }
}

class SimpleFirebaseSnapshot {
    private $value;
    
    public function __construct($value) {
        $this->value = $value;
    }
    
    public function exists() {
        return $this->value !== null && $this->value !== '';
    }
    
    public function getValue() {
        return $this->value;
    }
}

// Initialize database
try {
    $database = new SimpleFirebaseDatabase($firebaseConfig['databaseURL'], $serviceAccount);
} catch (Exception $e) {
    // Fallback to mock database
    class MockDatabase {
        private $data = [];
        
        public function getReference($path) {
            return new MockReference($this, $path);
        }
        
        public function getValue($path = null) {
            if ($path) {
                return $this->data[$path] ?? [];
            }
            return $this->data;
        }
        
        public function setValue($path, $value) {
            $this->data[$path] = $value;
        }
    }
    
    class MockReference {
        private $database;
        private $path;
        
        public function __construct($database, $path) {
            $this->database = $database;
            $this->path = $path;
        }
        
        public function getSnapshot() {
            return new MockSnapshot($this->database->getValue($this->path));
        }
        
        public function push($value) {
            $key = uniqid();
            $this->database->setValue($this->path . '/' . $key, $value);
            return $key;
        }
        
        public function set($value) {
            $this->database->setValue($this->path, $value);
        }
    }
    
    class MockSnapshot {
        private $value;
        
        public function __construct($value) {
            $this->value = $value;
        }
        
        public function exists() {
            return !empty($this->value);
        }
        
        public function getValue() {
            return $this->value;
        }
    }
    
    $database = new MockDatabase();
}

// Mock messaging
$messaging = null;
?>
