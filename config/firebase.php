<?php
// Firebase configuration
// Check if vendor/autoload.php exists, otherwise use mock implementation
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

// Firebase configuration
$firebaseConfig = [
    'apiKey' => 'AIzaSyCpm5OhNvWksaGGot76Bwr9EpYb1CH4FvY',
    'authDomain' => 'reminder-c0728.firebaseapp.com',
    'databaseURL' => 'https://reminder-c0728-default-rtdb.firebaseio.com/',
    'projectId' => 'reminder-c0728',
    'storageBucket' => 'reminder-c0728.firebasestorage.app',
    'messagingSenderId' => '987181259638',
    'appId' => '1:987181259638:android:283e678c075059f9d7b857'
];

// Service account configuration - REPLACE WITH YOUR ACTUAL VALUES
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

// IMPORTANT: Update the above values with your actual Firebase project details
// Visit: config/firebase_setup_guide.php for detailed instructions

// Force Mock Mode (set to true to use in-memory mock DB even if Firebase is configured)
$FORCE_MOCK_MODE = false;

try {
    // Only try to initialize Firebase if vendor/autoload.php exists and Mock Mode is not forced
    if (!$FORCE_MOCK_MODE && file_exists(__DIR__ . '/../vendor/autoload.php')) {
        // Initialize Firebase using fully qualified class name
        $factory = new \Kreait\Firebase\Factory();
        $factory = $factory
            ->withServiceAccount($serviceAccount)
            ->withDatabaseUri($firebaseConfig['databaseURL']);
        
        $database = $factory->createDatabase();
        $messaging = $factory->createMessaging();
    } else {
        // Use mock database if Mock Mode is forced or Firebase dependencies are not installed
        throw new Exception('Mock mode forced or Firebase dependencies not installed - using mock database');
    }
} catch (Exception $e) {
    // For development, create a mock database if Firebase is not configured
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

// Helper functions for Firebase operations
function uploadToFirebase($database, $path, $data) {
    try {
        $reference = $database->getReference($path);
        $reference->set($data);
        return true;
    } catch (Exception $e) {
        error_log("Firebase upload error: " . $e->getMessage());
        return false;
    }
}

function firebaseKeyFromEmail($email) {
    $email = strtolower(trim((string)$email));
    $b64 = base64_encode($email);
    $b64url = rtrim(strtr($b64, '+/', '-_'), '=');
    return $b64url;
}

function firebaseEmailFromKey($key) {
    $key = (string)$key;
    $b64 = strtr($key, '-_', '+/');
    $pad = strlen($b64) % 4;
    if ($pad > 0) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $decoded = base64_decode($b64, true);
    if ($decoded === false) {
        return '';
    }
    return (string)$decoded;
}

function validateCSVData($data, $type) {
    $errors = [];
    
    if ($type === 'lecture') {
        // Validate lecture CSV structure
        $required_fields = ['date', 'time', 'faculty_id', 'faculty_email', 'subject', 'room', 'academic_year', 'branch', 'year', 'section'];
        
        foreach ($data as $row_index => $row) {
            foreach ($required_fields as $field) {
                if (!isset($row[$field]) || empty($row[$field])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Missing required field '$field'";
                }
            }
            
            // Validate date format
            if (isset($row['date']) && !empty($row['date'])) {
                if (!DateTime::createFromFormat('Y-m-d', $row['date']) && 
                    !DateTime::createFromFormat('d/m/Y', $row['date'])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid date format. Use Y-m-d or d/m/Y";
                }
            }
            
            // Validate time format
            if (isset($row['time']) && !empty($row['time'])) {
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $row['time'])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid time format. Use HH:MM";
                }
            }
            
            // Validate email format
            if (isset($row['faculty_email']) && !empty($row['faculty_email'])) {
                if (!filter_var($row['faculty_email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid email format";
                }
            }

            // Validate academic_year format (e.g., 2024-2025)
            if (isset($row['academic_year']) && !empty($row['academic_year'])) {
                if (!preg_match('/^\d{4}-\d{4}$/', $row['academic_year'])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid academic_year. Use YYYY-YYYY";
                }
            }

            // Validate branch (letters, numbers, dash and space allowed, 2-20 chars)
            if (isset($row['branch']) && !empty($row['branch'])) {
                if (!preg_match('/^[A-Za-z0-9 \-]{2,20}$/', $row['branch'])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid branch. Use 2-20 letters/numbers (spaces and hyphens allowed)";
                }
            }

            // Validate year (accept 1,2,3,4 or I)
            if (isset($row['year']) && !empty($row['year'])) {
                $yearVal = strtoupper(trim($row['year']));
                $allowedYears = ['1','2','3','4','I'];
                if (!in_array($yearVal, $allowedYears, true)) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid year. Allowed: 1, 2, 3, 4 or I";
                }
            }

            // Validate section (s1 or s2)
            if (isset($row['section']) && !empty($row['section'])) {
                $sec = strtolower(trim($row['section']));
                if (!in_array($sec, ['s1','s2'], true)) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid section. Allowed: s1 or s2";
                }
            }
        }
    } elseif ($type === 'lecture_template') {
        // Weekly lecture template validation: only specified fields
        $required_fields = ['day', 'time', 'name', 'faculty_id', 'faculty_email', 'subject', 'room'];

        $allowed_days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday','mon','tue','wed','thu','fri','sat','sun'];

        foreach ($data as $row_index => $row) {
            foreach ($required_fields as $field) {
                if (!isset($row[$field]) || $row[$field] === '') {
                    $errors[] = "Row " . ($row_index + 2) . ": Missing required field '$field'";
                }
            }

            // Validate day of week
            if (isset($row['day']) && $row['day'] !== '') {
                $day = strtolower(trim($row['day']));
                if (!in_array($day, $allowed_days, true)) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid day. Use full or short weekday name (e.g., Monday or Mon)";
                }
            }

            // Validate time format
            if (isset($row['time']) && $row['time'] !== '') {
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $row['time'])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid time format. Use HH:MM";
                }
            }

            // Validate email format
            if (isset($row['faculty_email']) && $row['faculty_email'] !== '') {
                if (!filter_var($row['faculty_email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid email format";
                }
            }
        }
    } elseif ($type === 'invigilation') {
        // Validate invigilation CSV structure
        $required_fields = ['date', 'time', 'faculty_id', 'faculty_email', 'exam', 'room'];
        
        foreach ($data as $row_index => $row) {
            foreach ($required_fields as $field) {
                if (!isset($row[$field]) || empty($row[$field])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Missing required field '$field'";
                }
            }
            
            // Validate date format
            if (isset($row['date']) && !empty($row['date'])) {
                if (!DateTime::createFromFormat('Y-m-d', $row['date']) && 
                    !DateTime::createFromFormat('d/m/Y', $row['date'])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid date format. Use Y-m-d or d/m/Y";
                }
            }
            
            // Validate time format
            if (isset($row['time']) && !empty($row['time'])) {
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/', $row['time'])) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid time format. Use HH:MM";
                }
            }
            
            // Validate email format
            if (isset($row['faculty_email']) && !empty($row['faculty_email'])) {
                if (!filter_var($row['faculty_email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid email format";
                }
            }
        }
    } elseif ($type === 'faculty_leave_master') {
        $required_fields = ['employee_id', 'name', 'department', 'faculty_email', 'total_leaves', 'cl', 'el', 'ml'];

        foreach ($data as $row_index => $row) {
            foreach ($required_fields as $field) {
                if (!isset($row[$field]) || $row[$field] === '') {
                    $errors[] = "Row " . ($row_index + 2) . ": Missing required field '$field'";
                }
            }

            if (isset($row['faculty_email']) && $row['faculty_email'] !== '') {
                if (!filter_var($row['faculty_email'], FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row " . ($row_index + 2) . ": Invalid email format";
                }
            }

            $numericFields = ['total_leaves', 'cl', 'el', 'ml'];
            foreach ($numericFields as $nf) {
                if (isset($row[$nf]) && $row[$nf] !== '') {
                    if (!is_numeric($row[$nf]) || (float)$row[$nf] < 0) {
                        $errors[] = "Row " . ($row_index + 2) . ": '$nf' must be a non-negative number";
                    }
                }
            }

            // Removed total_leaves validation - allow any relationship between total and individual leaves
        }
    }
    
    return $errors;
}

function parseCSVFile($file) {
    $data = [];
    $header = [];
    $row_index = 0;

    // Header alias map to canonical keys
    $aliases = [
        'day' => 'day',
        'weekday' => 'day',
        'day of week' => 'day',
        'time' => 'time',
        'time slot' => 'time',
        'timing' => 'time',
        'name' => 'name',
        'faculty name' => 'name',
        'name of faculty' => 'name',
        'faculty' => 'name',
        'faculty_id' => 'faculty_id',
        'faculty id' => 'faculty_id',
        'facultyid' => 'faculty_id',
        'faculty email' => 'faculty_email',
        'faculty_email' => 'faculty_email',
        'email' => 'faculty_email',
        'email id' => 'faculty_email',
        'email-id' => 'faculty_email',
        'employee id' => 'employee_id',
        'employee_id' => 'employee_id',
        'employeeid' => 'employee_id',
        'emp id' => 'employee_id',
        'emp_id' => 'employee_id',
        'empid' => 'employee_id',
        'department' => 'department',
        'dept' => 'department',
        'dept name' => 'department',
        'total leaves' => 'total_leaves',
        'total_leaves' => 'total_leaves',
        'total' => 'total_leaves',
        'cl' => 'cl',
        'casual leave' => 'cl',
        'el' => 'el',
        'earned leave' => 'el',
        'ml' => 'ml',
        'medical leave' => 'ml',
        'subject' => 'subject',
        'subject name' => 'subject',
        'room' => 'room',
        'room no' => 'room',
        'room number' => 'room',
        'classroom' => 'room',
    ];

    // Helper to normalize header strings
    $normalize = function ($str) {
        $str = strtolower(trim($str));
        // Remove UTF-8 BOM if present
        $str = ltrim($str, "\xEF\xBB\xBF\xFE\xFF\x00");
        // Replace underscores/dashes with spaces and collapse spaces
        $str = str_replace(['_', '-'], ' ', $str);
        $str = preg_replace('/\s+/', ' ', $str);
        return $str;
    };

    // Debug: Log raw headers before normalization
    $debugHeaders = [];
    if (($fh = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $firstLine = fgets($fh);
        if ($firstLine !== false) {
            $rawHeaders = str_getcsv($firstLine);
            foreach ($rawHeaders as $i => $header) {
                $debugHeaders[$i] = [
                    'raw' => $header,
                    'trimmed' => trim($header),
                    'lowercase' => strtolower(trim($header)),
                    'length' => strlen($header),
                    'hex' => bin2hex($header)
                ];
            }
            error_log("Raw headers debug: " . print_r($debugHeaders, true));
        }
        fclose($fh);
    }

    // Detect delimiter by peeking first line
    $delimiter = ',';
    if (($fh = fopen($file['tmp_name'], 'r')) !== FALSE) {
        $firstLine = fgets($fh);
        if ($firstLine !== false) {
            $comma = substr_count($firstLine, ',');
            $semicolon = substr_count($firstLine, ';');
            $tab = substr_count($firstLine, "\t");
            if ($semicolon > $comma && $semicolon >= $tab) {
                $delimiter = ';';
            } elseif ($tab > $comma && $tab > $semicolon) {
                $delimiter = "\t";
            } else {
                $delimiter = ',';
            }
        }
        rewind($fh);

        while (($row = fgetcsv($fh, 10000, $delimiter)) !== FALSE) {
            if ($row_index === 0) {
                // Header row
                $header = array_map(function ($h) use ($normalize, $aliases) {
                    $norm = $normalize($h);
                    return isset($aliases[$norm]) ? $aliases[$norm] : $norm;
                }, $row);
            } else {
                // Data row
                $data_row = [];
                foreach ($header as $key_index => $key) {
                    $val = isset($row[$key_index]) ? trim($row[$key_index]) : '';
                    $data_row[$key] = $val;
                }
                $data[] = $data_row;
            }
            $row_index++;
        }
        fclose($fh);
    }

    return $data;
}

function sendFCMNotification($messaging, $title, $body, $data = [], $tokens = []) {
    global $serviceAccount;
    
    try {
        if (empty($tokens)) {
            return false;
        }

        $projectId = 'reminder-c0728';
        $serviceAccountEmail = 'firebase-adminsdk-fbsvc@reminder-c0728.iam.gserviceaccount.com';
        $privateKey = $serviceAccount['private_key'];

        // Get OAuth2 token
        $privateKeyResource = openssl_pkey_get_private($privateKey);
        if ($privateKeyResource === false) {
            return false;
        }

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

        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return false;
        }

        $successCount = 0;

        // Send to each token using v1 API
        foreach ($tokens as $token) {
            $message = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body
                    ],
                    'data' => $data
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
            
            if ($httpCode == 200) {
                $successCount++;
            }
        }

        return $successCount > 0;

    } catch (Exception $e) {
        return false;
    }
}

