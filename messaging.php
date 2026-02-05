<?php
require_once 'config/firebase.php';
require_once 'firebase_auth.php';

// Require admin role for this page (before any HTML output)
requireAdmin();

// Get current user email
$currentUser = getCurrentUser();
$userEmail = $currentUser['email'] ?? '';
$userDisplayName = $currentUser['displayName'] ?? $userEmail;

// Handle form submission for sending messages (before any HTML output)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $recipient = $_POST['recipient'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    
    try {
        if (empty($recipient) || empty($subject) || empty($message)) {
            $_SESSION['error_message'] = 'Please fill all required fields';
        } else {
            $messageData = [
                'sender_email' => $userEmail,
                'recipient_email' => $recipient,
                'subject' => $subject,
                'message' => $message,
                'sent_at' => time(),
                'status' => 'sent',
                'read' => false
            ];
            
            // Save to Firebase
            $database->getReference('messages')->push($messageData);
            
            // Send FCM push notification using existing function
            try {
                // Get recipient's FCM tokens from database
                $recipientTokens = getUserFCMTokens($recipient);
                
                if (!empty($recipientTokens)) {
                    $notificationData = [
                        'sender_email' => $userEmail,
                        'recipient_email' => $recipient,
                        'type' => 'message',
                        'message_id' => $database->getReference('messages')->push($messageData)->getKey()
                    ];
                    
                    // Use existing FCM function from firebase.php
                    sendFCMNotification(null, $subject, $message, $notificationData, $recipientTokens);
                }
            } catch (Exception $e) {
                error_log("FCM notification failed: " . $e->getMessage());
                // Continue even if FCM fails
            }
            
            $_SESSION['success_message'] = 'Message sent successfully!';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error sending message: ' . $e->getMessage();
    }
    
    header('Location: messaging.php');
    exit;
}

require_once 'includes/header.php';

// Function to get user's FCM tokens from database
function getUserFCMTokens($email) {
    global $database;
    
    try {
        // Sanitize email for Firebase path
        $sanitizedEmail = str_replace('.', '_', $email);
        $sanitizedEmail = str_replace('@', '_', $sanitizedEmail);
        
        // Get tokens from the same path used by Android app
        $tokens_ref = $database->getReference('users/' . $sanitizedEmail . '/fcm_tokens');
        $tokens_snapshot = $tokens_ref->getSnapshot();
        $tokens_data = $tokens_snapshot->exists() ? $tokens_snapshot->getValue() : [];
        
        // Extract tokens from the data structure
        $tokens = [];
        if (is_array($tokens_data)) {
            foreach ($tokens_data as $token_data) {
                if (is_array($token_data) && isset($token_data['token'])) {
                    $tokens[] = $token_data['token'];
                } elseif (is_string($token_data)) {
                    $tokens[] = $token_data;
                }
            }
        }
        
        return array_unique($tokens);
        
    } catch (Exception $e) {
        error_log("Error getting FCM tokens for $email: " . $e->getMessage());
        return [];
    }
}

// Fetch messages
$sentMessages = [];
$receivedMessages = [];

try {
    // Fetch all messages
    $messages_ref = $database->getReference('messages');
    $messages_snapshot = $messages_ref->getSnapshot();
    $all_messages = $messages_snapshot->exists() ? $messages_snapshot->getValue() : [];
    
    // Separate sent and received messages
    foreach ($all_messages as $key => $msg) {
        $msg['id'] = $key;
        if (isset($msg['sender_email']) && $msg['sender_email'] === $userEmail) {
            $sentMessages[] = $msg;
        } elseif (isset($msg['recipient_email']) && $msg['recipient_email'] === $userEmail) {
            $receivedMessages[] = $msg;
        }
    }
    
    // Sort by date (newest first)
    usort($sentMessages, function($a, $b) {
        return ($b['sent_at'] ?? 0) - ($a['sent_at'] ?? 0);
    });
    
    usort($receivedMessages, function($a, $b) {
        return ($b['sent_at'] ?? 0) - ($a['sent_at'] ?? 0);
    });
    
} catch (Exception $e) {
    $error_message = 'Error loading messages: ' . $e->getMessage();
}

// Fetch faculty list for recipients
$facultyList = [];
try {
    $faculty_ref = $database->getReference('faculty_leave_master');
    $faculty_snapshot = $faculty_ref->getSnapshot();
    $facultyList = $faculty_snapshot->exists() ? $faculty_snapshot->getValue() : [];
} catch (Exception $e) {
    // Continue without faculty list
}
?>

<div class="container my-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="bi bi-chat-dots"></i> Messaging System
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

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle"></i> <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Send Message -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Send Message</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Recipient</label>
                            <select name="recipient" class="form-select" required>
                                <option value="">Select recipient</option>
                                <?php foreach ($facultyList as $key => $faculty): ?>
                                    <option value="<?php echo h($faculty['faculty_email'] ?? ''); ?>">
                                        <?php echo h($faculty['name'] ?? $faculty['faculty_email'] ?? ''); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-send"></i> Send Message
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Messages -->
        <div class="col-lg-8">
            <ul class="nav nav-tabs" id="messageTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="received-tab" data-bs-toggle="tab" data-bs-target="#received" type="button" role="tab">
                        <i class="bi bi-inbox"></i> Received (<?php echo count($receivedMessages); ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button" role="tab">
                        <i class="bi bi-send"></i> Sent (<?php echo count($sentMessages); ?>)
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="messageTabsContent">
                <!-- Received Messages -->
                <div class="tab-pane fade show active" id="received" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <?php if (empty($receivedMessages)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="text-muted mt-3">No Messages</h5>
                                    <p class="text-muted">You haven't received any messages yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($receivedMessages as $msg): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo h($msg['subject']); ?></h6>
                                                    <p class="mb-1"><?php echo h($msg['message']); ?></p>
                                                    <small class="text-muted">
                                                        From: <?php echo h($msg['sender_email']); ?> • 
                                                        <?php echo date('M j, Y H:i', $msg['sent_at']); ?>
                                                    </small>
                                                </div>
                                                <?php if (!($msg['read'] ?? true)): ?>
                                                    <span class="badge bg-primary">New</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Sent Messages -->
                <div class="tab-pane fade" id="sent" role="tabpanel">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <?php if (empty($sentMessages)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-send text-muted" style="font-size: 3rem;"></i>
                                    <h5 class="text-muted mt-3">No Sent Messages</h5>
                                    <p class="text-muted">You haven't sent any messages yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group">
                                    <?php foreach ($sentMessages as $msg): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?php echo h($msg['subject']); ?></h6>
                                                    <p class="mb-1"><?php echo h($msg['message']); ?></p>
                                                    <small class="text-muted">
                                                        To: <?php echo h($msg['recipient_email']); ?> • 
                                                        <?php echo date('M j, Y H:i', $msg['sent_at']); ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-success">Sent</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
