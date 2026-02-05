<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

// Require admin role for this page (before any HTML output)
requireAdmin();

require_once 'includes/header.php';

// Get current user email
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;

// Fetch analytics data
$analyticsData = [];

try {
    // Fetch lecture templates for schedule analysis
    $templates_ref = $database->getReference('lecture_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $lecture_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];
    
    // Fetch invigilation data
    $invigilation_ref = $database->getReference('invigilation');
    $invigilation_snapshot = $invigilation_ref->getSnapshot();
    $invigilation_data = $invigilation_snapshot->exists() ? $invigilation_snapshot->getValue() : [];
    
    // Fetch leave data
    $leave_ref = $database->getReference('leave_ledger');
    $leave_snapshot = $leave_ref->getSnapshot();
    $leave_data = $leave_snapshot->exists() ? $leave_snapshot->getValue() : [];
    
    // Fetch faculty data
    $faculty_ref = $database->getReference('faculty_leave_master');
    $faculty_snapshot = $faculty_ref->getSnapshot();
    $faculty_data = $faculty_snapshot->exists() ? $faculty_snapshot->getValue() : [];
    
    // Process analytics data
    $analyticsData = [
        'total_faculty' => count($faculty_data),
        'total_lectures' => count($lecture_templates),
        'total_invigilation' => count($invigilation_data),
        'total_leave_entries' => count($leave_data),
        'lecture_distribution' => analyzeLectureDistribution($lecture_templates),
        'invigilation_distribution' => analyzeInvigilationDistribution($invigilation_data),
        'leave_statistics' => analyzeLeaveStatistics($leave_data),
        'faculty_workload' => analyzeFacultyWorkload($lecture_templates, $faculty_data),
        'room_utilization' => analyzeRoomUtilization($lecture_templates)
    ];
    
} catch (Exception $e) {
    $error_message = 'Error loading analytics data: ' . $e->getMessage();
}

// Analytics helper functions
function analyzeLectureDistribution($templates) {
    $distribution = [];
    $dayCount = [];
    
    foreach ($templates as $template) {
        $day = $template['day'] ?? 'Unknown';
        $dayCount[$day] = ($dayCount[$day] ?? 0) + 1;
    }
    
    return $dayCount;
}

function analyzeInvigilationDistribution($invigilation) {
    $distribution = [];
    $venueCount = [];
    
    foreach ($invigilation as $duty) {
        $venue = $duty['venue'] ?? 'Unknown';
        $venueCount[$venue] = ($venueCount[$venue] ?? 0) + 1;
    }
    
    return $venueCount;
}

function analyzeLeaveStatistics($leave_data) {
    $statistics = [
        'total_days' => 0,
        'leave_types' => [],
        'monthly_trend' => []
    ];
    
    foreach ($leave_data as $leave) {
        $type = $leave['leave_type'] ?? 'Unknown';
        $days = $leave['days'] ?? 0;
        $month = date('Y-m', strtotime($leave['date'] ?? 'now'));
        
        $statistics['total_days'] += $days;
        $statistics['leave_types'][$type] = ($statistics['leave_types'][$type] ?? 0) + $days;
        $statistics['monthly_trend'][$month] = ($statistics['monthly_trend'][$month] ?? 0) + $days;
    }
    
    return $statistics;
}

function analyzeFacultyWorkload($templates, $faculty) {
    $workload = [];
    
    foreach ($templates as $template) {
        $faculty_email = $template['faculty_email'] ?? '';
        if ($faculty_email) {
            $workload[$faculty_email] = ($workload[$faculty_email] ?? 0) + 1;
        }
    }
    
    return $workload;
}

function analyzeRoomUtilization($templates) {
    $utilization = [];
    
    foreach ($templates as $template) {
        $room = $template['room'] ?? 'Unknown';
        $utilization[$room] = ($utilization[$room] ?? 0) + 1;
    }
    
    return $utilization;
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-graph-up-arrow"></i> Analytics Dashboard
        </h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Overview Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-primary"><?php echo $analyticsData['total_faculty']; ?></h3>
                    <p class="text-muted mb-0">Total Faculty</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-success"><?php echo $analyticsData['total_lectures']; ?></h3>
                    <p class="text-muted mb-0">Weekly Lectures</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-info"><?php echo $analyticsData['total_invigilation']; ?></h3>
                    <p class="text-muted mb-0">Invigilation Duties</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <h3 class="text-warning"><?php echo $analyticsData['total_leave_entries']; ?></h3>
                    <p class="text-muted mb-0">Leave Entries</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Lecture Distribution -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Lecture Distribution by Day</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($analyticsData['lecture_distribution'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Lectures</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalLectures = array_sum($analyticsData['lecture_distribution']);
                                    foreach ($analyticsData['lecture_distribution'] as $day => $count): 
                                    ?>
                                        <tr>
                                            <td><?php echo h($day); ?></td>
                                            <td><?php echo $count; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $totalLectures > 0 ? ($count / $totalLectures * 100) : 0; ?>%">
                                                        <?php echo $totalLectures > 0 ? round($count / $totalLectures * 100) : 0; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No lecture data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Leave Statistics -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Leave Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Total Leave Days: <?php echo $analyticsData['leave_statistics']['total_days']; ?></h6>
                    </div>
                    
                    <?php if (!empty($analyticsData['leave_statistics']['leave_types'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Leave Type</th>
                                        <th>Days</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $totalDays = $analyticsData['leave_statistics']['total_days'];
                                    foreach ($analyticsData['leave_statistics']['leave_types'] as $type => $days): 
                                    ?>
                                        <tr>
                                            <td><?php echo h($type); ?></td>
                                            <td><?php echo $days; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-warning" role="progressbar" 
                                                         style="width: <?php echo $totalDays > 0 ? ($days / $totalDays * 100) : 0; ?>%">
                                                        <?php echo $totalDays > 0 ? round($days / $totalDays * 100) : 0; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No leave data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Faculty Workload -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Faculty Workload Analysis</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($analyticsData['faculty_workload'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Faculty Email</th>
                                        <th>Lectures/Week</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    arsort($analyticsData['faculty_workload']);
                                    foreach ($analyticsData['faculty_workload'] as $email => $count): 
                                    ?>
                                        <tr>
                                            <td><?php echo h($email); ?></td>
                                            <td><?php echo $count; ?></td>
                                            <td>
                                                <?php 
                                                if ($count > 20) {
                                                    echo '<span class="badge bg-danger">High</span>';
                                                } elseif ($count > 15) {
                                                    echo '<span class="badge bg-warning">Medium</span>';
                                                } else {
                                                    echo '<span class="badge bg-success">Normal</span>';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No workload data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Room Utilization -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Room Utilization</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($analyticsData['room_utilization'])): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Room</th>
                                        <th>Lectures/Week</th>
                                        <th>Utilization</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    arsort($analyticsData['room_utilization']);
                                    $maxUtilization = max($analyticsData['room_utilization']);
                                    foreach ($analyticsData['room_utilization'] as $room => $count): 
                                    ?>
                                        <tr>
                                            <td><?php echo h($room); ?></td>
                                            <td><?php echo $count; ?></td>
                                            <td>
                                                <div class="progress" style="height: 20px;">
                                                    <div class="progress-bar bg-info" role="progressbar" 
                                                         style="width: <?php echo $maxUtilization > 0 ? ($count / $maxUtilization * 100) : 0; ?>%">
                                                        <?php echo $maxUtilization > 0 ? round($count / $maxUtilization * 100) : 0; ?>%
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No room utilization data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Options -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Export Analytics</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <button onclick="exportToCSV('lecture_distribution')" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-download"></i> Export Lecture Distribution
                        </button>
                        <button onclick="exportToCSV('leave_statistics')" class="btn btn-outline-warning btn-sm">
                            <i class="bi bi-download"></i> Export Leave Statistics
                        </button>
                        <button onclick="exportToCSV('faculty_workload')" class="btn btn-outline-success btn-sm">
                            <i class="bi bi-download"></i> Export Faculty Workload
                        </button>
                        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-printer"></i> Print Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToCSV(type) {
    // Simple CSV export functionality
    let data = [];
    
    switch(type) {
        case 'lecture_distribution':
            data = <?php echo json_encode($analyticsData['lecture_distribution']); ?>;
            break;
        case 'leave_statistics':
            data = <?php echo json_encode($analyticsData['leave_statistics']); ?>;
            break;
        case 'faculty_workload':
            data = <?php echo json_encode($analyticsData['faculty_workload']); ?>;
            break;
    }
    
    console.log('Exporting ' + type + ':', data);
    alert('CSV export for ' + type + ' would be implemented here');
}
</script>

<?php require_once 'includes/footer.php'; ?>
