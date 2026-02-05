<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

// Require admin role for this page (before any HTML output)
requireAdmin();

// Get current user email
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;

// Handle form submission for approve/reject (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = $_POST['request_id'] ?? '';
    
    try {
        if ($action === 'approve') {
            // Move from leave_requests to leave_ledger
            $request_ref = $database->getReference('leave_requests/' . $requestId);
            $request_snapshot = $request_ref->getSnapshot();
            
            if ($request_snapshot->exists()) {
                $requestData = $request_snapshot->getValue();
                
                // Add to leave_ledger
                $leaveData = [
                    'faculty_email' => $requestData['faculty_email'],
                    'leave_type' => $requestData['leave_type'],
                    'session' => $requestData['session'],
                    'date' => $requestData['date'] ?? $requestData['from_date'] ?? date('Y-m-d'),
                    'to_date' => $requestData['to_date'],
                    'days' => $requestData['days'],
                    'reason' => $requestData['reason'],
                    'status' => 'approved',
                    'approved_by' => $userEmail,
                    'approved_at' => time(),
                    'created_at' => $requestData['requested_at']
                ];
                
                $database->getReference('leave_ledger')->push($leaveData);
                
                // Remove from leave_requests
                $request_ref->remove();
                
                $_SESSION['success_message'] = 'Leave request approved successfully!';
            }
        } elseif ($action === 'reject') {
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            
            // Update request with rejection info
            $updateData = [
                'status' => 'rejected',
                'rejected_by' => $userEmail,
                'rejected_at' => time(),
                'rejection_reason' => $rejection_reason
            ];
            
            $database->getReference('leave_requests/' . $requestId)->update($updateData);
            
            $_SESSION['success_message'] = 'Leave request rejected!';
        }
        
        header('Location: manage_leave_requests.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error processing request: ' . $e->getMessage();
        header('Location: manage_leave_requests.php');
        exit;
    }
}

require_once 'includes/header.php';

// Fetch leave requests
$leaveRequests = [];
try {
    $requests_ref = $database->getReference('leave_requests');
    $requests_snapshot = $requests_ref->getSnapshot();
    $all_requests = $requests_snapshot->exists() ? $requests_snapshot->getValue() : [];
    
    foreach ($all_requests as $key => $request) {
        if ($request['status'] === 'pending') {
            $request['id'] = $key;
            $leaveRequests[] = $request;
        }
    }
    
    // Sort by requested date (newest first)
    usort($leaveRequests, function($a, $b) {
        return ($b['requested_at'] ?? 0) - ($a['requested_at'] ?? 0);
    });
    
} catch (Exception $e) {
    $error_message = 'Error loading requests: ' . $e->getMessage();
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-clipboard-check"></i> Manage Leave Requests
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Debug Information (temporary) -->
    <?php if (!empty($leaveRequests)): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <h6><i class="bi bi-bug"></i> Debug - Leave Request Data Structure</h6>
            <details>
                <summary>Click to view sample data structure</summary>
                <pre class="small"><?php echo h(print_r(array_slice($leaveRequests, 0, 1), true)); ?></pre>
            </details>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Pending Leave Requests</h5>
                <span class="badge bg-dark"><?php echo count($leaveRequests); ?> requests</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($leaveRequests)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-check text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Pending Requests</h5>
                    <p class="text-muted">All leave requests have been processed.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Faculty</th>
                                <th>Leave Type</th>
                                <th>Date Range</th>
                                <th>Days</th>
                                <th>Reason</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaveRequests as $request): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo h($request['faculty_email']); ?></strong>
                                            <br><small class="text-muted">Requested: <?php echo date('M j, Y H:i', $request['requested_at']); ?></small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo h($request['leave_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo date('M j, Y', strtotime($request['date'] ?? $request['from_date'] ?? '1970-01-01')); ?></strong>
                                            <?php if (($request['to_date'] ?? '') !== ($request['date'] ?? $request['from_date'] ?? '')): ?>
                                                <br><small class="text-muted">to <?php echo date('M j, Y', strtotime($request['to_date'])); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?php echo $request['days']; ?> days</span>
                                    </td>
                                    <td>
                                        <div style="max-width: 200px; word-wrap: break-word;">
                                            <?php echo h($request['reason']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', $request['requested_at']); ?>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this leave request?')">
                                                    <i class="bi bi-check-circle"></i> Approve
                                                </button>
                                            </form>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="action" value="reject">
                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this leave request?')">
                                                    <i class="bi bi-x-circle"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?php echo count($leaveRequests); ?></h3>
                    <p class="text-muted mb-0">Pending Requests</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success">
                        <?php 
                        $approved_ref = $database->getReference('leave_ledger');
                        $approved_snapshot = $approved_ref->getSnapshot();
                        $approved_leaves = $approved_snapshot->exists() ? $approved_snapshot->getValue() : [];
                        echo count($approved_leaves);
                        ?>
                    </h3>
                    <p class="text-muted mb-0">Approved Leaves</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-danger">
                        <?php 
                        $rejected_ref = $database->getReference('leave_requests');
                        $rejected_snapshot = $rejected_ref->getSnapshot();
                        $all_requests = $rejected_snapshot->exists() ? $rejected_snapshot->getValue() : [];
                        $rejected_count = 0;
                        foreach ($all_requests as $request) {
                            if (($request['status'] ?? '') === 'rejected') {
                                $rejected_count++;
                            }
                        }
                        echo $rejected_count;
                        ?>
                    </h3>
                    <p class="text-muted mb-0">Rejected Requests</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
