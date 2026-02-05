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
$invigilation = [];

try {
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
    
    // Sort by date and time
    usort($invigilation, function($a, $b) {
        return [$a['date'] ?? '', $a['time'] ?? ''] <=> [$b['date'] ?? '', $b['time'] ?? ''];
    });
    
} catch (Exception $e) {
    $error_message = 'Error loading data: ' . $e->getMessage();
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-clipboard-check"></i> My Invigilation Duties
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
        <div class="card-header bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Invigilation Assignments</h5>
                <span class="badge bg-light text-dark"><?php echo count($invigilation); ?> duties</span>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($invigilation)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                    <h5 class="text-muted mt-3">No Invigilation Duties</h5>
                    <p class="text-muted">You don't have any invigilation duties assigned.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Exam</th>
                                <th>Venue</th>
                                <th>Subject</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invigilation as $inv): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-calendar-event text-info me-2"></i>
                                            <div>
                                                <strong><?php echo date('M j, Y', strtotime($inv['date'])); ?></strong>
                                                <br><small class="text-muted"><?php echo date('l', strtotime($inv['date'])); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <i class="bi bi-clock"></i> <?php echo h($inv['time']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo h($inv['exam'] ?? 'N/A'); ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-building"></i> <?php echo h($inv['venue'] ?? $inv['room'] ?? 'Not assigned'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo h($inv['subject'] ?? $inv['exam'] ?? 'N/A'); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $invDate = new DateTime($inv['date']);
                                        $today = new DateTime();
                                        $today->setTime(0, 0, 0);
                                        
                                        if ($invDate < $today) {
                                            echo '<span class="badge bg-secondary">Completed</span>';
                                        } elseif ($invDate == $today) {
                                            echo '<span class="badge bg-success">Today</span>';
                                        } else {
                                            echo '<span class="badge bg-warning">Upcoming</span>';
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
                    <h3 class="text-info"><?php 
                        $today = new DateTime();
                        $todayInv = array_filter($invigilation, function($i) use ($today) {
                            return $i['date'] === $today->format('Y-m-d');
                        });
                        echo count($todayInv);
                    ?></h3>
                    <p class="text-muted mb-0">Today's Duties</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?php 
                        $weekStart = new DateTime('this week');
                        $weekEnd = new DateTime('this week +6 days');
                        $weekInv = array_filter($invigilation, function($i) use ($weekStart, $weekEnd) {
                            $date = new DateTime($i['date']);
                            return $date >= $weekStart && $date <= $weekEnd;
                        });
                        echo count($weekInv);
                    ?></h3>
                    <p class="text-muted mb-0">This Week</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php 
                        $monthStart = new DateTime('first day of this month');
                        $monthEnd = new DateTime('last day of this month');
                        $monthInv = array_filter($invigilation, function($i) use ($monthStart, $monthEnd) {
                            $date = new DateTime($i['date']);
                            return $date >= $monthStart && $date <= $monthEnd;
                        });
                        echo count($monthInv);
                    ?></h3>
                    <p class="text-muted mb-0">This Month</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Important Information -->
    <div class="alert alert-warning mt-4">
        <h5><i class="bi bi-exclamation-triangle"></i> Important Information</h5>
        <ul class="mb-0">
            <li>Please arrive at the venue 15 minutes before the exam starts</li>
            <li>Carry your official ID card for verification</li>
            <li>Ensure proper seating arrangement and discipline</li>
            <li>Report any irregularities to the chief invigilator immediately</li>
            <li>Complete the invigilation report form after the exam</li>
        </ul>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
