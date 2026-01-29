<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

$templates = [];
try {
    $ref = $database->getReference('lecture_templates');
    $snap = $ref->getSnapshot();
    if ($snap->exists()) {
        $templates = $snap->getValue();
        if (!is_array($templates)) {
            $templates = [];
        }
    }
} catch (Exception $e) {
    $error = 'Error loading templates: ' . $e->getMessage();
}

$editId = isset($_GET['id']) ? trim($_GET['id']) : '';
$editItem = ($editId && isset($templates[$editId])) ? $templates[$editId] : null;

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Lecture Templates</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
body { background:#f4f6f9; }
.card { border:none; border-radius:12px; }
.shadow-soft { box-shadow:0 10px 25px rgba(0,0,0,.08); }
.table thead th { background:#f1f3f5; font-weight:600; }
</style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
<div class="container">
<a class="navbar-brand" href="index.php">
<i class="bi bi-calendar-check"></i> Faculty Management System
</a>
<div class="navbar-nav ms-auto">
<a class="nav-link text-white" href="manage_invigilation.php"><i class="bi bi-clipboard-check"></i> Invigilation</a>
<a class="nav-link text-white" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
</div>
</nav>

<div class="container my-4">

<!-- HEADER -->
<div class="d-flex justify-content-between align-items-center mb-3">
<h3 class="mb-0"><i class="bi bi-book"></i> Manage Lecture Templates</h3>
<a href="manage_lectures.php" class="btn btn-outline-secondary btn-sm">
<i class="bi bi-x-circle"></i> Clear Form
</a>
</div>

<?php if (!empty($_SESSION['success_message'])): ?>
<div class="alert alert-success alert-dismissible fade show">
<i class="bi bi-check-circle"></i>
<?= h($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
<button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['error_message'])): ?>
<div class="alert alert-danger alert-dismissible fade show">
<i class="bi bi-exclamation-triangle"></i>
<?= h($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
<button class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">

<!-- FORM -->
<div class="col-lg-5">
<div class="card shadow-soft">
<div class="card-header bg-success text-white">
<strong><?= $editItem ? 'Edit Lecture Template' : 'Add Lecture Template'; ?></strong>
</div>

<div class="card-body">
<form action="save_lecture.php" method="post">
<input type="hidden" name="id" value="<?= h($editId); ?>">

<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Day</label>
<input type="text" name="day" class="form-control"
placeholder="Mon or Monday"
value="<?= h($editItem['day'] ?? ''); ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Time</label>
<input type="time" name="time" class="form-control"
value="<?= h($editItem['time'] ?? ''); ?>" required>
</div>

<div class="col-12">
<label class="form-label">Lecture Name</label>
<input type="text" name="name" class="form-control"
value="<?= h($editItem['name'] ?? ''); ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Faculty ID</label>
<input type="text" name="faculty_id" class="form-control"
value="<?= h($editItem['faculty_id'] ?? ''); ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Faculty Email</label>
<input type="email" name="faculty_email" class="form-control"
value="<?= h($editItem['faculty_email'] ?? ''); ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Subject</label>
<input type="text" name="subject" class="form-control"
value="<?= h($editItem['subject'] ?? ''); ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Room</label>
<input type="text" name="room" class="form-control"
value="<?= h($editItem['room'] ?? ''); ?>" required>
</div>
</div>

<div class="mt-4 d-flex gap-2">
<button class="btn btn-success">
<i class="bi bi-save"></i> Save
</button>

<a class="btn btn-secondary" href="index.php">
<i class="bi bi-arrow-left"></i> Back
</a>

<?php if ($editItem): ?>
<a class="btn btn-danger ms-auto"
href="delete_lecture.php?id=<?= h($editId); ?>"
onclick="return confirm('Delete this template?');">
<i class="bi bi-trash"></i> Delete
</a>
<?php endif; ?>
</div>
</form>
</div>
</div>
</div>

<!-- TABLE -->
<div class="col-lg-7">
<div class="card shadow-soft">
<div class="card-header bg-primary text-white">
<strong>Existing Lecture Templates</strong>
</div>

<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover table-striped table-bordered align-middle mb-0">
<thead>
<tr>
<th>Day</th>
<th>Time</th>
<th>Lecture</th>
<th>Faculty</th>
<th>Subject</th>
<th>Room</th>
<th class="text-end">Actions</th>
</tr>
</thead>
<tbody>

<?php if (empty($templates)): ?>
<tr>
<td colspan="7" class="text-center text-muted py-4">
No templates found
</td>
</tr>
<?php else: foreach ($templates as $key => $t): ?>
<tr>
<td><?= h($t['day'] ?? ''); ?></td>
<td><?= h($t['time'] ?? ''); ?></td>
<td><?= h($t['name'] ?? ''); ?></td>

<td>
<div class="fw-semibold"><?= h($t['faculty_id'] ?? ''); ?></div>
<div class="small text-muted"><?= h($t['faculty_email'] ?? ''); ?></div>
</td>

<td><?= h($t['subject'] ?? ''); ?></td>
<td><span class="badge bg-secondary"><?= h($t['room'] ?? ''); ?></span></td>

<td class="text-end">
<a class="btn btn-sm btn-outline-primary"
href="manage_lectures.php?id=<?= h($key); ?>">
<i class="bi bi-pencil"></i>
</a>
<a class="btn btn-sm btn-outline-danger"
href="delete_lecture.php?id=<?= h($key); ?>"
onclick="return confirm('Delete this template?');">
<i class="bi bi-trash"></i>
</a>
</td>
</tr>
<?php endforeach; endif; ?>

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
