<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

$lectures = [];
$invigilation = [];

try {
    $templates_ref = $database->getReference('lecture_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $lecture_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];

    $generated = [];
    if (!empty($lecture_templates)) {
        $start = new DateTime('today');
        $end = (new DateTime('today'))->modify('+13 days');
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));

        foreach ($period as $date) {
            $dowFull = strtolower($date->format('l'));
            $dowShort = strtolower($date->format('D'));

            foreach ($lecture_templates as $tpl) {
                $tplDay = strtolower(trim($tpl['day'] ?? ''));
                if ($tplDay === $dowFull || $tplDay === $dowShort) {
                    $generated[] = [
                        'date' => $date->format('Y-m-d'),
                        'time' => $tpl['time'] ?? '',
                        'name' => $tpl['name'] ?? '',
                        'faculty_id' => $tpl['faculty_id'] ?? '',
                        'faculty_email' => $tpl['faculty_email'] ?? '',
                        'subject' => $tpl['subject'] ?? '',
                        'room' => $tpl['room'] ?? ''
                    ];
                }
            }
        }
    }

    usort($generated, fn($a, $b) => [$a['date'], $a['time']] <=> [$b['date'], $b['time']]);
    $lectures = $generated;

    $invigilation_ref = $database->getReference('invigilation');
    $invigilation_snapshot = $invigilation_ref->getSnapshot();
    $invigilation = $invigilation_snapshot->exists() ? $invigilation_snapshot->getValue() : [];

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard | Faculty Management</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
body { background:#f4f6f9; }
.card { border: none; border-radius: 12px; }
.shadow-soft { box-shadow: 0 10px 25px rgba(0,0,0,.08); }
.stat-card { color:#fff; }
.stat-1 { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-2 { background: linear-gradient(135deg,#43cea2,#185a9d); }
.stat-3 { background: linear-gradient(135deg,#ff9966,#ff5e62); }
.upload-area {
    border:2px dashed #ced4da;
    border-radius:12px;
    padding:24px;
    text-align:center;
    transition:.3s;
}
.upload-area:hover { background:#f8f9fa; border-color:#0d6efd; }
.table thead th { background:#f1f3f5; font-weight:600; }
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
<div class="container">
<a class="navbar-brand" href="index.php"><i class="bi bi-calendar-check"></i> Faculty Management</a>
<div class="navbar-nav ms-auto">
<a class="nav-link text-white" href="manage_lectures.php"><i class="bi bi-book"></i> Lectures</a>
<a class="nav-link text-white" href="manage_invigilation.php"><i class="bi bi-clipboard-check"></i> Invigilation</a>
<a class="nav-link text-white" href="manage_faculty_leaves.php"><i class="bi bi-person-lines-fill"></i> Leaves</a>
<a class="nav-link text-white" href="templates.php"><i class="bi bi-file-earmark-csv"></i> Templates</a>
<a class="nav-link text-white" href="leave_balance_report.php"><i class="bi bi-graph-up"></i> Balance</a>
<a class="nav-link text-white" href="manage_messaging.php"><i class="bi bi-chat-dots"></i> Messaging</a>
<a class="nav-link text-white" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
</div>
</nav>

<div class="container my-4">

<!-- HEADER -->
<div class="mb-4">
<h2 class="fw-bold">Admin Dashboard</h2>
<p class="text-muted">Overview of lectures, invigilation & faculty activity</p>
</div>

<!-- STATS -->
<div class="row g-3 mb-4">
<div class="col-md-4">
<div class="card stat-card stat-1 shadow-soft">
<div class="card-body d-flex justify-content-between align-items-center">
<div>
<div class="small">Total Lectures</div>
<h2><?= count($lectures); ?></h2>
</div>
<i class="bi bi-book fs-1 opacity-75"></i>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card stat-card stat-2 shadow-soft">
<div class="card-body d-flex justify-content-between align-items-center">
<div>
<div class="small">Invigilation Duties</div>
<h2><?= count($invigilation); ?></h2>
</div>
<i class="bi bi-clipboard-check fs-1 opacity-75"></i>
</div>
</div>
</div>

<div class="col-md-4">
<div class="card stat-card stat-3 shadow-soft">
<div class="card-body d-flex justify-content-between align-items-center">
<div>
<div class="small">Active Faculty</div>
<h2><?= count(array_unique(array_column($lectures,'faculty_id'))); ?></h2>
</div>
<i class="bi bi-people fs-1 opacity-75"></i>
</div>
</div>
</div>
</div>

<!-- UPLOADS -->
<div class="row g-3 mb-4">
<?php
$uploads = [
    ['Lecture Timetable','upload_lecture.php','success'],
    ['Invigilation Duty','upload_invigilation.php','info'],
    ['Faculty Leave Master','upload_faculty_leaves.php','warning']
];
foreach ($uploads as $u):
?>
<div class="col-md-4">
<div class="card shadow-soft">
<div class="card-header bg-<?= $u[2]; ?> text-white"><?= $u[0]; ?></div>
<div class="card-body">
<form action="<?= $u[1]; ?>" method="post" enctype="multipart/form-data">
<div class="upload-area mb-3">
<i class="bi bi-file-earmark-csv fs-1 text-muted"></i>
<p class="mb-2">Upload CSV File</p>
<input type="file" class="form-control" name="<?= $u[0] === 'Faculty Leave Master' ? 'faculty_leaves_csv' : 'csv_file'; ?>" required>
</div>
<button class="btn btn-<?= $u[2]; ?> w-100">
<i class="bi bi-cloud-upload"></i> Upload
</button>
</form>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- TABLES -->
<div class="card shadow-soft">
<div class="card-header">
<ul class="nav nav-tabs card-header-tabs">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#lec">Lectures</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inv">Invigilation</a></li>
</ul>
</div>

<div class="card-body">
<div class="tab-content">

<div class="tab-pane fade show active" id="lec">
<div class="table-responsive">
<table class="table table-sm table-hover">
<thead>
<tr>
<th>Date</th><th>Time</th><th>Faculty</th><th>Subject</th><th>Room</th>
</tr>
</thead>
<tbody>
<?php foreach (array_slice($lectures,0,10) as $l): ?>
<tr>
<td><?= $l['date']; ?></td>
<td><?= $l['time']; ?></td>
<td><?= $l['faculty_id']; ?></td>
<td><?= $l['subject']; ?></td>
<td><?= $l['room']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="tab-pane fade" id="inv">
<div class="table-responsive">
<table class="table table-sm table-hover">
<thead>
<tr>
<th>Date</th><th>Time</th><th>Faculty</th><th>Exam</th><th>Room</th>
</tr>
</thead>
<tbody>
<?php foreach (array_slice($invigilation,0,10) as $i): ?>
<tr>
<td><?= $i['date']; ?></td>
<td><?= $i['time']; ?></td>
<td><?= $i['faculty_id']; ?></td>
<td><?= $i['exam']; ?></td>
<td><?= $i['room']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>
</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
