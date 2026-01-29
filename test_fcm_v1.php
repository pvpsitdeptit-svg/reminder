<?php
// Test FCM using v1 API (requires OAuth2 token)

// Your project info
$projectId = "reminder-c0728";
$serviceAccountFile = __DIR__ . '/service-account.json'; // You'll need to create this

// For now, let's test with the service account approach
require_once 'config/firebase.php';

echo "<h2>FCM v1 API Test</h2>";

try {
    if (isset($messaging)) {
        echo "<p>✅ Firebase Messaging initialized</p>";
        
        // Get OAuth2 token using service account
        $factory = new \Kreait\Firebase\Factory();
        $serviceAccount = $factory->withServiceAccount([
            'type' => 'service_account',
            'project_id' => $projectId,
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
DonICKNWoLnnuMpBXXoU+w==-----END PRIVATE KEY-----',
            'client_email' => 'firebase-adminsdk-fbsvc@reminder-c0728.iam.gserviceaccount.com',
            'client_id' => '108955683686626709101',
            'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
            'token_uri' => 'https://oauth2.googleapis.com/token'
        ]);
        
        $messaging = $serviceAccount->createMessaging();
        
        // Test message
        $token = "dQQrev2nSwSn6DJkZCkK3s:APA91bE...";
        
        $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget(\Kreait\Firebase\Messaging\Target::token($token))
            ->withNotification(\Kreait\Firebase\Messaging\Notification::create('Test v1', 'Testing FCM v1 API'))
            ->withData(['type' => 'test_v1', 'timestamp' => time()]);
        
        $result = $messaging->send($message);
        
        echo "<p>✅ FCM v1 message sent!</p>";
        echo "<p>Message ID: " . htmlspecialchars($result->messageId()) . "</p>";
        
    } else {
        echo "<p>❌ Firebase Messaging not initialized</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