function sendLeaveAvailedNotification($messaging, $database, $facultyEmail, $leaveType, $days, $fromDate, $toDate = null) {
    try {
        // Get faculty's FCM tokens from database using same path as Android app
        $sanitizedEmail = str_replace('.', '_', $facultyEmail);
        $sanitizedEmail = str_replace('@', '_', $sanitizedEmail);
        
        // Get token from fcm_tokens node (same as Android app stores)
        $tokenSnapshot = $database->getReference('fcm_tokens/' . $sanitizedEmail)->getSnapshot();
        $token = $tokenSnapshot->getValue();
        
        if (empty($token)) {
            return false;
        }
        
        $title = "Leave Availed - Admin Entry";
        $body = "Admin has recorded {$leaveType} leave for {$days} day(s) from {$fromDate}" . 
                ($toDate && $toDate !== $fromDate ? " to {$toDate}" : "");
        
        $data = [
            'type' => 'leave_availed',
            'faculty_email' => $facultyEmail,
            'leave_type' => $leaveType,
            'days' => (string)$days,
            'from_date' => $fromDate,
            'to_date' => $toDate ?: $fromDate,
            'timestamp' => (string)time()
        ];
        
        // Send to specific device token
        $result = sendFCMNotification($messaging, $title, $body, $data, [$token]);
        
        return $result;
        
    } catch (Exception $e) {
        return false;
    }
}
?>
