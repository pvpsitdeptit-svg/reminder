<?php
require_once 'includes/header.php';
require_once 'config/firebase.php';

// Helper function for room utilization
function calculateRoomUtilization($lectures) {
    $room_usage = [];
    foreach ($lectures as $lecture) {
        $room = $lecture['room'] ?? 'Unknown';
        if (!isset($room_usage[$room])) {
            $room_usage[$room] = 0;
        }
        $room_usage[$room]++;
    }
    return $room_usage;
}

// Your existing functionality - keep all current logic
$lectures = [];
$invigilation = [];

try {
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

    usort($generated, fn($a, $b) => [$a['date'], $a['time']] <=> [$b['date'], $b['time']]);
    $lectures = $generated;

    $invigilation_ref = $database->getReference('invigilation');
    $invigilation_snapshot = $invigilation_ref->getSnapshot();
    $invigilation = $invigilation_snapshot->exists() ? $invigilation_snapshot->getValue() : [];

} catch (Exception $e) {
    $error = $e->getMessage();
}

// NEW: Enhanced Analytics Integration
$analytics_data = [];
$optimization_suggestions = [];
$conflict_alerts = [];

try {
    // Load Advanced Analytics if available
    if (file_exists('includes/AdvancedAnalyticsAI.php')) {
        require_once 'includes/AdvancedAnalyticsAI.php';
        $analytics = new AdvancedAnalyticsAI();
        
        // Analyze current schedule data
        $schedule_data = [
            'lectures' => $lectures,
            'invigilation' => $invigilation,
            'faculty_count' => count(array_unique(array_column($lectures,'faculty_id'))),
            'room_utilization' => calculateRoomUtilization($lectures)
        ];
        
        $analytics_data = $analytics->generateAnalyticsReport($schedule_data, 'comprehensive');
        $optimization_suggestions = $analytics->generateOptimizationSuggestions($schedule_data);
        $conflict_alerts = $analytics->detectPotentialConflicts($schedule_data);
    }
} catch (Exception $e) {
    $analytics_error = $e->getMessage();
}

// NEW: Quantum Optimization Integration
$quantum_optimization = [];
try {
    if (file_exists('includes/QuantumInspiredOptimizationEngine.php')) {
        require_once 'includes/QuantumInspiredOptimizationEngine.php';
        $quantum = new QuantumInspiredOptimizationEngine();
        
        // Optimize current lecture schedule
        $optimization_result = $quantum->optimizeSchedule($lectures, [
            'max_hours_per_day' => 8,
            'min_gap_between_lectures' => 15
        ], [
            'minimize_conflicts' => 0.9,
            'maximize_room_utilization' => 0.8
        ]);
        
        $quantum_optimization = $optimization_result;
    }
} catch (Exception $e) {
    $quantum_error = $e->getMessage();
}
?>

<div class="container my-4">

<!-- Enhanced Dashboard Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h2 fw-bold mb-1">
            <i class="bi bi-speedometer2 me-2"></i>Enhanced Admin Dashboard
        </h1>
        <p class="text-muted">Faculty Management with AI-Powered Analytics</p>
    </div>
    <div class="text-end">
        <div class="btn-group" role="group">
            <a href="index.php" class="btn btn-outline-secondary">
                <i class="bi bi-house"></i> Classic View
            </a>
            <a href="dashboard.php" class="btn btn-primary active">
                <i class="bi bi-cpu"></i> Enhanced View
            </a>
            <a href="phase1_integration_test.php" class="btn btn-info">
                <i class="bi bi-gear"></i> Test Systems
            </a>
        </div>
    </div>
</div>

<!-- Enhanced STATS with AI Insights -->
<div class="row g-3 mb-4">
<div class="col-md-3">
<div class="card stat-card stat-1 shadow-soft">
<div class="card-body d-flex justify-content-between align-items-center">
<div>
<div class="small">Total Lectures</div>
<h2><?= count($lectures); ?></h2>
<?php if (isset($analytics_data['data_summary']['total_records'])): ?>
<small class="text-success">
    <i class="bi bi-arrow-up"></i> <?= $analytics_data['data_summary']['total_records']; ?> analyzed
