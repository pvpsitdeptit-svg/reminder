<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

// Require faculty role (or admin for oversight) before any HTML output
requireFaculty();

require_once 'includes/header.php';

// Get current user email
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;

// Fetch data for current faculty only
$leaveEntries = [];
$pendingRequests = [];

try {
    // Fetch approved leaves from leave_ledger
    $leave_ref = $database->getReference('leave_ledger');
    $leave_snapshot = $leave_ref->getSnapshot();
    $all_leaves = $leave_snapshot->exists() ? $leave_snapshot->getValue() : [];
    
    // Filter approved leave entries for current faculty
    foreach ($all_leaves as $key => $leave) {
        if (isset($leave['faculty_email']) && strtolower($leave['faculty_email']) === strtolower($userEmail)) {
            $leave['id'] = $key;
            $leave['status'] = $leave['status'] ?? 'approved';
            $leaveEntries[] = $leave;
        }
    }
    
    // Fetch pending requests from leave_requests
    $requests_ref = $database->getReference('leave_requests');
    $requests_snapshot = $requests_ref->getSnapshot();
    $all_requests = $requests_snapshot->exists() ? $requests_snapshot->getValue() : [];
    
    // Filter pending requests for current faculty
    foreach ($all_requests as $key => $request) {
        if (isset($request['faculty_email']) && strtolower($request['faculty_email']) === strtolower($userEmail)) {
            $request['id'] = $key;
            $request['status'] = $request['status'] ?? 'pending';
            $pendingRequests[] = $request;
        }
    }
    
    // Combine all entries
    $allEntries = array_merge($pendingRequests, $leaveEntries);
    
    // Sort by date (newest first)
    usort($allEntries, function($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
    
} catch (Exception $e) {
    $error_message = 'Error loading data: ' . $e->getMessage();
}

// Helper function for leave type colors
function getLeaveTypeColor($leaveType) {
    switch($leaveType) {
        case 'CL': return 'primary';
        case 'EL': return 'success';
        case 'ML': return 'danger';
        case 'HPL': return 'warning';
        case 'OD': return 'info';
        case 'CCL': return 'secondary';
        case 'LOP': return 'dark';
        default: return 'secondary';
    }
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-calendar-x"></i> My Leave History
        </h1>
        <div>
            <a href="leave_request.php" class="btn btn-primary me-2">
                <i class="bi bi-plus-circle"></i> Request Leave
            </a>
            <a href="faculty_dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Leave History & Status</h5>
                <div>
                    <span class="badge bg-warning me-2"><?php echo count($pendingRequests); ?> pending</span>
                    <span class="badge bg-success"><?php echo count($leaveEntries); ?> approved</span>
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($allEntries)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Leave History</h5>
                    <p class="text-muted">You haven't taken any leave yet.</p>
                    <a href="leave_request.php" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Request Your First Leave
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Leave Type</th>
                                <th>Session</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allEntries as $leave): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-event text-warning me-2"></i>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($leave['date'])); ?></strong>
                                                <?php if (isset($leave['to_date']) && $leave['to_date'] !== $leave['date']): ?>
                                                    <br><small class="text-muted">Range: <?php echo h($leave['to_date']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo getLeaveTypeColor($leave['leave_type'] ?? ''); ?>">
                                            <?php echo h($leave['leave_type'] ?? ''); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        $session = h($leave['session'] ?? '');
                                        if ($session === 'FULL') {
                                            echo '<span class="badge bg-success">FULL</span>';
                                        } elseif ($session === 'FN') {
                                            echo '<span class="badge bg-warning">FN</span>';
                                        } elseif ($session === 'AN') {
                                            echo '<span class="badge bg-info">AN</span>';
                                        } else {
                                            echo $session;
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo h($leave['days'] ?? ''); ?> days</span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; word-wrap: break-word;">
                                            <?php echo h($leave['reason'] ?? 'No reason provided'); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php 
                                        $status = $leave['status'] ?? 'pending';
                                        if ($status === 'pending') {
                                            echo '<span class="badge bg-warning"><i class="bi bi-clock"></i> Pending</span>';
                                        } elseif ($status === 'approved') {
                                            echo '<span class="badge bg-success"><i class="bi bi-check-circle"></i> Approved</span>';
                                        } elseif ($status === 'rejected') {
                                            echo '<span class="badge bg-danger"><i class="bi bi-x-circle"></i> Rejected</span>';
                                        } else {
                                            echo '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Leave Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?php echo count($pendingRequests); ?></h3>
                    <p class="text-muted mb-0">Pending Requests</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success"><?php echo count($leaveEntries); ?></h3>
                    <p class="text-muted mb-0">Approved Leaves</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php 
                        $totalDays = array_sum(array_column($allEntries, 'days'));
                        echo $totalDays;
                    ?></h3>
                    <p class="text-muted mb-0">Total Leave Days</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-info"><?php 
                        $currentYear = date('Y');
                        $yearLeaves = array_filter($allEntries, function($l) use ($currentYear) {
                            return date('Y', strtotime($l['date'])) == $currentYear;
                        });
                        echo count($yearLeaves);
                    ?></h3>
                    <p class="text-muted mb-0">This Year</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Balance Information -->
    <div class="alert alert-info mt-4">
        <h5><i class="bi bi-info-circle"></i> Leave Balance Information</h5>
        <div class="row">
            <div class="col-md-6">
                <strong>Annual Leave Entitlement:</strong>
                <ul class="mb-0">
                    <li>Casual Leave (CL): 12 days</li>
                    <li>Earned Leave (EL): 15 days</li>
                    <li>Medical Leave (ML): 12 days</li>
                    <li>Half-pay Leave (HPL): 15 days</li>
                </ul>
            </div>
            <div class="col-md-6">
                <strong>Current Usage:</strong>
                <ul class="mb-0">
                    <li>CL Used: <?php echo array_sum(array_column(array_filter($leaveEntries, function($l) { return ($l['leave_type'] ?? '') === 'CL'; }), 'days')); ?> days</li>
                    <li>EL Used: <?php echo array_sum(array_column(array_filter($leaveEntries, function($l) { return ($l['leave_type'] ?? '') === 'EL'; }), 'days')); ?> days</li>
                    <li>ML Used: <?php echo array_sum(array_column(array_filter($leaveEntries, function($l) { return ($l['leave_type'] ?? '') === 'ML'; }), 'days')); ?> days</li>
                    <li>HPL Used: <?php echo array_sum(array_column(array_filter($leaveEntries, function($l) { return ($l['leave_type'] ?? '') === 'HPL'; }), 'days')); ?> days</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
