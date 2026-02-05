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

try {
    // Fetch lecture templates and generate lectures (same as admin dashboard)
    $templates_ref = $database->getReference('lecture_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $lecture_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];

    $generated = [];
    if (!empty($lecture_templates)) {
        $start = new DateTime('today');
        $end = (new DateTime('today'))->modify('+30 days'); // Show 30 days ahead
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
    
    // Sort by date and time
    usort($lectures, function($a, $b) {
        return [$a['date'] ?? '', $a['time'] ?? ''] <=> [$b['date'] ?? '', $b['time'] ?? ''];
    });
    
} catch (Exception $e) {
    $error_message = 'Error loading data: ' . $e->getMessage();
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-chalkboard"></i> My Lecture Schedule
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

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Lecture Assignments</h5>
                <span class="badge bg-light text-dark"><?php echo count($lectures); ?> lectures</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($lectures)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Lecture Assignments</h5>
                    <p class="text-muted">You don't have any lecture assignments scheduled for the next 30 days.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Class/Room</th>
                                <th>Faculty Name</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lectures as $lecture): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-event text-primary me-2"></i>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($lecture['date'])); ?></strong>
                                                <br><small class="text-muted"><?php echo date('l', strtotime($lecture['date'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="bi bi-clock"></i> <?php echo h($lecture['time']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo h($lecture['subject']); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-door-open"></i> <?php echo h($lecture['room']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo h($lecture['name']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $lectureDate = new DateTime($lecture['date']);
                                        $today = new DateTime();
                                        $today->setTime(0, 0, 0);
                                        
                                        if ($lectureDate < $today) {
                                            echo '<span class="badge bg-secondary">Completed</span>';
                                        } elseif ($lectureDate == $today) {
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
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php 
                        $today = new DateTime();
                        $todayLectures = array_filter($lectures, function($l) use ($today) {
                            return $l['date'] === $today->format('Y-m-d');
                        });
                        echo count($todayLectures);
                    ?></h3>
                    <p class="text-muted mb-0">Today's Lectures</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success"><?php 
                        $weekStart = new DateTime('this week');
                        $weekEnd = new DateTime('this week +6 days');
                        $weekLectures = array_filter($lectures, function($l) use ($weekStart, $weekEnd) {
                            $date = new DateTime($l['date']);
                            return $date >= $weekStart && $date <= $weekEnd;
                        });
                        echo count($weekLectures);
                    ?></h3>
                    <p class="text-muted mb-0">This Week</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-info"><?php 
                        $monthStart = new DateTime('first day of this month');
                        $monthEnd = new DateTime('last day of this month');
                        $monthLectures = array_filter($lectures, function($l) use ($monthStart, $monthEnd) {
                            $date = new DateTime($l['date']);
                            return $date >= $monthStart && $date <= $monthEnd;
                        });
                        echo count($monthLectures);
                    ?></h3>
                    <p class="text-muted mb-0">This Month</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
