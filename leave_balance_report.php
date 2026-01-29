<?php
require_once 'includes/header.php';

require_once 'config/firebase.php';

$master = [];
$ledger = [];

try {
    $mSnap = $database->getReference('faculty_leave_master')->getSnapshot();
    if ($mSnap->exists()) {
        $master = $mSnap->getValue();
        if (!is_array($master)) $master = [];
    }

    $lSnap = $database->getReference('leave_ledger')->getSnapshot();
    if ($lSnap->exists()) {
        $ledger = $lSnap->getValue();
        if (!is_array($ledger)) $ledger = [];
    }
} catch (Exception $e) {
    $error = 'Error loading data: ' . $e->getMessage();
}

$filterDept  = isset($_GET['department']) ? trim($_GET['department']) : '';
$filterEmail = isset($_GET['email']) ? strtolower(trim($_GET['email'])) : '';

/* ---------- AVAILED CALCULATION (UNCHANGED) ---------- */
$availed = [];
foreach ($ledger as $row) {
    if (!is_array($row)) continue;

    $k = $row['faculty_key'] ?? '';
    if ($k === '' && !empty($row['faculty_email'])) {
        $k = firebaseKeyFromEmail($row['faculty_email']);
    }
    if ($k === '') continue;

    $type = strtoupper($row['leave_type'] ?? '');
    if (!in_array($type, ['CL','EL','HPL','OD','CCL','LOP'], true)) continue;

    $days = is_numeric($row['days'] ?? null) ? (float)$row['days'] : 0;

    if (!isset($availed[$k])) {
        $availed[$k] = ['CL'=>0,'EL'=>0,'HPL'=>0,'OD'=>0,'CCL'=>0,'LOP'=>0];
    }
    $availed[$k][$type] += $days;
}

/* ---------- ROW BUILD (UNCHANGED) ---------- */
$rows = [];
foreach ($master as $k => $m) {
    if (!is_array($m)) continue;

    $email = $m['faculty_email'] ?? firebaseEmailFromKey($k);
    $emailNorm = strtolower($email ?? '');

    if ($filterDept && strcasecmp($m['department'] ?? '', $filterDept) !== 0) continue;
    if ($filterEmail && strpos($emailNorm, $filterEmail) === false) continue;

    $ent = [
        'CL' => (float)($m['cl'] ?? 0),
        'EL' => (float)($m['el'] ?? 0),
        'HPL' => (float)($m['hpl'] ?? 0),
        'OD' => (float)($m['od'] ?? 0),
        'CCL' => (float)($m['ccl'] ?? 0),
        'LOP' => (float)($m['lop'] ?? 0),
    ];
    $av = $availed[$k] ?? ['CL'=>0,'EL'=>0,'HPL'=>0,'OD'=>0,'CCL'=>0,'LOP'=>0];

    $bal = [
        'CL' => $ent['CL'] - $av['CL'],
        'EL' => $ent['EL'] - $av['EL'],
        'HPL' => $ent['HPL'] - $av['HPL'],
        'OD' => $ent['OD'] - $av['OD'],
        'CCL' => $ent['CCL'] - $av['CCL'],
        'LOP' => $ent['LOP'] - $av['LOP'],
    ];

    // Get leave history for this faculty
    $leaveHistory = [];
    foreach ($ledger as $id => $row) {
        if (!is_array($row)) continue;
        $rowEmail = $row['faculty_email'] ?? '';
        if (strtolower($rowEmail) === strtolower($email)) {
            $leaveHistory[] = [
                'id' => $id,
                'date' => $row['date'] ?? '',
                'leave_type' => $row['leave_type'] ?? '',
                'days' => $row['days'] ?? 0,
                'session' => $row['session'] ?? '',
                'reason' => $row['reason'] ?? '',
                'createdAt' => $row['createdAt'] ?? 0
            ];
        }
    }
    // Sort by date descending
    usort($leaveHistory, fn($a, $b) => strcmp($b['date'], $a['date']));

    $rows[] = [
        'name' => $m['name'] ?? '',
        'email' => $email,
        'employee_id' => $m['employee_id'] ?? '',
        'department' => $m['department'] ?? '',
        'ent' => $ent,
        'av'  => $av,
        'bal' => $bal,
        'leaveHistory' => $leaveHistory,
    ];
}

