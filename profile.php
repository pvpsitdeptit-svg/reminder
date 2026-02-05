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

// Fetch faculty data
$facultyData = [];
try {
    $faculty_ref = $database->getReference('faculty_leave_master');
    $faculty_snapshot = $faculty_ref->getSnapshot();
    $all_faculty = $faculty_snapshot->exists() ? $faculty_snapshot->getValue() : [];
    
    // Find current faculty data
    foreach ($all_faculty as $key => $faculty) {
        if (isset($faculty['faculty_email']) && strtolower($faculty['faculty_email']) === strtolower($userEmail)) {
            $facultyData = $faculty;
            $facultyData['id'] = $key;
            break;
        }
    }
} catch (Exception $e) {
    $error_message = 'Error loading faculty data: ' . $e->getMessage();
}

// Fetch statistics
$stats = [
    'total_lectures' => 0,
    'total_invigilation' => 0,
    'total_leave_days' => 0,
    'this_month_lectures' => 0,
    'this_month_invigilation' => 0
];

try {
    // Fetch lectures
    $templates_ref = $database->getReference('lecture_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $lecture_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];

    $generated = [];
    if (!empty($lecture_templates)) {
        $start = new DateTime('today');
        $end = (new DateTime('today'))->modify('+30 days');
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
                        'faculty_email' => $tpl['faculty_email'] ?? '',
                        'subject' => $tpl['subject'] ?? '',
                        'room' => $tpl['room'] ?? ''
                    ];
                }
            }
        }
    }

    // Filter lectures for current faculty
    foreach ($generated as $lecture) {
        if (isset($lecture['faculty_email']) && strtolower($lecture['faculty_email']) === strtolower($userEmail)) {
            $stats['total_lectures']++;
            
            $lectureDate = new DateTime($lecture['date']);
            $currentMonth = new DateTime('first day of this month');
            if ($lectureDate >= $currentMonth) {
                $stats['this_month_lectures']++;
            }
        }
    }
    
    // Fetch invigilation
    $invigilation_ref = $database->getReference('invigilation');
    $invigilation_snapshot = $invigilation_ref->getSnapshot();
    $all_invigilation = $invigilation_snapshot->exists() ? $invigilation_snapshot->getValue() : [];
    
    foreach ($all_invigilation as $inv) {
        if (isset($inv['faculty_email']) && strtolower($inv['faculty_email']) === strtolower($userEmail)) {
            $stats['total_invigilation']++;
            
            $invDate = new DateTime($inv['date']);
            $currentMonth = new DateTime('first day of this month');
            if ($invDate >= $currentMonth) {
                $stats['this_month_invigilation']++;
            }
        }
    }
    
    // Fetch leave entries
    $leave_ref = $database->getReference('leave_ledger');
    $leave_snapshot = $leave_ref->getSnapshot();
    $all_leaves = $leave_snapshot->exists() ? $leave_snapshot->getValue() : [];
    
    foreach ($all_leaves as $leave) {
        if (isset($leave['faculty_email']) && strtolower($leave['faculty_email']) === strtolower($userEmail)) {
            $stats['total_leave_days'] += ($leave['days'] ?? 0);
        }
    }
    
} catch (Exception $e) {
    $error_message = 'Error loading statistics: ' . $e->getMessage();
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-person"></i> My Profile
        </h1>
        <a href="faculty_dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Profile Information -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="user-avatar mx-auto" style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <?php echo strtoupper(substr($userDisplayName, 0, 1)); ?>
                        </div>
                    </div>
                    <h4><?php echo h($userDisplayName); ?></h4>
                    <p class="text-muted"><?php echo h($userEmail); ?></p>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p><strong>Name:</strong> <?php echo h($facultyData['name'] ?? 'Not available'); ?></p>
                        <p><strong>Department:</strong> <?php echo h($facultyData['department'] ?? 'Not available'); ?></p>
                        <p><strong>Employee ID:</strong> <?php echo h($facultyData['employee_id'] ?? 'Not available'); ?></p>
                        <p><strong>Designation:</strong> <?php echo h($facultyData['designation'] ?? 'Not available'); ?></p>
                        <p><strong>Join Date:</strong> <?php echo isset($facultyData['join_date']) ? date('M j, Y', strtotime($facultyData['join_date'])) : 'Not available'; ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Statistics -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Activity Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                <div>
                                    <h6 class="mb-1">Total Lectures</h6>
                                    <small class="text-muted">Next 30 days</small>
                                </div>
                                <h3 class="text-primary mb-0"><?php echo $stats['total_lectures']; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                <div>
                                    <h6 class="mb-1">This Month</h6>
                                    <small class="text-muted">Lectures assigned</small>
                                </div>
                                <h3 class="text-success mb-0"><?php echo $stats['this_month_lectures']; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                <div>
                                    <h6 class="mb-1">Invigilation</h6>
                                    <small class="text-muted">Total duties</small>
                                </div>
                                <h3 class="text-info mb-0"><?php echo $stats['total_invigilation']; ?></h3>
                            </div>
                        </div>
                        <div class="col-md-6 mt-3">
                            <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                                <div>
                                    <h6 class="mb-1">Leave Taken</h6>
                                    <small class="text-muted">Total days</small>
                                </div>
                                <h3 class="text-warning mb-0"><?php echo $stats['total_leave_days']; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Leave Balance -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Leave Balance</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h5 class="text-primary mb-1">CL</h5>
                                <h4 class="mb-0">12</h4>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h5 class="text-success mb-1">EL</h5>
                                <h4 class="mb-0">15</h4>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h5 class="text-danger mb-1">ML</h5>
                                <h4 class="mb-0">12</h4>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3 border rounded">
                                <h5 class="text-warning mb-1">HPL</h5>
                                <h4 class="mb-0">15</h4>
                                <small class="text-muted">Total</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <a href="view_leaves.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-eye"></i> View Leave History
                        </a>
                        <a href="leave_request.php" class="btn btn-primary btn-sm ms-2">
                            <i class="bi bi-plus-circle"></i> Request Leave
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <a href="view_lectures.php" class="btn btn-outline-success w-100">
                                <i class="bi bi-chalkboard"></i> View Lectures
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="view_invigilation.php" class="btn btn-outline-info w-100">
                                <i class="bi bi-clipboard-check"></i> View Invigilation
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="view_schedule.php" class="btn btn-outline-primary w-100">
                                <i class="bi bi-calendar-week"></i> View Schedule
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="leave_request.php" class="btn btn-outline-warning w-100">
                                <i class="bi bi-calendar-plus"></i> Request Leave
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.user-avatar {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}
</style>

<?php require_once 'includes/footer.php'; ?>
