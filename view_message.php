<?php
require_once 'config/firebase.php';

$messageId = $_GET['id'] ?? '';

if (empty($messageId)) {
    die('Message ID is required');
}

try {
    // Get message from Firebase
    $messageRef = $database->getReference('admin_messages/' . $messageId);
    $snapshot = $messageRef->getSnapshot();
    $message = $snapshot->getValue();
    
    if (!$message) {
        die('Message not found');
    }
    
    // Mark message as read
    $database->getReference('admin_messages/' . $messageId)->update([
        'status' => 'read',
        'read_at' => time()
    ]);
    
} catch (Exception $e) {
    die('Error loading message: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message - <?php echo htmlspecialchars($message['subject']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="bi bi-envelope"></i> 
                            <?php echo htmlspecialchars($message['subject']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>From:</strong><br>
                                <?php echo htmlspecialchars($message['sender_email']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Date:</strong><br>
                                <?php echo date('M j, Y g:i A', $message['created_at']); ?>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>To:</strong><br>
                                <?php echo htmlspecialchars($message['recipient_email']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Type:</strong><br>
                                <span class="badge bg-<?php echo getBadgeColor($message['message_type']); ?>">
                                    <?php echo ucfirst($message['message_type']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <div class="message-content">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" onclick="window.close()">
                            <i class="bi bi-x"></i> Close
                        </button>
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="bi bi-printer"></i> Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
function getBadgeColor($type) {
    switch ($type) {
        case 'alert': return 'danger';
        case 'notification': return 'info';
        case 'general': return 'secondary';
        default: return 'secondary';
    }
}
?>
