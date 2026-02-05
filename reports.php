<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

// Require admin role for this page (before any HTML output)
requireAdmin();

// Get current user email
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;

// Handle report generation (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? '';
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    
    try {
        $reportData = [];
        
        switch ($reportType) {
            case 'leave_summary':
                $reportData = generateLeaveSummary($database, $startDate, $endDate);
                break;
            case 'faculty_workload':
                $reportData = generateFacultyWorkload($database, $startDate, $endDate);
                break;
            case 'lecture_schedule':
                $reportData = generateLectureSchedule($database, $startDate, $endDate);
                break;
            default:
                throw new Exception('Invalid report type');
        }
        
        $_SESSION['report_data'] = $reportData;
        $_SESSION['report_type'] = $reportType;
        $_SESSION['success_message'] = 'Report generated successfully!';
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error generating report: ' . $e->getMessage();
    }
    
    header('Location: reports.php');
    exit;
}

require_once 'includes/header.php';

// Report generation functions
function generateLeaveSummary($database, $startDate, $endDate) {
    $leave_ref = $database->getReference('leave_ledger');
    $leave_snapshot = $leave_ref->getSnapshot();
    $all_leaves = $leave_snapshot->exists() ? $leave_snapshot->getValue() : [];
    
    $summary = [];
    $totalDays = 0;
    
    foreach ($all_leaves as $leave) {
        $leaveDate = $leave['date'] ?? '';
        if ($leaveDate >= $startDate && $leaveDate <= $endDate) {
            $type = $leave['leave_type'] ?? 'Unknown';
            $faculty = $leave['faculty_email'] ?? 'Unknown';
            
            if (!isset($summary[$type])) {
                $summary[$type] = ['count' => 0, 'days' => 0, 'faculty' => []];
            }
            
            $summary[$type]['count']++;
            $summary[$type]['days'] += ($leave['days'] ?? 0);
            $summary[$type]['faculty'][] = $faculty;
            $totalDays += ($leave['days'] ?? 0);
        }
    }
    
    return [
        'type' => 'Leave Summary',
        'period' => "$startDate to $endDate",
        'total_days' => $totalDays,
        'summary' => $summary
    ];
}

function generateFacultyWorkload($database, $startDate, $endDate) {
    // Fetch lecture templates and generate schedule
    $templates_ref = $database->getReference('lecture_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $lecture_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];
    
    $workload = [];
    
    if (!empty($lecture_templates)) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
        
        foreach ($period as $date) {
            $dowFull = strtolower($date->format('l'));
            $dowShort = strtolower($date->format('D'));
            
            foreach ($lecture_templates as $tpl) {
                $tplDay = strtolower(trim($tpl['day'] ?? ''));
                if ($tplDay === $dowFull || $tplDay === $dowShort) {
                    $faculty = $tpl['faculty_email'] ?? 'Unknown';
                    
                    if (!isset($workload[$faculty])) {
                        $workload[$faculty] = ['lectures' => 0, 'subjects' => []];
                    }
                    
                    $workload[$faculty]['lectures']++;
                    $workload[$faculty]['subjects'][] = $tpl['subject'] ?? 'Unknown';
                }
            }
        }
    }
    
    return [
        'type' => 'Faculty Workload',
        'period' => "$startDate to $endDate",
        'workload' => $workload
    ];
}

