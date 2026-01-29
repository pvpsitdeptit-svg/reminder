<?php
require_once 'includes/header.php';

require_once 'config/firebase.php';

$items = [];
try {
    $ref = $database->getReference('invigilation');
    $snap = $ref->getSnapshot();
    if ($snap->exists()) {
        $items = $snap->getValue();
        if (!is_array($items)) {
            $items = [];
        }
    }
} catch (Exception $e) {
    $error = 'Error loading invigilation: ' . $e->getMessage();
}

$editId = isset($_GET['id']) ? trim($_GET['id']) : '';
$editItem = ($editId && isset($items[$editId])) ? $items[$editId] : null;
?>
    <div class="container my-4">

<div class="d-flex justify-content-between align-items-center mb-4">
<h1 class="h3 mb-0">
<i class="bi bi-clipboard-check"></i> Manage Invigilation Duties
</h1>
<a href="index.php" class="btn btn-secondary">
<i class="bi bi-arrow-left"></i> Back to Dashboard
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
<div class="card-header bg-info text-white">
<strong><?= $editItem ? 'Edit Invigilation Duty' : 'Add Invigilation Duty'; ?></strong>
</div>

<div class="card-body">
<form action="save_invigilation.php" method="post">
<input type="hidden" name="id" value="<?= h($editId); ?>">

<div class="row g-3">
<div class="col-md-6">
<label class="form-label">Date</label>
<input type="date" name="date" class="form-control"
value="<?= h($editItem['date'] ?? ''); ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Time</label>
<input type="time" name="time" class="form-control"
value="<?= h($editItem['time'] ?? ''); ?>" required>
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

<div class="col-12">
<label class="form-label">Exam</label>
<input type="text" name="exam" class="form-control"
value="<?= h($editItem['exam'] ?? ''); ?>" required>
</div>

<div class="col-md-6">
<label class="form-label">Room</label>
<input type="text" name="room" class="form-control"
value="<?= h($editItem['room'] ?? ''); ?>" required>
</div>
</div>

<div class="mt-4 d-flex gap-2">
<button class="btn btn-info">
<i class="bi bi-save"></i> Save
</button>

<a class="btn btn-secondary" href="index.php">
<i class="bi bi-arrow-left"></i> Back
</a>

<?php if ($editItem): ?>
<a class="btn btn-danger ms-auto"
href="delete_invigilation.php?id=<?= h($editId); ?>"
onclick="return confirm('Delete this duty?');">
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
<strong>Existing Invigilation Records</strong>
</div>

<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover table-striped table-bordered align-middle mb-0">
<thead>
<tr>
<th>Date</th>
<th>Time</th>
<th>Faculty</th>
<th>Exam</th>
<th>Room</th>
<th class="text-end">Actions</th>
</tr>
</thead>
<tbody>

<?php if (empty($items)): ?>
<tr>
<td colspan="6" class="text-center text-muted py-4">
No records found
</td>
</tr>
<?php else: foreach ($items as $key => $t): ?>
<tr>
<td><?= h($t['date'] ?? ''); ?></td>
<td><?= h($t['time'] ?? ''); ?></td>

<td>
<div class="fw-semibold"><?= h($t['faculty_id'] ?? ''); ?></div>
<div class="small text-muted"><?= h($t['faculty_email'] ?? ''); ?></div>
</td>

<td><?= h($t['exam'] ?? ''); ?></td>
<td><span class="badge bg-secondary"><?= h($t['room'] ?? ''); ?></span></td>

<td class="text-end">
<a class="btn btn-sm btn-outline-primary"
href="manage_invigilation.php?id=<?= h($key); ?>">
<i class="bi bi-pencil"></i>
</a>
<a class="btn btn-sm btn-outline-danger"
href="delete_invigilation.php?id=<?= h($key); ?>"
onclick="return confirm('Delete this duty?');">
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

<?php require_once 'includes/footer.php'; ?>
