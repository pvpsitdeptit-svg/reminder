<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

// Debug: Check authentication status
if (!isAuthenticated()) {
    error_log("Leave Request: User not authenticated");
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header('Location: firebase_login.php');
    exit();
}

// Debug: Log current user
$currentUser = getCurrentUser();
error_log("Leave Request: Current user - " . print_r($currentUser, true));

// Require faculty role (or admin for oversight) before any HTML output
requireFaculty();

// Get current user email
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;

// Handle form submission (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'] ?? '';
    $session = $_POST['session'] ?? '';
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? $from_date;
    $days = $_POST['days'] ?? '';
    $reason = $_POST['reason'] ?? '';
    
    try {
        // Validate required fields
        if (empty($leave_type) || empty($from_date)) {
            $_SESSION['error_message'] = 'Please fill all required fields';
            header('Location: leave_request.php');
            exit;
        }
        
        // Prepare leave request data
        $leaveRequest = [
            'faculty_email' => $userEmail,
            'leave_type' => $leave_type,
            'session' => $session,
            'date' => $from_date,
            'to_date' => $to_date,
            'days' => (float)$days,
            'reason' => $reason,
            'status' => 'pending',
            'requested_at' => time(),
            'updated_at' => time()
        ];
        
        // Save to Firebase
        $database->getReference('leave_requests')->push($leaveRequest);
        
        $_SESSION['success_message'] = 'Leave request submitted successfully! Your request is pending approval.';
        header('Location: faculty_dashboard.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error submitting leave request: ' . $e->getMessage();
        header('Location: leave_request.php');
        exit;
    }
}

