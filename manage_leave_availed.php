<?php
session_start();
require_once 'config/firebase.php';

// Form processing logic - must be before header to avoid headers already sent
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("POST request received. Action: " . ($_POST['action'] ?? 'none'));
    error_log("Session data: " . print_r($_SESSION, true));
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_leave') {
        try {
            $faculty_email = $_POST['faculty_email'] ?? '';
            $leave_type = $_POST['leave_type'] ?? '';
            $session = $_POST['session'] ?? '';
            $from_date = $_POST['from_date'] ?? '';
            $to_date = $_POST['to_date'] ?? $from_date;
            $days = $_POST['days'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            // Validate required fields
            if (empty($faculty_email) || empty($leave_type) || empty($from_date)) {
                $_SESSION['error_message'] = 'Please fill all required fields';
                header('Location: manage_leave_availed.php');
                exit;
            }
            
            // Prepare data for Firebase
            $leaveData = [
                'faculty_email' => $faculty_email,
                'leave_type' => $leave_type,
                'session' => $session,
                'date' => $from_date,
                'to_date' => $to_date,
                'days' => (float)$days,
                'reason' => $reason,
                'created_at' => time(),
                'updated_at' => time()
            ];
            
            // Save to Firebase
            $newLeaveRef = $database->getReference('leave_ledger')->push($leaveData);
            $leaveId = $newLeaveRef->getKey();
            
            // Send FCM notification to faculty about leave entry
            $fcmStatus = 'not_sent';
            $fcmError = '';
            
            // Debug: Log the entire process
            error_log("=== FCM DEBUG START ===");
            error_log("Faculty email: " . $faculty_email);
            
            try {
                // Get FCM token for the faculty
                $sanitizedEmail = str_replace(['.', '@'], ['_', '_'], $faculty_email);
                error_log("Sanitized email: " . $sanitizedEmail);
                
                $tokenRef = $database->getReference('fcm_tokens/' . $sanitizedEmail);
                $tokenSnapshot = $tokenRef->getSnapshot();
                $fcmToken = $tokenSnapshot->getValue();
                
                error_log("FCM Token found: " . ($fcmToken ? 'YES' : 'NO'));
                if ($fcmToken) {
                    error_log("FCM Token length: " . strlen($fcmToken));
                    error_log("FCM Token preview: " . substr($fcmToken, 0, 30) . "...");
                }
                
                if ($fcmToken) {
                    // Prepare notification data (using same format as admin messages)
                    $notificationData = [
                        'type' => 'leave_entry_added',
                        'faculty_email' => $faculty_email,
                        'leave_id' => $leaveId,
                        'leave_type' => $leave_type,
                        'session' => $session,
                        'date' => $from_date,
                        'days' => (string)$days,
                        'reason' => $reason,
                        'added_by' => $_SESSION['user_email'] ?? 'Admin',
                        'timestamp' => (string)time()
                    ];
                    
                    $title = "Leave Entry Added";
                    $body = "Your leave entry has been recorded: $leave_type - $session on $from_date ($days day(s))";
                    
                    error_log("Notification title: " . $title);
                    error_log("Notification body: " . $body);
                    error_log("Notification data: " . json_encode($notificationData));
                    
                    // Initialize Firebase Messaging
                    $messaging = $factory->createMessaging();
                    error_log("Firebase Messaging initialized");
                    
                    // Send FCM notification
                    error_log("Attempting to send FCM notification...");
                    $result = sendFCMNotification($messaging, $title, $body, $notificationData, [$fcmToken]);
                    error_log("FCM send result: " . ($result ? 'SUCCESS' : 'FAILED'));
                    
                    if ($result) {
                        $fcmStatus = 'sent';
                    } else {
                        $fcmStatus = 'failed';
                        $fcmError = 'FCM send failed - check error logs';
                    }
                } else {
                    $fcmStatus = 'no_token';
                    $fcmError = 'No FCM token found for faculty: ' . $faculty_email;
                    error_log("No FCM token found for faculty: " . $faculty_email);
                }
            } catch (Exception $e) {
                $fcmStatus = 'error';
                $fcmError = $e->getMessage();
                error_log("FCM Exception: " . $e->getMessage());
            }
            
            error_log("FCM Final Status: " . $fcmStatus);
            error_log("FCM Final Error: " . $fcmError);
            error_log("=== FCM DEBUG END ===");
            
            // Log FCM delivery status
            $fcmLog = [
                'leave_id' => $leaveId,
                'faculty_email' => $faculty_email,
                'fcm_status' => $fcmStatus,
                'error_message' => $fcmError,
                'notification_type' => 'leave_entry_added',
                'sent_at' => time()
            ];
            $database->getReference('fcm_delivery_log')->push($fcmLog);
            
            $_SESSION['success_message'] = 'Leave entry added successfully' . ($fcmStatus === 'sent' ? ' with notification sent!' : '');
            header('Location: manage_leave_availed.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error adding leave entry: ' . $e->getMessage();
            header('Location: manage_leave_availed.php');
            exit;
        }
    }
    
    if ($action === 'edit_leave') {
        try {
            $leave_id = $_POST['leave_id'] ?? '';
            $faculty_email = $_POST['faculty_email'] ?? '';
            $leave_type = $_POST['leave_type'] ?? '';
            $session = $_POST['session'] ?? '';
            $from_date = $_POST['from_date'] ?? '';
            $to_date = $_POST['to_date'] ?? $from_date;
            $days = $_POST['days'] ?? '';
            $reason = $_POST['reason'] ?? '';
            
            // Update data in Firebase
            $updateData = [
                'faculty_email' => $faculty_email,
                'leave_type' => $leave_type,
                'session' => $session,
                'date' => $from_date,
                'to_date' => $to_date,
                'days' => (float)$days,
                'reason' => $reason,
                'updated_at' => time()
            ];
            
            $database->getReference('leave_ledger/' . $leave_id)->update($updateData);
            
            $_SESSION['success_message'] = 'Leave entry updated successfully';
            header('Location: manage_leave_availed.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error updating leave entry: ' . $e->getMessage();
            header('Location: manage_leave_availed.php');
            exit;
        }
    }
    
    if ($action === 'delete_leave') {
        try {
            $leave_id = $_POST['leave_id'] ?? '';
            error_log("Delete action triggered for leave_id: " . $leave_id);
            
            if (empty($leave_id)) {
                $_SESSION['error_message'] = 'Leave ID is required for deletion';
                header('Location: manage_leave_availed.php');
                exit;
            }
            
            $database->getReference('leave_ledger/' . $leave_id)->remove();
            
            $_SESSION['success_message'] = 'Leave entry deleted successfully';
            header('Location: manage_leave_availed.php');
            exit;
            
        } catch (Exception $e) {
            error_log("Delete error: " . $e->getMessage());
            $_SESSION['error_message'] = 'Error deleting leave entry: ' . $e->getMessage();
            header('Location: manage_leave_availed.php');
            exit;
        }
    }
}

require_once 'includes/header.php';

$master = [];
$ledger = [];
$messages = [];
try {
    $mSnap = $database->getReference('faculty_leave_master')->getSnapshot();
    if ($mSnap->exists()) {
        $master = $mSnap->getValue();
        if (!is_array($master)) $master = [];
    }

    $lSnap = $database->getReference('leave_ledger')->getSnapshot();
    if ($lSnap->exists()) {
        $ledger = $lSnap->getValue();
        if (!is_array($ledger)) $ledger = [];
    }
    
    // Get messages from Firebase
    $messagesRef = $database->getReference('admin_messages');
    $messagesSnapshot = $messagesRef->getSnapshot();
    $allMessages = $messagesSnapshot->getValue();
    
    if ($allMessages && is_array($allMessages)) {
        // Convert to array and sort by created_at descending
        $messageArray = [];
        foreach ($allMessages as $key => $message) {
            $message['id'] = $key;
            $messageArray[] = $message;
        }
        
        // Sort by created_at descending
        usort($messageArray, function($a, $b) {
            return ($b['created_at'] ?? 0) - ($a['created_at'] ?? 0);
        });
        
        $messages = $messageArray;
    }
    
} catch (Exception $e) {
    $error = 'Error loading data: ' . $e->getMessage();
}

function getBadgeColor($type) {
    $colors = [
        'general' => 'primary',
        'urgent' => 'danger',
        'info' => 'info',
        'success' => 'success',
        'warning' => 'warning'
    ];
    return $colors[$type] ?? 'secondary';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'send_message') {
        $recipientEmail = $_POST['recipient_email'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
        $messageType = $_POST['message_type'] ?? 'general';
        $senderEmail = $_SESSION['admin_email'] ?? 'admin@reminder.com';
        
        if (empty($recipientEmail) || empty($subject) || empty($message)) {
            $_SESSION['error_message'] = 'All fields are required';
            header('Location: manage_leave_availed.php');
            exit;
        }
        
        try {
            // Store message in Firebase Realtime Database
            $messageData = [
                'sender_email' => $senderEmail,
                'recipient_email' => $recipientEmail,
                'subject' => $subject,
                'message' => $message,
                'message_type' => $messageType,
                'status' => 'sent',
                'created_at' => time(),
                'updated_at' => time()
            ];
            
            $messagesRef = $database->getReference('admin_messages');
            $newMessageRef = $messagesRef->push($messageData);
            $messageId = $newMessageRef->getKey();
            
            // Get FCM token for recipient
            $sanitizedEmail = str_replace(['.', '@'], ['_', '_'], $recipientEmail);
            $tokenRef = $database->getReference('fcm_tokens/' . $sanitizedEmail);
            $tokenSnapshot = $tokenRef->getSnapshot();
            $fcmToken = $tokenSnapshot->getValue();
            
            $deliveryStatus = 'failed';
            $errorMessage = 'No FCM token found';
            
            if ($fcmToken) {
                // Send FCM notification
                $notificationData = [
                    'type' => 'admin_message',
                    'message_id' => $messageId,
                    'sender_email' => $senderEmail,
                    'subject' => $subject,
                    'message' => substr($message, 0, 200),
                    'full_message_url' => "view_message.php?id=$messageId"
                ];
                
                $result = sendFCMNotification($messaging, $subject, $message, $notificationData, [$fcmToken]);
                
                if ($result) {
                    $deliveryStatus = 'sent';
                    $errorMessage = null;
                }
            }
            
            // Update message with delivery status
            $deliveryData = [
                'delivery_status' => $deliveryStatus,
                'error_message' => $errorMessage,
                'sent_at' => time()
            ];
            
            $database->getReference('admin_messages/' . $messageId)->update($deliveryData);
            
            // Store delivery record
            $deliveryRecord = [
                'message_id' => $messageId,
                'faculty_email' => $recipientEmail,
                'fcm_token' => $fcmToken,
                'delivery_status' => $deliveryStatus,
                'error_message' => $errorMessage,
                'sent_at' => time()
            ];
            
            $database->getReference('message_delivery')->push($deliveryRecord);
            
            $_SESSION['success_message'] = 'Message sent successfully! Delivery status: ' . $deliveryStatus;
            header('Location: manage_leave_availed.php');
            exit;
            
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error sending message: ' . $e->getMessage();
            header('Location: manage_leave_availed.php');
            exit;
        }
    }
    
    if ($action === 'delete_message') {
        $messageId = $_POST['message_id'] ?? '';
        
        if (empty($messageId)) {
            $_SESSION['error_message'] = 'Message ID is required';
            header('Location: manage_leave_availed.php');
            exit;
        }
        
        try {
            $database->getReference('admin_messages/' . $messageId)->remove();
            $_SESSION['success_message'] = 'Message deleted successfully';
            header('Location: manage_leave_availed.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error deleting message: ' . $e->getMessage();
            header('Location: manage_leave_availed.php');
            exit;
        }
    }
    
    if ($action === 'edit_message') {
        $messageId = $_POST['message_id'] ?? '';
        $subject = $_POST['subject'] ?? '';
        $message = $_POST['message'] ?? '';
        $messageType = $_POST['message_type'] ?? 'general';
        
        if (empty($messageId) || empty($subject) || empty($message)) {
            $_SESSION['error_message'] = 'All fields are required';
            header('Location: manage_leave_availed.php');
            exit;
        }
        
        try {
            $updateData = [
                'subject' => $subject,
                'message' => $message,
                'message_type' => $messageType,
                'updated_at' => time()
            ];
            
            $database->getReference('admin_messages/' . $messageId)->update($updateData);
            $_SESSION['success_message'] = 'Message updated successfully';
            header('Location: manage_leave_availed.php');
            exit;
        } catch (Exception $e) {
            $_SESSION['error_message'] = 'Error updating message: ' . $e->getMessage();
            header('Location: manage_leave_availed.php');
            exit;
        }
    }
}

$facultyOptions = [];
foreach ($master as $k => $v) {
    $email = $v['faculty_email'] ?? firebaseEmailFromKey($k);
    if ($email === '') continue;
    $facultyOptions[strtolower($email)] = $v;
}
ksort($facultyOptions);

$ledgerRows = [];
foreach ($ledger as $id => $row) {
    if (!is_array($row)) continue;
    $ledgerRows[] = ['id' => $id, 'row' => $row];
}
usort($ledgerRows, function($a, $b) {
    $ad = $a['row']['date'] ?? '';
    $bd = $b['row']['date'] ?? '';
    $cmp = strcmp($bd, $ad);
    if ($cmp !== 0) return $cmp;
    return strcmp($b['id'], $a['id']);
});
?>
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-journal-check"></i> Availed Leaves (Admin Entry)
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
    <div class="col-lg-5 mb-4">
      <div class="card">
        <div class="card-header bg-success text-white">
          <strong>Add Availed Leave</strong>
        </div>
        <div class="card-body">
          <form method="post">
            <input type="hidden" name="action" value="add_leave">
            <input type="hidden" name="leave_id" id="leave_id_field">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Faculty Email</label>
                <select name="faculty_email" class="form-select" required>
                  <option value="">Select faculty</option>
                  <?php foreach ($facultyOptions as $email => $v): ?>
                    <option value="<?php echo h($email); ?>"><?php echo h($email . ' - ' . ($v['name'] ?? '') . ' (' . ($v['department'] ?? '') . ')'); ?></option>
                  <?php endforeach; ?>
                </select>
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
              <div class="col-6" id="daysField" style="display:none;">
                <label class="form-label">Days</label>
                <input type="number" min="0.5" step="0.5" name="days" class="form-control" value="1">
              </div>
              <div class="col-12">
                <label class="form-label">Date Range</label>
                <div class="row g-2">
                  <div class="col-6">
                    <input type="date" name="from_date" class="form-control" required>
                    <small class="text-muted">From</small>
                  </div>
                  <div class="col-6">
                    <input type="date" name="to_date" class="form-control" id="toDate">
                    <small class="text-muted">To (optional, defaults to From)</small>
                  </div>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label">Reason (optional)</label>
                <input type="text" name="reason" class="form-control" maxlength="200">
              </div>
            </div>
            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-success" type="submit"><i class="bi bi-plus-circle"></i> Add</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <strong>Recent Availed Entries</strong>
          <button class="btn btn-sm btn-outline-primary" onclick="toggleTableExpand()">
            <i class="bi bi-arrows-expand" id="expandIcon"></i> <span id="expandText">Expand</span>
          </button>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive" style="max-height: 400px; overflow-y: auto; overflow-x: auto;">
            <table class="table table-striped table-sm align-middle sticky-header" style="min-width: 800px;">
              <thead class="table-light sticky-top">
                <tr>
                  <th style="min-width: 100px;">Date</th>
                  <th style="min-width: 250px;">Email</th>
                  <th style="min-width: 80px;">Type</th>
                  <th style="min-width: 80px;">Session</th>
                  <th style="min-width: 60px;">Days</th>
                  <th style="min-width: 200px;">Reason</th>
                  <th style="min-width: 120px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($ledgerRows)): ?>
                  <tr><td colspan="7" class="text-muted text-center py-4">No availed leave entries yet.</td></tr>
                <?php else: 
                  // Show minimum 10 entries or all if expanded
                  $displayRows = $ledgerRows;
                  if (count($ledgerRows) > 10) {
                    $displayRows = array_slice($ledgerRows, 0, 10);
                  }
                  foreach ($displayRows as $item): $r = $item['row']; 
                ?>
                  <tr>
                    <td style="min-width: 100px; white-space: nowrap;"><?php echo h($r['date'] ?? ''); ?></td>
                    <td style="min-width: 250px; word-break: break-all;"><?php echo h($r['faculty_email'] ?? ''); ?></td>
                    <td style="min-width: 80px; white-space: nowrap;"><?php echo h($r['leave_type'] ?? ''); ?></td>
                    <td style="min-width: 80px; white-space: nowrap;"><?php echo h($r['session'] ?? ''); ?></td>
                    <td style="min-width: 60px; white-space: nowrap; text-align: center;"><?php echo h($r['days'] ?? ''); ?></td>
                    <td style="min-width: 200px; word-break: break-word; max-width: 300px;"><?php echo h($r['reason'] ?? ''); ?></td>
                    <td style="min-width: 120px; white-space: nowrap;" class="text-end">
                      <button class="btn btn-sm btn-outline-primary me-1" onclick="editLeave('<?php echo h($item['id']); ?>', '<?php echo h($r['faculty_email'] ?? ''); ?>', '<?php echo h($r['leave_type'] ?? ''); ?>', '<?php echo h($r['session'] ?? ''); ?>', '<?php echo h($r['date'] ?? ''); ?>', '<?php echo h($r['to_date'] ?? $r['date'] ?? ''); ?>', '<?php echo h($r['days'] ?? ''); ?>', '<?php echo h(addslashes($r['reason'] ?? '')); ?>')">
                        <i class="bi bi-pencil"></i>
                      </button>
                      <button class="btn btn-sm btn-outline-danger" onclick="deleteLeave('<?php echo h($item['id']); ?>')">
                        <i class="bi bi-trash"></i>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; 
                  // Show "Load More" if there are more entries
                  if (count($ledgerRows) > 10):
                ?>
                  <tr id="loadMoreRow" class="d-none">
                    <td colspan="7" class="text-center py-3">
                      <button class="btn btn-primary" onclick="loadMoreEntries()">
                        <i class="bi bi-arrow-down-circle"></i> Load More Entries
                      </button>
                    </td>
                  </tr>
                <?php endif; endif; ?>
              </tbody>
            </table>
          </div>
          <?php if (count($ledgerRows) > 10): ?>
            <div class="text-center p-2 bg-light border-top">
              <small class="text-muted">
                Showing <span id="showingCount">10</span> of <?php echo count($ledgerRows); ?> entries
              </small>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
function editLeave(id, email, leaveType, session, fromDate, toDate, days, reason) {
    console.log('Editing leave:', id);
    
    // Populate the form with current data
    var facultySelect = document.querySelector('select[name="faculty_email"]');
    var leaveTypeSelect = document.querySelector('select[name="leave_type"]');
    var sessionSelect = document.querySelector('select[name="session"]');
    var fromDateInput = document.querySelector('input[name="from_date"]');
    var toDateInput = document.querySelector('input[name="to_date"]');
    var daysInput = document.querySelector('input[name="days"]');
    var reasonInput = document.querySelector('input[name="reason"]');
    
    if (facultySelect) facultySelect.value = email;
    if (leaveTypeSelect) leaveTypeSelect.value = leaveType;
    if (sessionSelect) sessionSelect.value = session;
    if (fromDateInput) fromDateInput.value = fromDate;
    if (toDateInput) toDateInput.value = toDate;
    if (daysInput) daysInput.value = days;
    if (reasonInput) reasonInput.value = reason;
    
    // Change form action to edit
    var actionInput = document.querySelector('input[name="action"]');
    var leaveIdInput = document.getElementById('leave_id_field');
    if (actionInput) actionInput.value = 'edit_leave';
    if (leaveIdInput) leaveIdInput.value = id;
    
    // Change button text
    var submitBtn = document.querySelector('button[type="submit"]');
    if (submitBtn) submitBtn.innerHTML = '<i class="bi bi-save"></i> Update';
    
    // Scroll to form
    var cardHeader = document.querySelector('.card-header');
    if (cardHeader) cardHeader.scrollIntoView({ behavior: 'smooth' });
}

function deleteLeave(id) {
    console.log('Delete clicked for ID:', id);
    if (confirm('Are you sure you want to delete this leave entry?')) {
        var deleteIdInput = document.getElementById('delete_leave_id');
        if (deleteIdInput) {
            deleteIdInput.value = id;
            console.log('Submitting delete form for ID:', id);
            document.getElementById('deleteForm').submit();
        } else {
            console.error('Delete form input not found');
        }
    }
}

function updateSessionOptions(leaveType) {
    var sessionSelect = document.getElementById('sessionSelect');
    if (sessionSelect) {
        if (leaveType === 'CL' || leaveType === 'CCL') {
            sessionSelect.innerHTML = '<option value="">Select session</option><option value="FN">FN (Forenoon)</option><option value="AN">AN (Afternoon)</option><option value="FULL">Full Day</option>';
        } else {
            sessionSelect.innerHTML = '<option value="FULL">Full Day</option>';
        }
    }
}

// Auto-fill to date when from date changes
document.addEventListener('DOMContentLoaded', function() {
    var fromDate = document.querySelector('input[name="from_date"]');
    if (fromDate) {
        fromDate.addEventListener('change', function() {
            var toDate = document.getElementById('toDate');
            if (toDate && !toDate.value) {
                toDate.value = this.value;
            }
        });
    }
});

// Table expand/collapse functionality
var isExpanded = false;
var allRows = <?php echo json_encode(array_values($ledgerRows)); ?>;

function toggleTableExpand() {
    var tableBody = document.querySelector('tbody');
    var expandIcon = document.getElementById('expandIcon');
    var expandText = document.getElementById('expandText');
    var showingCount = document.getElementById('showingCount');
    var loadMoreRow = document.getElementById('loadMoreRow');
    
    isExpanded = !isExpanded;
    
    if (isExpanded) {
        // Show all entries
        expandIcon.className = 'bi bi-arrows-collapse';
        expandText.textContent = 'Collapse';
        
        // Clear existing rows and add all rows
        var existingRows = tableBody.querySelectorAll('tr:not(#loadMoreRow)');
        existingRows.forEach(row => row.remove());
        
        // Add all rows
        allRows.forEach(function(item) {
            var r = item.row;
            var row = document.createElement('tr');
            row.innerHTML = `
                <td style="min-width: 100px; white-space: nowrap;">${r.date || ''}</td>
                <td style="min-width: 250px; word-break: break-all;">${r.faculty_email || ''}</td>
                <td style="min-width: 80px; white-space: nowrap;">${r.leave_type || ''}</td>
                <td style="min-width: 80px; white-space: nowrap;">${r.session || ''}</td>
                <td style="min-width: 60px; white-space: nowrap; text-align: center;">${r.days || ''}</td>
                <td style="min-width: 200px; word-break: break-word; max-width: 300px;">${r.reason || ''}</td>
                <td style="min-width: 120px; white-space: nowrap;" class="text-end">
                  <button class="btn btn-sm btn-outline-primary me-1" onclick="editLeave('${item.id}', '${r.faculty_email || ''}', '${r.leave_type || ''}', '${r.session || ''}', '${r.date || ''}', '${r.to_date || r.date || ''}', '${r.days || ''}', '${(r.reason || '').replace(/'/g, "\\'")}')">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteLeave('${item.id}')">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
            `;
            tableBody.insertBefore(row, loadMoreRow);
        });
        
        // Hide load more row
        if (loadMoreRow) {
            loadMoreRow.classList.add('d-none');
        }
        
        // Update count
        if (showingCount) {
            showingCount.textContent = allRows.length;
        }
        
        // Increase max height
        document.querySelector('.table-responsive').style.maxHeight = '600px';
        
    } else {
        // Show only 10 entries
        expandIcon.className = 'bi bi-arrows-expand';
        expandText.textContent = 'Expand';
        
        // Clear existing rows and add first 10 rows
        var existingRows = tableBody.querySelectorAll('tr:not(#loadMoreRow)');
        existingRows.forEach(row => row.remove());
        
        // Add first 10 rows
        var displayRows = allRows.slice(0, 10);
        displayRows.forEach(function(item) {
            var r = item.row;
            var row = document.createElement('tr');
            row.innerHTML = `
                <td style="min-width: 100px; white-space: nowrap;">${r.date || ''}</td>
                <td style="min-width: 250px; word-break: break-all;">${r.faculty_email || ''}</td>
                <td style="min-width: 80px; white-space: nowrap;">${r.leave_type || ''}</td>
                <td style="min-width: 80px; white-space: nowrap;">${r.session || ''}</td>
                <td style="min-width: 60px; white-space: nowrap; text-align: center;">${r.days || ''}</td>
                <td style="min-width: 200px; word-break: break-word; max-width: 300px;">${r.reason || ''}</td>
                <td style="min-width: 120px; white-space: nowrap;" class="text-end">
                  <button class="btn btn-sm btn-outline-primary me-1" onclick="editLeave('${item.id}', '${r.faculty_email || ''}', '${r.leave_type || ''}', '${r.session || ''}', '${r.date || ''}', '${r.to_date || r.date || ''}', '${r.days || ''}', '${(r.reason || '').replace(/'/g, "\\'")}')">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger" onclick="deleteLeave('${item.id}')">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
            `;
            tableBody.insertBefore(row, loadMoreRow);
        });
        
        // Show load more row if there are more entries
        if (loadMoreRow && allRows.length > 10) {
            loadMoreRow.classList.remove('d-none');
        }
        
        // Update count
        if (showingCount) {
            showingCount.textContent = '10';
        }
        
        // Reset max height
        document.querySelector('.table-responsive').style.maxHeight = '400px';
    }
}

function loadMoreEntries() {
    var tableBody = document.querySelector('tbody');
    var loadMoreRow = document.getElementById('loadMoreRow');
    var showingCount = document.getElementById('showingCount');
    var currentRows = tableBody.querySelectorAll('tr:not(#loadMoreRow)').length;
    
    // Add next 10 entries
    var nextRows = allRows.slice(currentRows, currentRows + 10);
    nextRows.forEach(function(item) {
        var r = item.row;
        var row = document.createElement('tr');
        row.innerHTML = `
            <td style="min-width: 100px; white-space: nowrap;">${r.date || ''}</td>
            <td style="min-width: 250px; word-break: break-all;">${r.faculty_email || ''}</td>
            <td style="min-width: 80px; white-space: nowrap;">${r.leave_type || ''}</td>
            <td style="min-width: 80px; white-space: nowrap;">${r.session || ''}</td>
            <td style="min-width: 60px; white-space: nowrap; text-align: center;">${r.days || ''}</td>
            <td style="min-width: 200px; word-break: break-word; max-width: 300px;">${r.reason || ''}</td>
            <td style="min-width: 120px; white-space: nowrap;" class="text-end">
              <button class="btn btn-sm btn-outline-primary me-1" onclick="editLeave('${item.id}', '${r.faculty_email || ''}', '${r.leave_type || ''}', '${r.session || ''}', '${r.date || ''}', '${r.to_date || r.date || ''}', '${r.days || ''}', '${(r.reason || '').replace(/'/g, "\\'")}')">
                <i class="bi bi-pencil"></i>
              </button>
              <button class="btn btn-sm btn-outline-danger" onclick="deleteLeave('${item.id}')">
                <i class="bi bi-trash"></i>
              </button>
            </td>
        `;
        tableBody.insertBefore(row, loadMoreRow);
    });
    
    // Update count
    var newTotal = tableBody.querySelectorAll('tr:not(#loadMoreRow)').length;
    if (showingCount) {
        showingCount.textContent = newTotal;
    }
    
    // Hide load more if all entries are shown
    if (newTotal >= allRows.length) {
        if (loadMoreRow) {
            loadMoreRow.classList.add('d-none');
        }
    }
}
</script>
</div>

<!-- Hidden form for deleting -->
<form id="deleteForm" method="post" style="display: none;">
  <input type="hidden" name="action" value="delete_leave">
  <input type="hidden" name="leave_id" id="delete_leave_id">
</form>

<?php require_once 'includes/footer.php'; ?>
</body>
</html>
