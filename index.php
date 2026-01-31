<?php
require_once 'includes/header.php';

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

<div class="container my-4">

<!-- Dashboard Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold mb-1">Admin Dashboard</h1>
        <p class="text-muted">Overview of lectures, invigilation & faculty activity</p>
    </div>
    <div class="text-end">
        <span class="badge bg-primary px-3 py-2">
            <i class="bi bi-calendar-check"></i> <?php echo date('F j, Y'); ?>
        </span>
    </div>
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
<input type="file" class="form-control" name="<?= $u[0] === 'Faculty Leave Master' ? 'faculty_leaves_csv' : ($u[0] === 'Invigilation Duty' ? 'invigilation_csv' : 'lecture_csv'); ?>" required>
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

<?php require_once 'includes/footer.php'; ?>
