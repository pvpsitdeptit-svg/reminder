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
$lectures = [];
$invigilation = [];
$leaveEntries = [];

try {
    // Fetch lecture templates and generate lectures (same as admin dashboard)
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

    // Filter lectures for current faculty
    foreach ($generated as $key => $lecture) {
        if (isset($lecture['faculty_email']) && strtolower($lecture['faculty_email']) === strtolower($userEmail)) {
            $lecture['id'] = $key;
            $lectures[] = $lecture;
        }
    }
    
    // Fetch invigilation for current faculty
    $invigilation_ref = $database->getReference('invigilation');
    $invigilation_snapshot = $invigilation_ref->getSnapshot();
    $all_invigilation = $invigilation_snapshot->exists() ? $invigilation_snapshot->getValue() : [];
    
    // Filter invigilation for current faculty
    foreach ($all_invigilation as $key => $inv) {
        if (isset($inv['faculty_email']) && strtolower($inv['faculty_email']) === strtolower($userEmail)) {
            $inv['id'] = $key;
            $invigilation[] = $inv;
        }
    }
    
    // Fetch leave entries for current faculty
    $leave_ref = $database->getReference('leave_ledger');
    $leave_snapshot = $leave_ref->getSnapshot();
    $all_leaves = $leave_snapshot->exists() ? $leave_snapshot->getValue() : [];
    
    // Filter leave entries for current faculty
    foreach ($all_leaves as $key => $leave) {
        if (isset($leave['faculty_email']) && strtolower($leave['faculty_email']) === strtolower($userEmail)) {
            $leave['id'] = $key;
            $leaveEntries[] = $leave;
        }
    }
    
    // Sort by date (newest first)
    usort($lectures, function($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
    
    usort($invigilation, function($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
    
    usort($leaveEntries, function($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });
    
    // Debug: Log data counts
    error_log("Faculty Dashboard Debug - User: " . $userEmail);
    error_log("Faculty Dashboard Debug - Total templates: " . count($lecture_templates));
    error_log("Faculty Dashboard Debug - Generated lectures: " . count($generated));
    error_log("Faculty Dashboard Debug - Lectures found: " . count($lectures));
    error_log("Faculty Dashboard Debug - Invigilation found: " . count($invigilation));
    error_log("Faculty Dashboard Debug - Leave entries found: " . count($leaveEntries));
    
    // Debug: Show sample template data
    if (!empty($lecture_templates)) {
        error_log("Sample template: " . print_r(array_slice($lecture_templates, 0, 1), true));
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading data: ' . $e->getMessage();
    error_log("Faculty Dashboard Error: " . $e->getMessage());
}
?>

<div class="container my-4">

<!-- Dashboard Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold mb-1">Faculty Dashboard</h1>
        <p class="text-muted">Welcome back, <?php echo h($userDisplayName); ?>!</p>
    </div>
    <div class="text-end">
        <span class="badge bg-primary px-3 py-2">
            <i class="bi bi-calendar-check"></i> <?php echo date('F j, Y'); ?>
        </span>
        <span class="badge bg-info px-3 py-2 ms-2">
            <i class="bi bi-person"></i> Faculty
        </span>
        <?php if (isAdmin()): ?>
        <span class="badge bg-warning px-3 py-2 ms-2">
            <i class="bi bi-shield-check"></i> Admin View
        </span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($error_message)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Lecture Duties</h6>
                        <h3 class="mb-0"><?php echo count($lectures); ?></h3>
                        <small class="text-success">
                            <i class="bi bi-calendar-week"></i> This month
                        </small>
                    </div>
                    <div class="text-primary">
                        <i class="bi bi-chalkboard" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Invigilation Duties</h6>
                        <h3 class="mb-0"><?php echo count($invigilation); ?></h3>
                        <small class="text-info">
                            <i class="bi bi-eye"></i> Assigned
                        </small>
                    </div>
                    <div class="text-info">
                        <i class="bi bi-clipboard-check" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card stat-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-2">Leave Entries</h6>
                        <h3 class="mb-0"><?php echo count($leaveEntries); ?></h3>
                        <small class="text-warning">
                            <i class="bi bi-calendar-x"></i> Total
                        </small>
                    </div>
                    <div class="text-warning">
                        <i class="bi bi-calendar3" style="font-size: 2rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="leave_request.php" class="btn btn-outline-primary w-100">
                            <i class="bi bi-calendar-plus"></i> Request Leave
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="view_schedule.php" class="btn btn-outline-success w-100">
                            <i class="bi bi-calendar-week"></i> View Schedule
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="profile.php" class="btn btn-outline-info w-100">
                            <i class="bi bi-person"></i> My Profile
                        </a>
                    </div>
                    <?php if (isAdmin()): ?>
                    <div class="col-md-3">
                        <a href="index.php" class="btn btn-outline-warning w-100">
                            <i class="bi bi-shield-check"></i> Admin Dashboard
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-chalkboard"></i> Recent Lectures</h5>
            </div>
            <div class="card-body">
                <?php if (empty($lectures)): ?>
                    <p class="text-muted">No lecture duties assigned.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        $recent_lectures = array_slice($lectures, 0, 5);
                        foreach ($recent_lectures as $lecture): 
                        ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo h($lecture['subject'] ?? 'N/A'); ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo h($lecture['date'] ?? ''); ?>
                                            <i class="bi bi-clock ms-2"></i> <?php echo h($lecture['time'] ?? ''); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-primary"><?php echo h($lecture['class'] ?? ''); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($lectures) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="view_lectures.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-clipboard-check"></i> Invigilation Duties</h5>
            </div>
            <div class="card-body">
                <?php if (empty($invigilation)): ?>
                    <p class="text-muted">No invigilation duties assigned.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        $recent_invigilation = array_slice($invigilation, 0, 5);
                        foreach ($recent_invigilation as $inv): 
                        ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo h($inv['exam'] ?? 'N/A'); ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo h($inv['date'] ?? ''); ?>
                                            <i class="bi bi-clock ms-2"></i> <?php echo h($inv['time'] ?? ''); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-info"><?php echo h($inv['venue'] ?? ''); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($invigilation) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="view_invigilation.php" class="btn btn-sm btn-outline-info">View All</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-calendar-x"></i> Leave History</h5>
            </div>
            <div class="card-body">
                <?php if (empty($leaveEntries)): ?>
                    <p class="text-muted">No leave entries found.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php 
                        $recent_leaves = array_slice($leaveEntries, 0, 5);
                        foreach ($recent_leaves as $leave): 
                        ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">
                                            <span class="badge bg-<?php echo getLeaveTypeColor($leave['leave_type'] ?? ''); ?>">
                                                <?php echo h($leave['leave_type'] ?? ''); ?>
                                            </span>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar"></i> <?php echo h($leave['date'] ?? ''); ?>
                                            <i class="bi bi-clock ms-2"></i> <?php echo h($leave['session'] ?? ''); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-secondary"><?php echo h($leave['days'] ?? ''); ?> days</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($leaveEntries) > 5): ?>
                        <div class="text-center mt-3">
                            <a href="view_leaves.php" class="btn btn-sm btn-outline-warning">View All</a>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

</div>

<?php require_once 'includes/footer.php'; ?>

<?php
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
