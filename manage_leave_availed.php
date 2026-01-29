<?php
session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

require_once 'config/firebase.php';

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

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

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
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Availed Leaves (Admin)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
  <script>
    function updateSessionOptions(leaveType) {
        var sessionSelect = document.getElementById('sessionSelect');
        if (leaveType === 'CL' || leaveType === 'CCL') {
            sessionSelect.innerHTML = '<option value="">Select session</option><option value="FN">FN (Forenoon)</option><option value="AN">AN (Afternoon)</option><option value="FULL">Full Day</option>';
        } else {
            sessionSelect.innerHTML = '<option value="FULL">Full Day</option>';
        }
    }
  </script>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="bi bi-calendar-check"></i> Faculty Management System</a>
    <div class="navbar-nav ms-auto">
      <a class="nav-link text-white" href="manage_faculty_leaves.php"><i class="bi bi-person-lines-fill"></i> Faculty Leaves</a>
      <a class="nav-link text-white" href="leave_balance_report.php"><i class="bi bi-graph-up"></i> Balance Report</a>
      <a class="nav-link text-white" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="container my-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-journal-check"></i> Availed Leaves (Admin Entry)</h1>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
  </div>

  <?php if (!empty($_SESSION['success_message'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle"></i> <?php echo h($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
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
          <form action="save_leave_availed.php" method="post">
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
        <div class="card-header">
          <strong>Recent Availed Entries</strong>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-striped table-sm align-middle">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Email</th>
                  <th>Type</th>
                  <th>Session</th>
                  <th>Days</th>
                  <th>Reason</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($ledgerRows)): ?>
                  <tr><td colspan="7" class="text-muted">No availed leave entries yet.</td></tr>
                <?php else: foreach ($ledgerRows as $item): $r = $item['row']; ?>
                  <tr>
                    <td><?php echo h($r['date'] ?? ''); ?></td>
                    <td><?php echo h($r['faculty_email'] ?? ''); ?></td>
                    <td><?php echo h($r['leave_type'] ?? ''); ?></td>
                    <td><?php echo h($r['session'] ?? ''); ?></td>
                    <td><?php echo h($r['days'] ?? ''); ?></td>
                    <td><?php echo h($r['reason'] ?? ''); ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-danger" href="delete_leave_availed.php?id=<?php echo h($item['id']); ?>" onclick="return confirm('Delete this availed leave entry?');"><i class="bi bi-trash"></i></a>
                    </td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Messaging Section -->
<div class="row mt-4">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-info text-white">
        <h5 class="card-title mb-0"><i class="bi bi-chat-dots"></i> Faculty Messaging</h5>
      </div>
      <div class="card-body">
        <?php if (!empty($_SESSION['success_message'])): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle"></i> <?php echo h($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
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
                <strong>Send New Message</strong>
              </div>
              <div class="card-body">
                <form method="post">
                  <input type="hidden" name="action" value="send_message">
                  <div class="row g-3">
                    <div class="col-12">
                      <label class="form-label">Recipient Faculty</label>
                      <select class="form-select" name="recipient_email" required>
                        <option value="">Select Faculty Member...</option>
                        <?php foreach ($facultyOptions as $email => $fac): ?>
                          <option value="<?php echo h($email); ?>">
                            <?php echo h($fac['name'] . ' - ' . $fac['department'] . ' (' . $email . ')'); ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Message Type</label>
                      <select class="form-select" name="message_type" required>
                        <option value="general">General</option>
                        <option value="notification">Notification</option>
                        <option value="alert">Alert</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Subject</label>
                      <input type="text" class="form-control" name="subject" required placeholder="Enter message subject">
                    </div>
                    <div class="col-12">
                      <label class="form-label">Message</label>
                      <textarea class="form-control" name="message" rows="4" required placeholder="Enter your message here..."></textarea>
                      <div class="form-text">
                        Maximum 1000 characters
                      </div>
                    </div>
                  </div>
                  <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
                    <button type="button" class="btn btn-secondary me-md-2" onclick="this.form.reset()">
                      <i class="bi bi-x"></i> Clear
                    </button>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send"></i> Send Message
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
          
          <div class="col-lg-7 mb-4">
            <div class="card">
              <div class="card-header bg-primary text-white">
                <strong>Recent Messages</strong>
              </div>
              <div class="card-body">
                <?php if (empty($messages)): ?>
                  <div class="text-center text-muted">No messages found</div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-striped table-hover">
                      <thead>
                        <tr>
                          <th>Subject</th>
                          <th>Recipient</th>
                          <th>Type</th>
                          <th>Status</th>
                          <th>Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php 
                        $displayMessages = array_slice($messages, 0, 10); // Show last 10 messages
                        foreach ($displayMessages as $message): 
                        ?>
                          <tr>
                            <td><?php echo h($message['subject']); ?></td>
                            <td>
                              <?php 
                              $recipientName = '';
                              foreach ($facultyOptions as $email => $fac) {
                                  if ($email === $message['recipient_email']) {
                                      $recipientName = $fac['name'];
                                      break;
                                  }
                              }
                              echo h($recipientName ?: $message['recipient_email']); 
                              ?>
                            </td>
                            <td>
                              <span class="badge bg-<?php echo getBadgeColor($message['message_type']); ?>">
                                <?php echo h(ucfirst($message['message_type'])); ?>
                              </span>
                            </td>
                            <td>
                              <span class="badge bg-<?php echo getStatusBadgeColor($message['delivery_status'] ?? 'sent'); ?>">
                                <?php echo h($message['delivery_status'] ?? 'sent'); ?>
                              </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', $message['created_at']); ?></td>
                            <td>
                              <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="viewMessage('<?php echo $message['id']; ?>')">
                                  <i class="bi bi-eye"></i> View
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="editMessageInline('<?php echo $message['id']; ?>')">
                                  <i class="bi bi-pencil"></i> Edit
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteMessageInline('<?php echo $message['id']; ?>')">
                                  <i class="bi bi-trash"></i> Delete
                                </button>
                              </div>
                            </td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Edit Message Modal -->
  <div class="modal fade" id="editMessageModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Message</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form method="post">
            <input type="hidden" name="action" value="edit_message">
            <input type="hidden" name="message_id" id="editMessageId">
            <div class="row g-3">
              <div class="col-12">
                <label class="form-label">Recipient</label>
                <input type="text" class="form-control" id="editRecipient" readonly>
              </div>
              <div class="col-12">
                <label class="form-label">Message Type</label>
                <select class="form-select" name="message_type" id="editMessageType" required>
                  <option value="general">General</option>
                  <option value="notification">Notification</option>
                  <option value="alert">Alert</option>
                </select>
              </div>
              <div class="col-12">
                <label class="form-label">Subject</label>
                <input type="text" class="form-control" name="subject" id="editSubject" required>
              </div>
              <div class="col-12">
                <label class="form-label">Message</label>
                <textarea class="form-control" name="message" id="editMessageText" rows="4" required></textarea>
              </div>
            </div>
            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-3">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Update Message</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

<script>
function viewMessage(messageId) {
    window.open('view_message.php?id=' + messageId, '_blank');
}

function editMessageInline(messageId) {
    <?php foreach ($messages as $msg): ?>
        if (messageId === '<?php echo $msg['id']; ?>') {
            document.getElementById('editMessageId').value = messageId;
            document.getElementById('editRecipient').value = '<?php echo h($msg['recipient_email']); ?>';
            document.getElementById('editMessageType').value = '<?php echo h($msg['message_type']); ?>';
            document.getElementById('editSubject').value = '<?php echo h($msg['subject']); ?>';
            document.getElementById('editMessageText').value = <?php echo json_encode($msg['message']); ?>;
            new bootstrap.Modal(document.getElementById('editMessageModal')).show();
        }
    <?php endforeach; ?>
}

function deleteMessageInline(messageId) {
    if (confirm('Are you sure you want to delete this message?')) {
        const form = document.createElement('form');
        form.method = 'post';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'delete_message';
        form.appendChild(actionInput);
        
        const messageIdInput = document.createElement('input');
        messageIdInput.type = 'hidden';
        messageIdInput.name = 'message_id';
        messageIdInput.value = messageId;
        form.appendChild(messageIdInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

function getBadgeColor(type) {
    switch (type) {
        case 'alert': return 'danger';
        case 'notification': return 'info';
        case 'general': return 'secondary';
        default: return 'secondary';
    }
}

function getStatusBadgeColor(status) {
    switch (status) {
        case 'sent': return 'success';
        case 'delivered': return 'info';
        case 'failed': return 'danger';
        default: return 'secondary';
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Function to update session options based on leave type
function updateSessionOptions(leaveType) {
    alert('Function called with: ' + leaveType); // Test alert
    var sessionSelect = document.getElementById('sessionSelect');
    var daysField = document.getElementById('daysField');
    var daysInput = daysField.querySelector('input[name="days"]');
    
    if (leaveType === 'CL') {
        // For CL, allow half-day sessions
        sessionSelect.innerHTML = '<option value="">Select session</option><option value="FN">FN (Forenoon)</option><option value="AN">AN (Afternoon)</option><option value="FULL">Full Day</option>';
        sessionSelect.value = '';
        daysField.style.display = 'none';
        daysInput.removeAttribute('required');
    } else {
        // For other leave types, only allow full day
        sessionSelect.innerHTML = '<option value="FULL">Full Day</option>';
        sessionSelect.value = 'FULL';
        daysInput.value = '1';
        daysField.style.display = 'none';
        daysInput.removeAttribute('required');
    }
}

// Handle session change for CL
document.getElementById('sessionSelect').addEventListener('change', function() {
    var daysField = document.getElementById('daysField');
    var daysInput = daysField.querySelector('input[name="days"]');
    var leaveType = document.querySelector('select[name="leave_type"]').value;
    
    if (leaveType === 'CL') {
        if (this.value === 'FN' || this.value === 'AN') {
            daysInput.value = '0.5';
            daysField.style.display = 'none';
            daysInput.removeAttribute('required');
        } else if (this.value === 'FULL') {
            daysInput.value = '1';
            daysField.style.display = 'none';
            daysInput.removeAttribute('required');
        } else {
            daysField.style.display = 'block';
            daysInput.setAttribute('required', 'required');
        }
    }
});

document.querySelector('input[name="from_date"]').addEventListener('change', function() {
    var toDate = document.getElementById('toDate');
    if (!toDate.value) {
        toDate.value = this.value;
    }
});
</script>
</body>
</html>