</small>
<?php endif; ?>
</div>
<i class="bi bi-book fs-1 opacity-75"></i>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card stat-card stat-2 shadow-soft">
<div class="card-body d-flex justify-content-between align-items-center">
<div>
<div class="small">Invigilation Duties</div>
<h2><?= count($invigilation); ?></h2>
<?php if (!empty($conflict_alerts)): ?>
<small class="text-warning">
    <i class="bi bi-exclamation-triangle"></i> <?= count($conflict_alerts); ?> conflicts
</small>
<?php endif; ?>
</div>
<i class="bi bi-clipboard-check fs-1 opacity-75"></i>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card stat-card stat-3 shadow-soft">
<div class="card-body d-flex justify-content-between align-items-center">
<div>
<div class="small">Active Faculty</div>
<h2><?= count(array_unique(array_column($lectures,'faculty_id'))); ?></h2>
<?php if (isset($analytics_data['performance_metrics']['faculty_efficiency'])): ?>
<small class="text-info">
    <i class="bi bi-graph-up"></i> <?= round($analytics_data['performance_metrics']['faculty_efficiency'], 1); ?>% efficiency
</small>
<?php endif; ?>
</div>
<i class="bi bi-people fs-1 opacity-75"></i>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card stat-card stat-4 shadow-soft border-primary">
<div class="card-body d-flex justify-content-between align-items-center">
<div>
<div class="small">AI Insights</div>
<h5 class="text-primary">Active</h5>
<?php if (!empty($optimization_suggestions)): ?>
<small class="text-success">
    <i class="bi bi-lightbulb"></i> <?= count($optimization_suggestions); ?> suggestions
</small>
<?php endif; ?>
</div>
<i class="bi bi-cpu fs-1 text-primary opacity-75"></i>
</div>
</div>
</div>
</div>

<!-- NEW: AI Optimization Panel -->
<?php if (!empty($optimization_suggestions) || !empty($quantum_optimization)): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="bi bi-magic me-2"></i>AI-Powered Optimization Suggestions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php if (!empty($optimization_suggestions)): ?>
                    <div class="col-md-6">
                        <h6><i class="bi bi-graph-up text-info"></i> Analytics Recommendations</h6>
                        <ul class="list-unstyled">
                            <?php foreach (array_slice($optimization_suggestions, 0, 3) as $suggestion): ?>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success me-2"></i>
                                <?= htmlspecialchars($suggestion['description'] ?? 'Optimization available'); ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($quantum_optimization)): ?>
                    <div class="col-md-6">
                        <h6><i class="bi bi-cpu text-warning"></i> Quantum Optimization</h6>
                        <p class="mb-2">
                            <strong>Speedup:</strong> <?= $quantum_optimization['performance_metrics']['quantum_speedup'] ?? 'N/A'; ?>x
                        </p>
                        <p class="mb-2">
                            <strong>Conflicts Resolved:</strong> <?= $quantum_optimization['conflicts_resolved'] ?? 0; ?>
                        </p>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewQuantumDetails()">
                            <i class="bi bi-eye"></i> View Details
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Conflict Alerts -->
<?php if (!empty($conflict_alerts)): ?>
<div class="row g-3 mb-4">
    <div class="col-12">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2"></i>Conflict Detection Alerts
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach (array_slice($conflict_alerts, 0, 3) as $alert): ?>
                    <div class="col-md-4">
                        <div class="alert alert-warning alert-sm mb-2">
                            <strong><?= htmlspecialchars($alert['type'] ?? 'Conflict'); ?></strong><br>
                            <small><?= htmlspecialchars($alert['description'] ?? 'Conflict detected'); ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Your Existing UPLOADS Section (Enhanced) -->
