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
    // Fetch lecture templates and generate lectures
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
            $lecture['type'] = 'lecture';
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
            $inv['type'] = 'invigilation';
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
            $leave['type'] = 'leave';
            $leaveEntries[] = $leave;
        }
    }
    
    // Combine all activities
    $allActivities = array_merge($lectures, $invigilation, $leaveEntries);
    
    // Sort by date and time
    usort($allActivities, function($a, $b) {
        return [$a['date'] ?? '', $a['time'] ?? ''] <=> [$b['date'] ?? '', $b['time'] ?? ''];
    });
    
} catch (Exception $e) {
    $error_message = 'Error loading data: ' . $e->getMessage();
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-calendar-week"></i> My Complete Schedule
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

    <!-- Filter Options -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h6 class="mb-0">Filter Activities:</h6>
                </div>
                <div class="col-md-6">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" onclick="filterActivities('all')">All</button>
                        <button type="button" class="btn btn-outline-success" onclick="filterActivities('lecture')">Lectures</button>
                        <button type="button" class="btn btn-outline-info" onclick="filterActivities('invigilation')">Invigilation</button>
                        <button type="button" class="btn btn-outline-warning" onclick="filterActivities('leave')">Leave</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Schedule Overview</h5>
                <span class="badge bg-light text-dark"><?php echo count($allActivities); ?> activities</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($allActivities)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Scheduled Activities</h5>
                    <p class="text-muted">You don't have any activities scheduled for the next 30 days.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="scheduleTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Activity Type</th>
                                <th>Details</th>
                                <th>Venue/Room</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($allActivities as $activity): ?>
                                <tr class="activity-row" data-type="<?php echo h($activity['type']); ?>">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <?php if ($activity['type'] === 'lecture'): ?>
                                                <i class="bi bi-chalkboard text-success me-2"></i>
                                            <?php elseif ($activity['type'] === 'invigilation'): ?>
                                                <i class="bi bi-clipboard-check text-info me-2"></i>
                                            <?php else: ?>
                                                <i class="bi bi-calendar-x text-warning me-2"></i>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($activity['date'])); ?></strong>
                                                <br><small class="text-muted"><?php echo date('l', strtotime($activity['date'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-clock"></i> <?php echo h($activity['time'] ?? 'All Day'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($activity['type'] === 'lecture'): ?>
                                            <span class="badge bg-success">Lecture</span>
                                        <?php elseif ($activity['type'] === 'invigilation'): ?>
                                            <span class="badge bg-info">Invigilation</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Leave</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($activity['type'] === 'lecture'): ?>
                                            <strong><?php echo h($activity['subject'] ?? 'N/A'); ?></strong>
                                            <br><small class="text-muted"><?php echo h($activity['name'] ?? 'N/A'); ?></small>
                                        <?php elseif ($activity['type'] === 'invigilation'): ?>
                                            <strong><?php echo h($activity['exam'] ?? 'N/A'); ?></strong>
                                            <br><small class="text-muted"><?php echo h($activity['subject'] ?? 'N/A'); ?></small>
                                        <?php else: ?>
                                            <strong><?php echo h($activity['leave_type'] ?? 'N/A'); ?></strong>
                                            <br><small class="text-muted"><?php echo h($activity['reason'] ?? 'No reason'); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($activity['type'] === 'lecture'): ?>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-door-open"></i> <?php echo h($activity['room'] ?? 'N/A'); ?>
                                            </span>
                                        <?php elseif ($activity['type'] === 'invigilation'): ?>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-building"></i> <?php echo h($activity['venue'] ?? 'N/A'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">N/A</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $activityDate = new DateTime($activity['date']);
                                        $today = new DateTime();
                                        $today->setTime(0, 0, 0);
                                        
                                        if ($activity['type'] === 'leave') {
                                            echo '<span class="badge bg-warning">On Leave</span>';
                                        } elseif ($activityDate < $today) {
                                            echo '<span class="badge bg-secondary">Completed</span>';
                                        } elseif ($activityDate == $today) {
                                            echo '<span class="badge bg-success">Today</span>';
                                        } else {
                                            echo '<span class="badge bg-primary">Upcoming</span>';
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

    <!-- Summary Statistics -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success"><?php echo count($lectures); ?></h3>
                    <p class="text-muted mb-0">Lectures</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-info"><?php echo count($invigilation); ?></h3>
                    <p class="text-muted mb-0">Invigilation</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?php echo count($leaveEntries); ?></h3>
                    <p class="text-muted mb-0">Leave Days</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php 
                        $today = new DateTime();
                        $todayActivities = array_filter($allActivities, function($a) use ($today) {
                            return $a['date'] === $today->format('Y-m-d');
                        });
                        echo count($todayActivities);
                    ?></h3>
                    <p class="text-muted mb-0">Today</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function filterActivities(type) {
    const rows = document.querySelectorAll('.activity-row');
    const buttons = document.querySelectorAll('.btn-group button');
    
    // Update button states
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter rows
    rows.forEach(row => {
        if (type === 'all' || row.dataset.type === type) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
