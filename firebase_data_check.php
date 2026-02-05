<?php
require_once 'config/firebase.php';

echo "<h2>🔥 Firebase Data Check</h2>\n";

try {
    $database = $GLOBALS['database'];
    
    // Check lecture_templates
    echo "<h3>📚 Lecture Templates</h3>\n";
    $lecturesRef = $database->getReference('lecture_templates');
    $lecturesSnapshot = $lecturesRef->getSnapshot();
    $lectures = $lecturesSnapshot->exists() ? $lecturesSnapshot->getValue() : [];
    
    echo "<p><strong>Total Records:</strong> " . count($lectures) . "</p>\n";
    if (!empty($lectures)) {
        echo "<div class='alert alert-success'>";
        $firstLecture = reset($lectures);
        echo "<strong>Sample Lecture:</strong><br>";
        echo "Faculty ID: " . ($firstLecture['faculty_id'] ?? 'N/A') . "<br>";
        echo "Subject: " . ($firstLecture['subject'] ?? 'N/A') . "<br>";
        echo "Time: " . ($firstLecture['time'] ?? 'N/A') . "<br>";
        echo "Room: " . ($firstLecture['room'] ?? 'N/A') . "<br>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>No lecture templates found</div>";
    }
    
    // Check invigilation
    echo "<h3>📋 Invigilation Duties</h3>\n";
    $invigilationRef = $database->getReference('invigilation');
    $invigilationSnapshot = $invigilationRef->getSnapshot();
    $invigilation = $invigilationSnapshot->exists() ? $invigilationSnapshot->getValue() : [];
    
    echo "<p><strong>Total Records:</strong> " . count($invigilation) . "</p>\n";
    if (!empty($invigilation)) {
        echo "<div class='alert alert-success'>";
        $firstInvigilation = reset($invigilation);
        echo "<strong>Sample Invigilation:</strong><br>";
        echo "Faculty ID: " . ($firstInvigilation['faculty_id'] ?? 'N/A') . "<br>";
        echo "Exam: " . ($firstInvigilation['exam'] ?? 'N/A') . "<br>";
        echo "Date: " . ($firstInvigilation['date'] ?? 'N/A') . "<br>";
        echo "Room: " . ($firstInvigilation['room'] ?? 'N/A') . "<br>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>No invigilation duties found</div>";
    }
    
    // Check faculty_leave_master
    echo "<h3>👥 Faculty Leave Master</h3>\n";
    $facultyLeaveRef = $database->getReference('faculty_leave_master');
    $facultyLeaveSnapshot = $facultyLeaveRef->getSnapshot();
    $facultyLeave = $facultyLeaveSnapshot->exists() ? $facultyLeaveSnapshot->getValue() : [];
    
    echo "<p><strong>Total Records:</strong> " . count($facultyLeave) . "</p>\n";
    if (!empty($facultyLeave)) {
        echo "<div class='alert alert-success'>";
        $firstFaculty = reset($facultyLeave);
        echo "<strong>Sample Faculty:</strong><br>";
        echo "Name: " . ($firstFaculty['name'] ?? 'N/A') . "<br>";
        echo "Department: " . ($firstFaculty['department'] ?? 'N/A') . "<br>";
        echo "Employee ID: " . ($firstFaculty['employee_id'] ?? 'N/A') . "<br>";
        echo "Email: " . ($firstFaculty['faculty_email'] ?? 'N/A') . "<br>";
        echo "</div>";
    } else {
        echo "<div class='alert alert-warning'>No faculty leave master data found</div>";
    }
    
    echo "<h3>🔗 Firebase Connection Test</h3>\n";
    echo "<div class='alert alert-info'>";
    echo "<strong>Database Type:</strong> " . get_class($database) . "<br>";
    echo "<strong>Connection Status:</strong> ✅ Connected<br>";
    echo "<strong>Database URL:</strong> https://reminder-c0728-default-rtdb.firebaseio.com/";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>";
    echo "<strong>Firebase Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