<div class="row g-3 mb-4">
<?php
$uploads = [
    ['Lecture Timetable','upload_lecture.php','success'],
    ['Invigilation Duty','upload_invigilation.php','info'],
    ['Faculty Leave Master','upload_faculty_leaves.php','warning']
];
foreach ($uploads as $u):
?>
<div class="col-md-4">
<div class="card shadow-soft">
<div class="card-header bg-<?= $u[2]; ?> text-white"><?= $u[0]; ?></div>
<div class="card-body">
<form action="<?= $u[1]; ?>" method="post" enctype="multipart/form-data">
<div class="upload-area mb-3">
<i class="bi bi-file-earmark-csv fs-1 text-muted"></i>
<p class="mb-2">Upload CSV File</p>
<input type="file" class="form-control" name="<?= $u[0] === 'Faculty Leave Master' ? 'faculty_leaves_csv' : ($u[0] === 'Invigilation Duty' ? 'invigilation_csv' : 'lecture_csv'); ?>" required>
</div>
<button class="btn btn-<?= $u[2]; ?> w-100">
<i class="bi bi-cloud-upload"></i> Upload
</button>
</form>
</div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- Enhanced TABLES with AI Insights -->
<div class="card shadow-soft">
<div class="card-header">
<ul class="nav nav-tabs card-header-tabs">
<li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#lec">Lectures</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#inv">Invigilation</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#analytics">AI Analytics</a></li>
<li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#optimization">Optimization</a></li>
</ul>
</div>

<div class="card-body">
<div class="tab-content">

<div class="tab-pane fade show active" id="lec">
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5>Lecture Schedule</h5>
    <?php if (!empty($quantum_optimization)): ?>
    <button class="btn btn-sm btn-outline-success" onclick="applyQuantumOptimization()">
        <i class="bi bi-magic"></i> Apply Quantum Optimization
    </button>
    <?php endif; ?>
</div>
<div class="table-responsive">
<table class="table table-sm table-hover">
<thead>
<tr>
<th>Date</th><th>Time</th><th>Faculty</th><th>Subject</th><th>Room</th><th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach (array_slice($lectures,0,10) as $l): ?>
<tr>
<td><?= $l['date']; ?></td>
<td><?= $l['time']; ?></td>
<td><?= $l['faculty_id']; ?></td>
<td><?= $l['subject']; ?></td>
<td><?= $l['room']; ?></td>
<td>
    <?php
    // Simple conflict detection
    $has_conflict = false;
    foreach ($lectures as $other_lecture) {
        if ($other_lecture !== $l && 
            $other_lecture['date'] === $l['date'] && 
            $other_lecture['time'] === $l['time'] && 
            ($other_lecture['room'] === $l['room'] || $other_lecture['faculty_id'] === $l['faculty_id'])) {
            $has_conflict = true;
            break;
        }
    }
    ?>
    <?php if ($has_conflict): ?>
        <span class="badge bg-warning">Conflict</span>
    <?php else: ?>
        <span class="badge bg-success">OK</span>
    <?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<div class="tab-pane fade" id="inv">
<div class="table-responsive">
<table class="table table-sm table-hover">
<thead>
<tr>
<th>Date</th><th>Time</th><th>Faculty</th><th>Exam</th><th>Room</th>
</tr>
</thead>
<tbody>
<?php foreach (array_slice($invigilation,0,10) as $i): ?>
<tr>
<td><?= $i['date']; ?></td>
<td><?= $i['time']; ?></td>
<td><?= $i['faculty_id']; ?></td>
<td><?= $i['exam']; ?></td>
<td><?= $i['room']; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<!-- NEW: AI Analytics Tab -->
<div class="tab-pane fade" id="analytics">
<?php if (!empty($analytics_data)): ?>
<div class="row">
    <div class="col-md-6">
        <h6><i class="bi bi-graph-up"></i> Performance Metrics</h6>
        <?php if (isset($analytics_data['performance_metrics'])): ?>
        <ul class="list-unstyled">
            <?php foreach ($analytics_data['performance_metrics'] as $metric => $value): ?>
            <li class="mb-2">
                <strong><?= ucfirst(str_replace('_', ' ', $metric)); ?>:</strong> 
                <?= is_numeric($value) ? round($value, 2) : $value; ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <h6><i class="bi bi-lightbulb"></i> Insights</h6>
        <?php if (isset($analytics_data['insights'])): ?>
        <ul class="list-unstyled">
            <?php foreach (array_slice($analytics_data['insights'], 0, 5) as $insight): ?>
            <li class="mb-2">
                <i class="bi bi-check-circle text-success me-2"></i>
                <?= htmlspecialchars($insight['description'] ?? 'Insight available'); ?>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div class="text-center text-muted py-4">
    <i class="bi bi-cpu fs-1"></i>
    <p>AI Analytics not available. Install AdvancedAnalyticsAI.php to enable.</p>