usort($rows, fn($a,$b)=>strcmp(strtolower($a['email']), strtolower($b['email'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Leave Balance Report</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

<style>
body { background:#f4f6f9; }
.card { border:none; border-radius:12px; }
.shadow-soft { box-shadow:0 10px 25px rgba(0,0,0,.08); }
.leave-row { display:flex; justify-content:space-between; font-size:.9rem; }
.balance-ok { color:#198754; font-weight:600; }
.balance-low { color:#dc3545; font-weight:600; }
</style>
</head>

<body>

<div class="container my-4">

<div class="card shadow-soft mb-3">
<div class="card-body">
<form class="row g-3">
<div class="col-md-4">
<label class="form-label">Department</label>
<input class="form-control" name="department" value="<?= h($filterDept); ?>">
</div>
<div class="col-md-4">
<label class="form-label">Email contains</label>
<input class="form-control" name="email" value="<?= h($filterEmail); ?>">
</div>
<div class="col-md-4 d-flex align-items-end gap-2">
<button class="btn btn-primary">Filter</button>
<a class="btn btn-outline-secondary" href="leave_balance_report.php">Reset</a>
</div>
</form>
</div>
</div>

<div class="card shadow-soft">
<div class="card-header bg-primary text-white">
<strong>Leave Balance Summary</strong>
</div>

<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover table-bordered align-middle mb-0">
<thead class="table-light">
<tr>
<th style="min-width:220px;">Faculty</th>
<th>Department</th>
<th style="min-width:320px;">Leave Summary (Ent / Av / Bal)</th>
<th>Status</th>
<th style="min-width:120px;">Leave History</th>
</tr>
</thead>

<tbody>
<?php if (!$rows): ?>
<tr><td colspan="5" class="text-center text-muted py-4">No records found</td></tr>
<?php else: foreach ($rows as $r):
$totalBal = ($r['bal']['CL'] ?? 0) + ($r['bal']['EL'] ?? 0) + ($r['bal']['HPL'] ?? 0) + ($r['bal']['OD'] ?? 0) + ($r['bal']['CCL'] ?? 0) + ($r['bal']['LOP'] ?? 0);
$low = $totalBal <= 2;

// Store leave history data in JavaScript variable
echo '<script>window.leaveHistoryData' . str_replace(['@', '.'], ['_', '_'], $r['email']) . ' = ' . json_encode($r['leaveHistory']) . ';</script>';
?>
<tr>

<td>
<div class="fw-semibold"><?= h($r['name']); ?></div>
<div class="small text-muted"><?= h($r['email']); ?></div>
<div class="small text-muted">ID: <?= h($r['employee_id']); ?></div>
</td>

<td><?= h($r['department']); ?></td>

<!-- SAFE UI (NO WARNINGS) -->
<td>
<?php foreach (['CL','EL','HPL','OD','CCL','LOP'] as $t): ?>
<div class="leave-row">
<strong><?= $t; ?></strong>
<span><?= h($r['ent'][$t] ?? 0); ?></span>
<span><?= h($r['av'][$t] ?? 0); ?></span>
<span class="<?= (($r['bal'][$t] ?? 0) <= 1) ? 'balance-low' : 'balance-ok'; ?>">
<?= h($r['bal'][$t] ?? 0); ?>
</span>
</div>
<?php endforeach; ?>
</td>

<td>
<span class="badge <?= $low ? 'bg-danger' : 'bg-success'; ?>">
<?= $low ? 'Low Balance' : 'Sufficient'; ?>
</span>
</td>

<td>
<button class="btn btn-sm btn-outline-primary" onclick="showLeaveHistory('<?= h($r['email']); ?>')">
<i class="bi bi-clock-history"></i> View
</button>
</td>

</tr>
<?php endforeach; endif; ?>
</tbody>
</table>
</div>
</div>
</div>

</div>

<!-- Leave History Modal -->
<div class="modal fade" id="leaveHistoryModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-clock-history"></i> Leave History
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <h6 id="modalFacultyEmail" class="text-muted mb-3"></h6>
        <div id="leaveHistoryContent">
          <div class="text-center text-muted">
            <div class="spinner-border spinner-border-sm" role="status"></div>
            Loading...
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
function showLeaveHistory(email) {
    // Get leave history data from global variable
    var dataKey = 'leaveHistoryData' + email.replace(/[@.]/g, '_');
    var leaveHistory = window[dataKey] || [];
    
    document.getElementById('modalFacultyEmail').textContent = email;
    
    let content = '';
    if (!leaveHistory || leaveHistory.length === 0) {
        content = '<div class="text-center text-muted py-4">No leave records found</div>';
    } else {
        content = '<div class="table-responsive"><table class="table table-sm table-striped"><thead><tr><th>Date</th><th>Type</th><th>Days</th><th>Session</th><th>Reason</th></tr></thead><tbody>';
        
        leaveHistory.forEach(record => {
            const date = record.date || 'N/A';
            const type = record.leave_type || 'N/A';
            const days = record.days || '0';
            const session = record.session || 'N/A';
            const reason = record.reason || '-';
            
            content += '<tr><td>' + date + '</td><td><span class="badge bg-info">' + type + '</span></td><td>' + days + '</td><td>' + session + '</td><td>' + reason + '</td></tr>';
        });
        
        content += '</tbody></table></div>';
    }
    
    document.getElementById('leaveHistoryContent').innerHTML = content;
    
    // Show modal using proper Bootstrap classes
    var modal = document.getElementById('leaveHistoryModal');
    modal.classList.add('show');
    modal.style.display = 'block';
    modal.style.visibility = 'visible';
    modal.style.opacity = '1';
    modal.style.zIndex = '1055';
    document.body.classList.add('modal-open');
    
    // Add backdrop
    var backdrop = document.createElement('div');
    backdrop.className = 'modal-backdrop fade show';
    backdrop.style.zIndex = '1050';
    backdrop.id = 'modal-backdrop';
    document.body.appendChild(backdrop);
    
    // Add close functionality
    var closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(function(btn) {
        btn.onclick = function() {
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
            var backdrop = document.getElementById('modal-backdrop');
            if (backdrop) backdrop.remove();
        };
    });
    
    // Close on backdrop click
    backdrop.onclick = function() {
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
        backdrop.remove();
    };
}
</script>
<?php require_once 'includes/footer.php'; ?>