function generateLectureSchedule($database, $startDate, $endDate) {
    $templates_ref = $database->getReference('lecture_templates');
    $templates_snapshot = $templates_ref->getSnapshot();
    $lecture_templates = $templates_snapshot->exists() ? $templates_snapshot->getValue() : [];
    
    $schedule = [];
    
    if (!empty($lecture_templates)) {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);
        $period = new DatePeriod($start, new DateInterval('P1D'), $end->modify('+1 day'));
        
        foreach ($period as $date) {
            $dowFull = strtolower($date->format('l'));
            $dowShort = strtolower($date->format('D'));
            
            foreach ($lecture_templates as $tpl) {
                $tplDay = strtolower(trim($tpl['day'] ?? ''));
                if ($tplDay === $dowFull || $tplDay === $dowShort) {
                    $schedule[] = [
                        'date' => $date->format('Y-m-d'),
                        'day' => $date->format('l'),
                        'time' => $tpl['time'] ?? '',
                        'subject' => $tpl['subject'] ?? '',
                        'faculty' => $tpl['faculty_email'] ?? '',
                        'room' => $tpl['room'] ?? ''
                    ];
                }
            }
        }
    }
    
    return [
        'type' => 'Lecture Schedule',
        'period' => "$startDate to $endDate",
        'schedule' => $schedule
    ];
}

// Get current report data
$currentReport = $_SESSION['report_data'] ?? null;
$currentReportType = $_SESSION['report_type'] ?? '';
unset($_SESSION['report_data'], $_SESSION['report_type']);
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-file-earmark-bar-graph"></i> Generate Reports
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

    <div class="row">
        <!-- Report Generation Form -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Generate New Report</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Report Type</label>
                            <select name="report_type" class="form-select" required>
                                <option value="">Select report type</option>
                                <option value="leave_summary">Leave Summary</option>
                                <option value="faculty_workload">Faculty Workload</option>
                                <option value="lecture_schedule">Lecture Schedule</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-file-earmark-bar-graph"></i> Generate Report
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Report Display -->
        <div class="col-lg-8">
            <?php if ($currentReport): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><?php echo h($currentReport['type']); ?> Report</h5>
                        <small>Period: <?php echo h($currentReport['period']); ?></small>
                    </div>
                    <div class="card-body">
                        <?php if ($currentReportType === 'leave_summary'): ?>
                            <h6>Leave Summary</h6>
                            <p><strong>Total Leave Days:</strong> <?php echo $currentReport['total_days']; ?></p>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Leave Type</th>
                                            <th>Count</th>
                                            <th>Days</th>
                                            <th>Faculty</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($currentReport['summary'] as $type => $data): ?>
                                            <tr>
                                                <td><?php echo h($type); ?></td>
                                                <td><?php echo $data['count']; ?></td>
                                                <td><?php echo $data['days']; ?></td>
                                                <td><?php echo count(array_unique($data['faculty'])); ?> faculty</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif ($currentReportType === 'faculty_workload'): ?>
                            <h6>Faculty Workload Analysis</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Faculty</th>
                                            <th>Lectures</th>
                                            <th>Subjects</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($currentReport['workload'] as $faculty => $data): ?>
                                            <tr>
                                                <td><?php echo h($faculty); ?></td>
                                                <td><?php echo $data['lectures']; ?></td>
                                                <td><?php echo implode(', ', array_unique($data['subjects'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                        <?php elseif ($currentReportType === 'lecture_schedule'): ?>
                            <h6>Lecture Schedule</h6>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Time</th>
                                            <th>Subject</th>
                                            <th>Faculty</th>
                                            <th>Room</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($currentReport['schedule'] as $lecture): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($lecture['date'])); ?></td>
                                                <td><?php echo h($lecture['day']); ?></td>
                                                <td><?php echo h($lecture['time']); ?></td>
                                                <td><?php echo h($lecture['subject']); ?></td>
                                                <td><?php echo h($lecture['faculty']); ?></td>
                                                <td><?php echo h($lecture['room']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <button onclick="window.print()" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-printer"></i> Print Report
                            </button>
                            <button onclick="exportToCSV()" class="btn btn-outline-success btn-sm">
                                <i class="bi bi-download"></i> Export CSV
                            </button>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-file-earmark-bar-graph text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">No Report Generated</h5>
                        <p class="text-muted">Select a report type and date range to generate a report.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function exportToCSV() {
    // Simple CSV export functionality
    alert('CSV export functionality would be implemented here');
}
</script>

<?php require_once 'includes/footer.php'; ?>