</div>
<?php endif; ?>
</div>

<!-- NEW: Optimization Tab -->
<div class="tab-pane fade" id="optimization">
<?php if (!empty($quantum_optimization)): ?>
<div class="row">
    <div class="col-md-6">
        <h6><i class="bi bi-cpu"></i> Quantum Optimization Results</h6>
        <p><strong>Algorithm:</strong> <?= $quantum_optimization['algorithm'] ?? 'QAOA'; ?></p>
        <p><strong>Quantum Speedup:</strong> <?= $quantum_optimization['performance_metrics']['quantum_speedup'] ?? 'N/A'; ?>x</p>
        <p><strong>Conflicts Resolved:</strong> <?= $quantum_optimization['conflicts_resolved'] ?? 0; ?></p>
        <p><strong>Optimization Score:</strong> <?= $quantum_optimization['optimization_score'] ?? 'N/A'; ?></p>
    </div>
    <div class="col-md-6">
        <h6><i class="bi bi-gear"></i> Optimization Actions</h6>
        <button class="btn btn-primary btn-sm mb-2" onclick="applyQuantumOptimization()">
            <i class="bi bi-magic"></i> Apply Optimization
        </button><br>
        <button class="btn btn-outline-info btn-sm mb-2" onclick="viewOptimizationDetails()">
            <i class="bi bi-eye"></i> View Details
        </button><br>
        <button class="btn btn-outline-secondary btn-sm" onclick="exportOptimizationReport()">
            <i class="bi bi-download"></i> Export Report
        </button>
    </div>
</div>
<?php else: ?>
<div class="text-center text-muted py-4">
    <i class="bi bi-cpu fs-1"></i>
    <p>Quantum Optimization not available. Install QuantumInspiredOptimizationEngine.php to enable.</p>
</div>
<?php endif; ?>
</div>

</div>
</div>
</div>

</div>

<!-- Quick Actions -->
<div class="row g-3 mt-4">
    <div class="col-12">
        <div class="card border-info">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">
                    <i class="bi bi-lightning me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <a href="manage_leave_availed.php" class="btn btn-outline-primary w-100 mb-2">
                            <i class="bi bi-calendar-x"></i> Manage Leaves
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="leave_balance_report.php" class="btn btn-outline-success w-100 mb-2">
                            <i class="bi bi-file-text"></i> Leave Reports
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="templates.php" class="btn btn-outline-warning w-100 mb-2">
                            <i class="bi bi-file-earmark-text"></i> Templates
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="phase1_integration_test.php" class="btn btn-outline-info w-100 mb-2">
                            <i class="bi bi-gear"></i> Test Systems
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</div>

<script>
// JavaScript for enhanced interactions
function applyQuantumOptimization() {
    if (confirm('Apply quantum optimization to current schedule? This may modify existing arrangements.')) {
        // Show loading
        alert('Quantum optimization is being applied... (This is a demo - actual implementation would save optimized schedule)');
    }
}

function viewQuantumDetails() {
    alert('Quantum Optimization Details:\n\n• Algorithm: QAOA\n• Quantum Speedup: 25.5x\n• Conflicts Resolved: 3\n• Optimization Score: 94.7%\n\n(Demo - actual implementation would show detailed results)');
}

function viewOptimizationDetails() {
    alert('Opening detailed optimization report... (Demo - actual implementation would show comprehensive analytics)');
}

function exportOptimizationReport() {
    alert('Exporting optimization report... (Demo - actual implementation would generate and download report)');
}
</script>

<?php require_once 'includes/footer.php'; ?>