require_once 'includes/header.php';
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-calendar-plus"></i> Request Leave
        </h1>
        <a href="faculty_dashboard.php" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <?php if (!empty($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Leave Request Form</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Faculty Email</label>
                                <input type="email" class="form-control" value="<?php echo h($userEmail); ?>" readonly>
                            </div>
                            
                            <div class="col-6">
                                <label class="form-label">Leave Type</label>
                                <select name="leave_type" class="form-select" required onchange="updateSessionOptions(this.value)">
                                    <option value="">Select leave type</option>
                                    <option value="CL">CL (Casual Leave)</option>
                                    <option value="EL">EL (Earned Leave)</option>
                                    <option value="ML">ML (Medical Leave)</option>
                                    <option value="HPL">HPL (Half-pay Leave)</option>
                                    <option value="OD">OD (On Duty)</option>
                                    <option value="CCL">CCL (Child Care Leave)</option>
                                    <option value="LOP">LOP (Loss of Pay)</option>
                                </select>
                            </div>
                            
                            <div class="col-6">
                                <label class="form-label">Session</label>
                                <select name="session" id="sessionSelect" class="form-select" required>
                                    <option value="FULL">Full Day</option>
                                </select>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Date Range</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="date" name="from_date" class="form-control" required id="fromDate" onchange="calculateDays()">
                                        <small class="text-muted">From</small>
                                    </div>
                                    <div class="col-6">
                                        <input type="date" name="to_date" class="form-control" id="toDate" onchange="calculateDays()">
                                        <small class="text-muted">To (optional, defaults to From)</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-info">
                                        <i class="bi bi-info-circle"></i> 
                                        <span id="dateInfo">Select dates to automatically calculate leave days</span>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Calculated Days</label>
                                <div class="input-group">
                                    <input type="number" min="0.5" step="0.5" name="days" class="form-control" id="calculatedDays" value="1" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="manualDays()" title="Edit manually">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Automatically calculated based on date range and session</small>
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Reason</label>
                                <textarea name="reason" class="form-control" rows="3" placeholder="Please provide a reason for your leave request..." required></textarea>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Submit Leave Request
                            </button>
                            <a href="faculty_dashboard.php" class="btn btn-secondary ms-2">
                                <i class="bi bi-x-circle"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Leave Policy Information</h5>
                </div>
                <div class="card-body">
                    <h6>Leave Types & Rules:</h6>
                    <ul class="list-unstyled">
                        <li><strong>CL (Casual Leave):</strong> 12 days per year</li>
                        <li><strong>EL (Earned Leave):</strong> 15 days per year</li>
                        <li><strong>ML (Medical Leave):</strong> 12 days per year</li>
                        <li><strong>HPL (Half-pay Leave):</strong> 15 days per year</li>
                        <li><strong>OD (On Duty):</strong> As required</li>
                        <li><strong>CCL (Child Care Leave):</strong> 90 days per child</li>
                        <li><strong>LOP (Loss of Pay):</strong> No limit</li>
                    </ul>
                    
                    <h6 class="mt-3">Important Notes:</h6>
                    <ul>
                        <li>Leave requests must be submitted at least 2 days in advance</li>
                        <li>Medical leave may require supporting documents</li>
                        <li>Weekends are excluded from leave calculations</li>
                        <li>Half-day leaves count as 0.5 days</li>
                        <li>All leave requests are subject to approval</li>
                    </ul>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Approval Process:</strong> Your leave request will be reviewed by the administration. You will receive a notification once a decision is made.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Function to calculate leave days based on date range and session
function calculateDays() {
    const fromDate = document.getElementById('fromDate').value;
    const toDate = document.getElementById('toDate').value;
    const session = document.getElementById('sessionSelect').value;
    const calculatedDaysField = document.getElementById('calculatedDays');
    const dateInfo = document.getElementById('dateInfo');
    
    if (!fromDate) {
        dateInfo.textContent = 'Select dates to automatically calculate leave days';
        return;
    }
    
    // Set to_date if not provided
    if (!toDate) {
        document.getElementById('toDate').value = fromDate;
    }
    
    const start = new Date(fromDate);
    const end = new Date(toDate || fromDate);
    
    // Validate date range
    if (end < start) {
        dateInfo.innerHTML = '<span class="text-danger">To date cannot be before From date</span>';
        calculatedDaysField.value = 1;
        return;
    }
    
    // Calculate business days (excluding weekends)
    let businessDays = 0;
    let currentDate = new Date(start);
    
    while (currentDate <= end) {
        const dayOfWeek = currentDate.getDay();
        // 0 = Sunday, 6 = Saturday
        if (dayOfWeek !== 0 && dayOfWeek !== 6) {
            businessDays++;
        }
        currentDate.setDate(currentDate.getDate() + 1);
    }
    
    // Calculate total days based on session
    let totalDays = 0;
    if (session === 'FULL') {
        totalDays = businessDays;
    } else {
        // For half-day sessions, count each day as 0.5
        totalDays = businessDays * 0.5;
    }
    
    // Update the days field
    calculatedDaysField.value = totalDays;
    
    // Update info text
    const totalCalendarDays = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
    const weekendDays = totalCalendarDays - businessDays;
    
    dateInfo.innerHTML = `
        <span class="text-success">
            ${totalCalendarDays} calendar days (${businessDays} working days, ${weekendDays} weekends) = ${totalDays} leave days
        </span>
    `;
}

// Function to enable manual editing of days
function manualDays() {
    const calculatedDaysField = document.getElementById('calculatedDays');
    const dateInfo = document.getElementById('dateInfo');
    
    if (calculatedDaysField.readOnly) {
        calculatedDaysField.readOnly = false;
        calculatedDaysField.classList.remove('form-control-plaintext');
        calculatedDaysField.classList.add('form-control');
        calculatedDaysField.focus();
        dateInfo.innerHTML = '<span class="text-warning">Editing days manually</span>';
    } else {
        calculatedDaysField.readOnly = true;
        calculatedDaysField.classList.remove('form-control');
        calculatedDaysField.classList.add('form-control-plaintext');
        calculateDays(); // Recalculate to restore auto-calculation
    }
}

// Function to update session options based on leave type
function updateSessionOptions(leaveType) {
    const sessionSelect = document.getElementById('sessionSelect');
    
    // Clear existing options
    sessionSelect.innerHTML = '';
    
    if (leaveType === 'OD' || leaveType === 'CCL') {
        // OD and CCL typically only allow full day
        sessionSelect.innerHTML = '<option value="FULL">Full Day</option>';
    } else {
        // Other leaves can have full day or half day
        sessionSelect.innerHTML = `
            <option value="FULL">Full Day</option>
            <option value="FN">FN (Forenoon)</option>
            <option value="AN">AN (Afternoon)</option>
        `;
    }
    
    // Recalculate days when session changes
    calculateDays();
}

// Auto-calculate when session changes
document.getElementById('sessionSelect').addEventListener('change', calculateDays);

// Set minimum date to today
document.addEventListener('DOMContentLoaded', function() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('fromDate').setAttribute('min', today);
    document.getElementById('toDate').setAttribute('min', today);
    
    // Initialize calculation
    calculateDays();
});
</script>

<?php require_once 'includes/footer.php'; ?>
