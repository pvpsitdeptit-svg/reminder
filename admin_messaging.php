<?php
require_once 'config/firebase.php';

header('Content-Type: application/json');

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_faculty_list':
        getFacultyList();
        break;
    case 'send_message':
        sendMessage();
        break;
    case 'get_messages':
        getMessages();
        break;
    case 'delete_message':
        deleteMessage();
        break;
    case 'edit_message':
        editMessage();
        break;
    case 'get_delivery_status':
        getDeliveryStatus();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function getFacultyList() {
    try {
        // Get faculty from faculty_leave_master in Firebase
        $ref = $database->getReference('faculty_leave_master');
        $snapshot = $ref->getSnapshot();
        $facultyData = $snapshot->getValue();
        
        $faculty = [];
        if ($facultyData && is_array($facultyData)) {
            foreach ($facultyData as $key => $item) {
                if (isset($item['faculty_email']) && !empty($item['faculty_email'])) {
                    $faculty[] = [
                        'faculty_email' => $item['faculty_email'],
                        'name' => $item['name'] ?? '',
                        'department' => $item['department'] ?? '',
                        'employee_id' => $item['employee_id'] ?? '',
                        'fcm_token' => null // Will be checked separately
                    ];
                }
            }
        }
        
        // Check FCM tokens for each faculty
        $fcmRef = $database->getReference('fcm_tokens');
        $fcmSnapshot = $fcmRef->getSnapshot();
        $fcmTokens = $fcmSnapshot->getValue();
        
        if ($fcmTokens && is_array($fcmTokens)) {
            foreach ($faculty as &$fac) {
                $sanitizedEmail = str_replace(['.', '@'], ['_', '_'], $fac['faculty_email']);
                $fac['fcm_token'] = $fcmTokens[$sanitizedEmail] ?? null;
            }
        }
        
        echo json_encode([
            'success' => true,
            'faculty' => $faculty
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching faculty list: ' . $e->getMessage()
        ]);
    }
}

function sendMessage() {
    $recipientEmail = $_POST['recipient_email'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $messageType = $_POST['message_type'] ?? 'general';
    $senderEmail = $_SESSION['admin_email'] ?? 'admin@reminder.com';
    
    if (empty($recipientEmail) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
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
                'message' => substr($message, 0, 200), // Truncate for FCM payload
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
        
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully',
            'delivery_status' => $deliveryStatus,
            'message_id' => $messageId
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error sending message: ' . $e->getMessage()
        ]);
    }
}

function getMessages() {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    try {
        // Get messages from Firebase
        $messagesRef = $database->getReference('admin_messages');
        $snapshot = $messagesRef->getSnapshot();
        $allMessages = $snapshot->getValue();
        
        $messages = [];
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
            
            // Apply pagination
            $messages = array_slice($messageArray, $offset, $limit);
            
            // Add faculty details
            foreach ($messages as &$msg) {
                $facultyRef = $database->getReference('faculty_leave_master');
                $facultySnapshot = $facultyRef->getSnapshot();
                $facultyData = $facultySnapshot->getValue();
                
                if ($facultyData && is_array($facultyData)) {
                    foreach ($facultyData as $faculty) {
                        if (isset($faculty['faculty_email']) && $faculty['faculty_email'] === $msg['recipient_email']) {
                            $msg['recipient_name'] = $faculty['name'] ?? '';
                            $msg['department'] = $faculty['department'] ?? '';
                            break;
                        }
                    }
                }
            }
        }
        
        $totalMessages = count($allMessages ?? []);
        
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $limit,
                'total' => $totalMessages,
                'total_pages' => ceil($totalMessages / $limit)
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching messages: ' . $e->getMessage()
        ]);
    }
}

function deleteMessage() {
    $messageId = $_POST['message_id'] ?? '';
    
    if (empty($messageId)) {
        echo json_encode(['success' => false, 'message' => 'Message ID is required']);
        return;
    }
    
    try {
        $database->getReference('admin_messages/' . $messageId)->remove();
        
        echo json_encode([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting message: ' . $e->getMessage()
        ]);
    }
}

function editMessage() {
    $messageId = $_POST['message_id'] ?? '';
    $subject = $_POST['subject'] ?? '';
    $message = $_POST['message'] ?? '';
    $messageType = $_POST['message_type'] ?? 'general';
    
    if (empty($messageId) || empty($subject) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    try {
        $updateData = [
            'subject' => $subject,
            'message' => $message,
            'message_type' => $messageType,
            'updated_at' => time()
        ];
        
        $database->getReference('admin_messages/' . $messageId)->update($updateData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Message updated successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error updating message: ' . $e->getMessage()
        ]);
    }
}

function getDeliveryStatus() {
    $messageId = $_GET['message_id'] ?? '';
    
    if (empty($messageId)) {
        echo json_encode(['success' => false, 'message' => 'Message ID is required']);
        return;
    }
    
    try {
        $deliveryRef = $database->getReference('message_delivery');
        $snapshot = $deliveryRef->getSnapshot();
        $deliveryData = $snapshot->getValue();
        
        $delivery = [];
        if ($deliveryData && is_array($deliveryData)) {
            foreach ($deliveryData as $key => $record) {
                if ($record['message_id'] === $messageId) {
                    $record['id'] = $key;
                    $delivery[] = $record;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'delivery_status' => $delivery
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error fetching delivery status: ' . $e->getMessage()
        ]);
    }
}
?>
