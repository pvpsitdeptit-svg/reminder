<?php
require_once 'includes/header.php';

require_once 'config/firebase.php';

$items = [];
try {
    $ref = $database->getReference('faculty_leave_master');
    $snap = $ref->getSnapshot();
    if ($snap->exists()) {
        $items = $snap->getValue();
        if (!is_array($items)) {
            $items = [];
        }
    }
} catch (Exception $e) {
    $error = 'Error loading faculty leave master: ' . $e->getMessage();
}

$editEmail = isset($_GET['email']) ? trim((string)$_GET['email']) : '';
$editKey = $editEmail ? firebaseKeyFromEmail($editEmail) : '';
$editItem = ($editKey && isset($items[$editKey])) ? $items[$editKey] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Faculty Leaves</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

<style>
.table thead th {
    background: #f8f9fa;
    font-weight: 600;
    white-space: nowrap;
}
.table td, .table th {
    vertical-align: middle;
}
.badge-leave {
    font-size: 0.85rem;
    min-width: 45px;
}
</style>
</head>

<body class="bg-light">

    <div class="container my-4">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">
        <i class="bi bi-person-lines-fill"></i> Manage Faculty Leave Master
    </h3>
    <a href="manage_faculty_leaves.php" class="btn btn-outline-secondary btn-sm">
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

<div class="row">

<!-- FORM -->
<div class="col-lg-5 mb-4">
<div class="card shadow-sm">
<div class="card-header bg-success text-white">
<strong><?= $editItem ? 'Edit Faculty Leave Master' : 'Add Faculty Leave Master'; ?></strong>
</div>
<div class="card-body">
<form action="save_faculty_leaves.php" method="post">
<input type="hidden" name="original_email" value="<?= h($editEmail); ?>">

<div class="row g-3">
<div class="col-6">
<label class="form-label">Employee ID</label>
<input type="text" name="employee_id" class="form-control" value="<?= h($editItem['employee_id'] ?? ''); ?>" required>
</div>

<div class="col-6">
<label class="form-label">Department</label>
<input type="text" name="department" class="form-control" value="<?= h($editItem['department'] ?? ''); ?>" required>
</div>

<div class="col-12">
<label class="form-label">Name</label>
<input type="text" name="name" class="form-control" value="<?= h($editItem['name'] ?? ''); ?>" required>
</div>

<div class="col-12">
<label class="form-label">Faculty Email</label>
<input type="email" name="faculty_email" class="form-control" value="<?= h($editItem['faculty_email'] ?? $editEmail); ?>" required>
</div>

<div class="col-6">
<label class="form-label">Total Leaves</label>
<input type="number" step="0.5" min="0" name="total_leaves" class="form-control" value="<?= h($editItem['total_leaves'] ?? '0'); ?>" required>
</div>

<div class="col-6">
<label class="form-label">CL</label>
<input type="number" step="0.5" min="0" name="cl" class="form-control" value="<?= h($editItem['cl'] ?? '0'); ?>" required>
</div>

<div class="col-6">
<label class="form-label">EL</label>
<input type="number" step="0.5" min="0" name="el" class="form-control" value="<?= h($editItem['el'] ?? '0'); ?>" required>
</div>

<div class="col-6">
<label class="form-label">ML</label>
<input type="number" step="0.5" min="0" name="ml" class="form-control" value="<?= h($editItem['ml'] ?? '0'); ?>" required>
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
   href="delete_faculty_leaves.php?email=<?= h($editEmail); ?>"
   onclick="return confirm('Delete this faculty leave record?');">
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
<div class="card shadow-sm">
<div class="card-header bg-primary text-white">
<strong>Existing Records</strong>
</div>
<div class="card-body p-0">

<div class="table-responsive">
<table class="table table-hover table-striped table-bordered mb-0 align-middle">
<thead class="table-light">
<tr>
<th style="min-width:180px;">Faculty</th>
<th style="min-width:140px;">Department</th>
<th style="min-width:220px;">Leave Summary</th>
<th class="text-end" style="width:110px;">Actions</th>
</tr>
</thead>

<tbody>
<?php if (empty($items)): ?>
<tr>
<td colspan="4" class="text-center text-muted py-4">
No records found
</td>
</tr>
<?php else:
ksort($items);
foreach ($items as $key => $t):
$decodedEmail = firebaseEmailFromKey($key);
$displayEmail = $decodedEmail !== '' ? $decodedEmail : $key;
?>
<tr>

<!-- FACULTY -->
<td>
<div class="fw-semibold"><?= h($t['name'] ?? ''); ?></div>
<div class="small text-muted"><?= h($displayEmail); ?></div>
<div class="small text-muted">ID: <?= h($t['employee_id'] ?? ''); ?></div>
</td>

<!-- DEPARTMENT -->
<td><?= h($t['department'] ?? ''); ?></td>

<!-- LEAVE SUMMARY -->
<td>
<div class="d-flex flex-wrap gap-2">
<span class="badge bg-dark">Total: <?= h($t['total_leaves']); ?></span>
<span class="badge bg-info text-dark">CL: <?= h($t['cl']); ?></span>
<span class="badge bg-warning text-dark">EL: <?= h($t['el']); ?></span>
<span class="badge bg-secondary">ML: <?= h($t['ml']); ?></span>
</div>
</td>

<!-- ACTIONS -->
<td class="text-end">
<a class="btn btn-sm btn-outline-primary"
   href="manage_faculty_leaves.php?email=<?= h($displayEmail); ?>">
<i class="bi bi-pencil"></i>
</a>
<a class="btn btn-sm btn-outline-danger"
   href="delete_faculty_leaves.php?email=<?= h($displayEmail); ?>"
   onclick="return confirm('Delete this faculty leave record?');">
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
