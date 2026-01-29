<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

function field($name) { return isset($_POST[$name]) ? trim((string)$_POST[$name]) : ''; }

$email = strtolower(field('faculty_email'));
$type = strtoupper(field('leave_type'));
$session = strtoupper(field('session'));
$days = field('days');
$fromDate = field('from_date');
$toDate = field('to_date');
$reason = field('reason');

$errors = [];
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid faculty email';
// Accept all leave types for record purposes
if (!in_array($type, ['CL','EL','ML','HPL','OD','CCL','LOP'], true)) $errors[] = 'Invalid leave type';
if ($session === '') $errors[] = 'Session is required';
if ($fromDate === '') $errors[] = 'From date is required';
if ($toDate === '') $toDate = $fromDate;

if (strtotime($fromDate) > strtotime($toDate)) {
    $errors[] = 'From date cannot be after To date';
}

if (in_array($session, ['FN','AN'], true)) {
    $days = 0.5;
} elseif ($session === 'FULL') {
    $days = 1.0;
} else {
    if ($days === '' || !is_numeric($days) || (float)$days <= 0) $errors[] = 'Days must be a positive number';
}

if (!empty($errors)) {
    $_SESSION['error_message'] = implode('<br>', $errors);
    header('Location: manage_leave_availed.php');
    exit();
}

try {
    $current = new DateTime($fromDate);
    $end = new DateTime($toDate);
    $interval = new DateInterval('P1D');
    $added = 0;

    while ($current <= $end) {
        $payload = [
            'faculty_email' => $email,
            'faculty_key' => firebaseKeyFromEmail($email),
            'leave_type' => $type,
            'session' => $session,
            'days' => (float)$days,
            'date' => $current->format('Y-m-d'),
            'reason' => $reason,
            'createdAt' => time(),
        ];

        $database->getReference('leave_ledger')->push($payload);
        $added++;
        $current->add($interval);
    }

    // Send FCM notification to faculty
    if (isset($messaging) && isset($database)) {
        sendLeaveAvailedNotification($messaging, $database, $email, $type, (float)$days, $fromDate, $toDate);
    }

    $_SESSION['success_message'] = $added . ' availed leave entries added';
} catch (Exception $e) {
    $_SESSION['error_message'] = 'Error saving availed leave entry: ' . $e->getMessage();
}

header('Location: manage_leave_availed.php');
exit();
