<?php
require_once 'config/firebase.php';

echo "=== Testing Lecture Timetable Upload ===" . PHP_EOL;

// Test lecture template CSV
$lecture_csv_content = file_get_contents('test_lecture_template.csv');
$temp_file = tempnam(sys_get_temp_dir(), 'test_lecture');
file_put_contents($temp_file, $lecture_csv_content);

$_FILES['lecture_csv'] = [
    'name' => 'test_lecture_template.csv',
    'type' => 'text/csv',
    'size' => strlen($lecture_csv_content),
    'tmp_name' => $temp_file,
    'error' => UPLOAD_ERR_OK
];

try {
    $lecture_data = parseCSVFile($_FILES['lecture_csv']);
    echo "Lecture CSV parsed: " . count($lecture_data) . " rows" . PHP_EOL;
    
    $validation_errors = validateCSVData($lecture_data, 'lecture_template');
    if (!empty($validation_errors)) {
        echo "Lecture validation errors:" . PHP_EOL;
        foreach ($validation_errors as $error) {
            echo "  - " . $error . PHP_EOL;
        }
    } else {
        echo "Lecture validation: PASSED" . PHP_EOL;
        
        // Test Firebase upload
        $lectures_ref = $database->getReference('lecture_templates_test');
        $lectures_ref->set([]);
        foreach ($lecture_data as $lecture) {
            $lectures_ref->push($lecture);
        }
        echo "Lecture Firebase upload: SUCCESS" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Lecture upload error: " . $e->getMessage() . PHP_EOL;
}

unlink($temp_file);

echo PHP_EOL . "=== Testing Faculty Leave Master Upload ===" . PHP_EOL;

// Create test faculty leave CSV
$faculty_csv_content = "employee_id,name,department,faculty_email,total_leaves,cl,el,ml
FAC001,John Smith,Computer Science,john.smith@college.com,30,12,15,3
FAC002,Jane Doe,Information Technology,jane.doe@college.com,30,10,15,5
FAC003,Bob Johnson,Computer Science,bob.johnson@college.com,30,12,18,0";

$temp_file2 = tempnam(sys_get_temp_dir(), 'test_faculty');
file_put_contents($temp_file2, $faculty_csv_content);

$_FILES['faculty_leaves_csv'] = [
    'name' => 'faculty_leaves.csv',
    'type' => 'text/csv',
    'size' => strlen($faculty_csv_content),
    'tmp_name' => $temp_file2,
    'error' => UPLOAD_ERR_OK
];

try {
    $faculty_data = parseCSVFile($_FILES['faculty_leaves_csv']);
    echo "Faculty CSV parsed: " . count($faculty_data) . " rows" . PHP_EOL;
    
    $validation_errors = validateCSVData($faculty_data, 'faculty_leave_master');
    if (!empty($validation_errors)) {
        echo "Faculty validation errors:" . PHP_EOL;
        foreach ($validation_errors as $error) {
            echo "  - " . $error . PHP_EOL;
        }
    } else {
        echo "Faculty validation: PASSED" . PHP_EOL;
        
        // Test Firebase upload
        $ref = $database->getReference('faculty_leave_master_test');
        $ref->set([]);
        
        foreach ($faculty_data as $r) {
            $email = strtolower(trim($r['faculty_email'] ?? ''));
            if ($email === '') continue;
            
            $key = firebaseKeyFromEmail($email);
            $payload = [
                'employee_id' => trim((string)($r['employee_id'] ?? '')),
                'name' => trim((string)($r['name'] ?? '')),
                'department' => trim((string)($r['department'] ?? '')),
                'faculty_email' => $email,
                'total_leaves' => (float)($r['total_leaves'] ?? 0),
                'cl' => (float)($r['cl'] ?? 0),
                'el' => (float)($r['el'] ?? 0),
                'ml' => (float)($r['ml'] ?? 0),
                'createdAt' => time(),
                'updatedAt' => time()
            ];
            
            $database->getReference('faculty_leave_master_test/' . $key)->set($payload);
        }
        echo "Faculty Firebase upload: SUCCESS" . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Faculty upload error: " . $e->getMessage() . PHP_EOL;
}

unlink($temp_file2);

echo PHP_EOL . "=== Upload Testing Complete ===" . PHP_EOL;
?>
