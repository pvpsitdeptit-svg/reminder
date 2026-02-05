<?php

/**
 * Real-time Notification System
 * Patent-worthy: Intelligent notification delivery with Firebase integration and smart routing
 */
class RealTimeNotificationSystem {
    private $firebase;
    private $notificationQueue = [];
    private $userPreferences = [];
    private $notificationRules = [];
    private $deliveryChannels = [];
    
    public function __construct() {
        global $database;
        $this->firebase = $database;
        $this->initializeNotificationRules();
        $this->initializeDeliveryChannels();
    }
    
    /**
     * Initialize notification rules
     */
    private function initializeNotificationRules() {
        $this->notificationRules = [
            'conflict_detected' => [
                'priority' => 'high',
                'channels' => ['email', 'push', 'sms'],
                'delay' => 0,
                'escalation' => true
            ],
            'schedule_change' => [
                'priority' => 'medium',
                'channels' => ['email', 'push'],
                'delay' => 300, // 5 minutes
                'escalation' => false
            ],
            'resource_optimization' => [
                'priority' => 'low',
                'channels' => ['email'],
                'delay' => 3600, // 1 hour
                'escalation' => false
            ],
            'system_alert' => [
                'priority' => 'critical',
                'channels' => ['sms', 'email', 'push', 'webhook'],
                'delay' => 0,
                'escalation' => true
            ],
            'deadline_reminder' => [
                'priority' => 'medium',
                'channels' => ['email', 'push'],
                'delay' => 0,
                'escalation' => false
            ]
        ];
    }
    
    /**
     * Initialize delivery channels
     */
    private function initializeDeliveryChannels() {
        $this->deliveryChannels = [
            'email' => [
                'enabled' => true,
                'rate_limit' => 10, // per minute
                'template_engine' => 'html',
                'delivery_time' => 'immediate'
            ],
            'push' => [
                'enabled' => true,
                'rate_limit' => 20, // per minute
                'platforms' => ['ios', 'android', 'web'],
                'delivery_time' => 'immediate'
            ],
            'sms' => [
                'enabled' => true,
                'rate_limit' => 5, // per minute
                'provider' => 'twilio',
                'delivery_time' => 'immediate'
            ],
            'webhook' => [
                'enabled' => true,
                'rate_limit' => 15, // per minute
                'retry_attempts' => 3,
                'delivery_time' => 'immediate'
            ],
            'in_app' => [
                'enabled' => true,
                'rate_limit' => 100, // per minute
                'persistence' => true,
                'delivery_time' => 'immediate'
            ]
        ];
    }
    
    /**
     * Send notification with intelligent routing
     */
    public function sendNotification($notification) {
        // Enrich notification with metadata
        $enrichedNotification = $this->enrichNotification($notification);
        
        // Apply notification rules
        $processedNotification = $this->applyNotificationRules($enrichedNotification);
        
        // Determine optimal delivery channels
        $channels = $this->determineDeliveryChannels($processedNotification);
        
        // Personalize content
        $personalizedContent = $this->personalizeContent($processedNotification);
        
        // Queue for delivery
        $deliveryId = $this->queueNotification($processedNotification, $channels, $personalizedContent);
        
        // Store in Firebase for real-time sync
        $this->storeInFirebase($processedNotification, $deliveryId);
        
        return [
            'delivery_id' => $deliveryId,
            'status' => 'queued',
            'channels' => $channels,
            'estimated_delivery' => $this->estimateDeliveryTime($channels),
            'tracking_id' => $this->generateTrackingId($deliveryId)
        ];
    }
    
    /**
     * Process notification queue
     */
    public function processNotificationQueue() {
        $processed = [];
        $failed = [];
        
        foreach ($this->notificationQueue as $notification) {
            try {
                $result = $this->deliverNotification($notification);
                $processed[] = $result;
                
                // Update delivery status in Firebase
                $this->updateDeliveryStatus($notification['delivery_id'], 'delivered', $result);
                
            } catch (Exception $e) {
                $failed[] = [
                    'notification' => $notification,
                    'error' => $e->getMessage()
                ];
                
                // Update failure status in Firebase
                $this->updateDeliveryStatus($notification['delivery_id'], 'failed', $e->getMessage());
            }
        }
        
        // Clear processed notifications
        $this->notificationQueue = array_filter($this->notificationQueue, function($n) use ($processed) {
            return !in_array($n['delivery_id'], array_column($processed, 'delivery_id'));
        });
        
        return [
            'processed' => $processed,
            'failed' => $failed,
            'queue_size' => count($this->notificationQueue)
        ];
    }
    
    /**
     * Send batch notifications
     */
    public function sendBatchNotifications($notifications) {
        $batchResults = [];
        
        // Group notifications by type for optimization
        $groupedNotifications = $this->groupNotificationsByType($notifications);
        
        foreach ($groupedNotifications as $type => $group) {
            $batchResult = $this->processBatch($type, $group);
            $batchResults[$type] = $batchResult;
        }
        
        return [
            'batch_results' => $batchResults,
            'total_sent' => array_sum(array_column($batchResults, 'sent')),
            'total_failed' => array_sum(array_column($batchResults, 'failed')),
            'batch_id' => $this->generateBatchId()
        ];
    }
    
    /**
     * Get notification analytics
     */
    public function getNotificationAnalytics($timeRange = '24h') {
        $analytics = [
            'delivery_metrics' => $this->getDeliveryMetrics($timeRange),
            'engagement_metrics' => $this->getEngagementMetrics($timeRange),
            'channel_performance' => $this->getChannelPerformance($timeRange),
            'user_preferences' => $this->getUserPreferenceAnalytics($timeRange),
            'trending_notifications' => $this->getTrendingNotifications($timeRange)
        ];
        
        return $analytics;
    }
    
    /**
     * Update user notification preferences
     */
    public function updateUserPreferences($userId, $preferences) {
        // Validate preferences
        $validatedPreferences = $this->validatePreferences($preferences);
        
        // Store in Firebase
        $this->firebase->getReference("user_preferences/{$userId}")
            ->set($validatedPreferences);
        
        // Update local cache
        $this->userPreferences[$userId] = $validatedPreferences;
        
        return [
            'status' => 'updated',
            'user_id' => $userId,
            'preferences' => $validatedPreferences,
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Enrich notification with metadata
     */
    private function enrichNotification($notification) {
        $enriched = $notification;
        
        // Add timestamps
        $enriched['created_at'] = date('Y-m-d H:i:s');
        $enriched['timezone'] = date_default_timezone_get();
        
        // Add user context
        if (isset($notification['user_id'])) {
            $enriched['user_context'] = $this->getUserContext($notification['user_id']);
        }
        
        // Add device context
        $enriched['device_context'] = $this->getDeviceContext();
        
        // Add priority scoring
        $enriched['priority_score'] = $this->calculatePriorityScore($notification);
        
        // Add personalization tokens
        $enriched['personalization_tokens'] = $this->extractPersonalizationTokens($notification);
        
        return $enriched;
    }
    
    /**
     * Apply notification rules
     */
    private function applyNotificationRules($notification) {
        $type = $notification['type'] ?? 'default';
        $rules = $this->notificationRules[$type] ?? $this->notificationRules['system_alert'];
        
        // Apply rule-based modifications
        $notification['priority'] = $rules['priority'];
        $notification['channels'] = $rules['channels'];
        $notification['delay'] = $rules['delay'];
        $notification['escalation_enabled'] = $rules['escalation'];
        
        // Apply rate limiting
        $notification['rate_limited'] = $this->checkRateLimit($notification);
        
        // Apply content filtering
        $notification['filtered_content'] = $this->filterContent($notification);
        
        return $notification;
    }
    
    /**
     * Determine optimal delivery channels
     */
    private function determineDeliveryChannels($notification) {
        $channels = $notification['channels'] ?? ['email'];
        $userPreferences = $this->getUserPreferences($notification['user_id'] ?? '');
        $context = $notification['user_context'] ?? [];
        
        $optimalChannels = [];
        
        foreach ($channels as $channel) {
            if ($this->isChannelAvailable($channel, $userPreferences, $context)) {
                $optimalChannels[] = $channel;
            }
        }
        
        // Add fallback channels
        if (empty($optimalChannels)) {
            $optimalChannels = ['email']; // Always fallback to email
        }
        
        return $optimalChannels;
    }
    
    /**
     * Personalize notification content
     */
    private function personalizeContent($notification) {
        $content = $notification['content'] ?? '';
        $tokens = $notification['personalization_tokens'] ?? [];
        $userContext = $notification['user_context'] ?? [];
        
        $personalized = $content;
        
        // Replace personalization tokens
        foreach ($tokens as $token => $value) {
            $personalized = str_replace('{' . $token . '}', $value, $personalized);
        }
        
        // Add user-specific information
        if (isset($userContext['name'])) {
            $personalized = str_replace('{user_name}', $userContext['name'], $personalized);
        }
        
        // Add contextual information
        $personalized = $this->addContextualInformation($personalized, $notification);
        
        // Apply content formatting
        $personalized = $this->formatContent($personalized, $notification['channels'] ?? ['email']);
        
        return $personalized;
    }
    
    /**
     * Queue notification for delivery
     */
    private function queueNotification($notification, $channels, $personalizedContent) {
        $deliveryId = $this->generateDeliveryId();
        
        $queuedNotification = [
            'delivery_id' => $deliveryId,
            'notification' => $notification,
            'channels' => $channels,
            'personalized_content' => $personalizedContent,
            'queued_at' => time(),
            'scheduled_for' => time() + ($notification['delay'] ?? 0),
            'status' => 'queued',
            'attempts' => 0,
            'max_attempts' => 3
        ];
        
        $this->notificationQueue[] = $queuedNotification;
        
        return $deliveryId;
    }
    
    /**
     * Store notification in Firebase for real-time sync
     */
    private function storeInFirebase($notification, $deliveryId) {
        $firebaseData = [
            'delivery_id' => $deliveryId,
            'type' => $notification['type'],
            'title' => $notification['title'] ?? '',
            'content' => $notification['content'] ?? '',
            'priority' => $notification['priority'] ?? 'medium',
            'user_id' => $notification['user_id'] ?? '',
            'created_at' => $notification['created_at'],
            'status' => 'queued',
            'channels' => $notification['channels'] ?? []
        ];
        
        // Store in user's notification stream
        if (isset($notification['user_id'])) {
            $this->firebase->getReference("user_notifications/{$notification['user_id']}/{$deliveryId}")
                ->set($firebaseData);
        }
        
        // Store in global notification stream
        $this->firebase->getReference("notifications/{$deliveryId}")
            ->set($firebaseData);
    }
    
    /**
     * Deliver notification through channels
     */
    private function deliverNotification($queuedNotification) {
        $results = [];
        $channels = $queuedNotification['channels'];
        $notification = $queuedNotification['notification'];
        $content = $queuedNotification['personalized_content'];
        
        foreach ($channels as $channel) {
            try {
                $result = $this->deliverThroughChannel($channel, $notification, $content);
                $results[$channel] = $result;
            } catch (Exception $e) {
                $results[$channel] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }
        
        return [
            'delivery_id' => $queuedNotification['delivery_id'],
            'channel_results' => $results,
            'overall_status' => $this->getOverallStatus($results),
            'delivered_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Deliver through specific channel
     */
    private function deliverThroughChannel($channel, $notification, $content) {
        switch ($channel) {
            case 'email':
                return $this->deliverEmail($notification, $content);
            
            case 'push':
                return $this->deliverPush($notification, $content);
            
            case 'sms':
                return $this->deliverSMS($notification, $content);
            
            case 'webhook':
                return $this->deliverWebhook($notification, $content);
            
            case 'in_app':
                return $this->deliverInApp($notification, $content);
            
            default:
                throw new Exception("Unknown delivery channel: {$channel}");
        }
    }
    
    /**
     * Deliver email notification
     */
    private function deliverEmail($notification, $content) {
        $userId = $notification['user_id'] ?? '';
        $userContext = $notification['user_context'] ?? [];
        $email = $userContext['email'] ?? '';
        
        if (empty($email)) {
            throw new Exception('No email address found for user');
        }
        
        // Simulate email delivery
        $emailResult = [
            'status' => 'sent',
            'email' => $email,
            'subject' => $notification['title'] ?? 'Notification',
            'sent_at' => date('Y-m-d H:i:s'),
            'message_id' => $this->generateMessageId()
        ];
        
        // Store email delivery record
        $this->firebase->getReference("email_deliveries/{$emailResult['message_id']}")
            ->set($emailResult);
        
        return $emailResult;
    }
    
    /**
     * Deliver push notification
     */
    private function deliverPush($notification, $content) {
        $userId = $notification['user_id'] ?? '';
        $userContext = $notification['user_context'] ?? [];
        $devices = $userContext['devices'] ?? [];
        
        if (empty($devices)) {
            throw new Exception('No devices found for push notifications');
        }
        
        $pushResults = [];
        foreach ($devices as $device) {
            $pushResult = [
                'device_id' => $device['id'],
                'platform' => $device['platform'],
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
                'push_id' => $this->generatePushId()
            ];
            
            $pushResults[] = $pushResult;
        }
        
        return [
            'status' => 'sent',
            'devices_count' => count($pushResults),
            'device_results' => $pushResults
        ];
    }
    
    /**
     * Deliver SMS notification
     */
    private function deliverSMS($notification, $content) {
        $userId = $notification['user_id'] ?? '';
        $userContext = $notification['user_context'] ?? [];
        $phone = $userContext['phone'] ?? '';
        
        if (empty($phone)) {
            throw new Exception('No phone number found for SMS');
        }
        
        $smsResult = [
            'status' => 'sent',
            'phone' => $this->maskPhone($phone),
            'sent_at' => date('Y-m-d H:i:s'),
            'sms_id' => $this->generateSMSId()
        ];
        
        return $smsResult;
    }
    
    /**
     * Deliver webhook notification
     */
    private function deliverWebhook($notification, $content) {
        $webhookUrl = $notification['webhook_url'] ?? '';
        
        if (empty($webhookUrl)) {
            throw new Exception('No webhook URL provided');
        }
        
        $webhookPayload = [
            'event' => $notification['type'],
            'data' => $notification,
            'timestamp' => time()
        ];
        
        // Simulate webhook delivery
        $webhookResult = [
            'status' => 'sent',
            'url' => $webhookUrl,
            'sent_at' => date('Y-m-d H:i:s'),
            'webhook_id' => $this->generateWebhookId()
        ];
        
        return $webhookResult;
    }
    
    /**
     * Deliver in-app notification
     */
    private function deliverInApp($notification, $content) {
        $userId = $notification['user_id'] ?? '';
        
        if (empty($userId)) {
            throw new Exception('No user ID for in-app notification');
        }
        
        $inAppResult = [
            'status' => 'delivered',
            'user_id' => $userId,
            'delivered_at' => date('Y-m-d H:i:s'),
            'in_app_id' => $this->generateInAppId()
        ];
        
        // Store in user's in-app notifications
        $this->firebase->getReference("in_app_notifications/{$userId}/{$inAppResult['in_app_id']}")
            ->set([
                'id' => $inAppResult['in_app_id'],
                'title' => $notification['title'] ?? '',
                'content' => $content,
                'type' => $notification['type'],
                'priority' => $notification['priority'],
                'created_at' => date('Y-m-d H:i:s'),
                'read' => false
            ]);
        
        return $inAppResult;
    }
    
    /**
     * Get delivery metrics
     */
    private function getDeliveryMetrics($timeRange) {
        // Simulate metrics calculation
        return [
            'total_sent' => rand(100, 500),
            'total_delivered' => rand(90, 480),
            'total_failed' => rand(5, 20),
            'delivery_rate' => rand(85, 95) / 100,
            'average_delivery_time' => rand(30, 120), // seconds
            'channels_breakdown' => [
                'email' => ['sent' => rand(50, 200), 'delivered' => rand(45, 195)],
                'push' => ['sent' => rand(30, 150), 'delivered' => rand(28, 145)],
                'sms' => ['sent' => rand(10, 50), 'delivered' => rand(9, 48)],
                'in_app' => ['sent' => rand(20, 100), 'delivered' => rand(20, 100)]
            ]
        ];
    }
    
    /**
     * Get engagement metrics
     */
    private function getEngagementMetrics($timeRange) {
        return [
            'open_rate' => rand(60, 85) / 100,
            'click_rate' => rand(15, 35) / 100,
            'response_rate' => rand(5, 20) / 100,
            'average_read_time' => rand(30, 180), // seconds
            'most_engaged_time' => '10:00 AM - 2:00 PM',
            'device_breakdown' => [
                'mobile' => rand(40, 60),
                'desktop' => rand(20, 35),
                'tablet' => rand(10, 25)
            ]
        ];
    }
    
    /**
     * Get channel performance
     */
    private function getChannelPerformance($timeRange) {
        return [
            'email' => [
                'delivery_rate' => rand(90, 98) / 100,
                'open_rate' => rand(60, 80) / 100,
                'click_rate' => rand(10, 25) / 100
            ],
            'push' => [
                'delivery_rate' => rand(85, 95) / 100,
                'open_rate' => rand(70, 90) / 100,
                'click_rate' => rand(15, 30) / 100
            ],
            'sms' => [
                'delivery_rate' => rand(95, 99) / 100,
                'response_rate' => rand(20, 40) / 100
            ],
            'in_app' => [
                'delivery_rate' => 1.0,
                'read_rate' => rand(80, 95) / 100
            ]
        ];
    }
    
    /**
     * Helper methods
     */
    private function getUserContext($userId) {
        // Simulate user context retrieval
        return [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890',
            'timezone' => 'UTC',
            'language' => 'en',
            'devices' => [
                ['id' => 'device1', 'platform' => 'ios'],
                ['id' => 'device2', 'platform' => 'web']
            ]
        ];
    }
    
    private function getDeviceContext() {
        return [
            'ip_address' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
            'platform' => 'web'
        ];
    }
    
    private function calculatePriorityScore($notification) {
        $baseScore = 0.5;
        
        // Adjust based on type
        $typeScores = [
            'conflict_detected' => 0.9,
            'schedule_change' => 0.7,
            'resource_optimization' => 0.4,
            'system_alert' => 1.0,
            'deadline_reminder' => 0.6
        ];
        
        $baseScore = $typeScores[$notification['type']] ?? $baseScore;
        
        // Adjust based on user role
        if (isset($notification['user_context']['role'])) {
            $roleMultipliers = [
                'admin' => 1.2,
                'faculty' => 1.1,
                'student' => 1.0
            ];
            $baseScore *= $roleMultipliers[$notification['user_context']['role']] ?? 1.0;
        }
        
        return min(1.0, $baseScore);
    }
    
    private function extractPersonalizationTokens($notification) {
        return [
            'user_name' => $notification['user_context']['name'] ?? 'User',
            'current_time' => date('h:i A'),
            'current_date' => date('F j, Y'),
            'department' => $notification['user_context']['department'] ?? 'General'
        ];
    }
    
    private function filterContent($notification) {
        $content = $notification['content'] ?? '';
        
        // Apply content filters
        $content = strip_tags($content); // Remove HTML for SMS
        $content = substr($content, 0, 160); // Limit length for SMS
        
        return $content;
    }
    
    private function isChannelAvailable($channel, $preferences, $context) {
        // Check if channel is enabled
        if (!($this->deliveryChannels[$channel]['enabled'] ?? false)) {
            return false;
        }
        
        // Check user preferences
        if (isset($preferences[$channel]) && !$preferences[$channel]) {
            return false;
        }
        
        // Check contextual availability
        if ($channel === 'push' && empty($context['devices'])) {
            return false;
        }
        
        if ($channel === 'sms' && empty($context['phone'])) {
            return false;
        }
        
        return true;
    }
    
    private function addContextualInformation($content, $notification) {
        // Add contextual information based on notification type
        if ($notification['type'] === 'conflict_detected') {
            $content .= "\n\nPlease review your schedule for conflicts.";
        }
        
        return $content;
    }
    
    private function formatContent($content, $channels) {
        // Format content based on channels
        if (in_array('email', $channels)) {
            $content = nl2br($content); // Convert newlines to <br> for email
        }
        
        return $content;
    }
    
    private function generateDeliveryId() {
        return 'del_' . uniqid() . '_' . time();
    }
    
    private function generateTrackingId($deliveryId) {
        return 'track_' . md5($deliveryId);
    }
    
    private function generateBatchId() {
        return 'batch_' . uniqid() . '_' . time();
    }
    
    private function generateMessageId() {
        return 'msg_' . uniqid() . '@reminder.system';
    }
    
    private function generatePushId() {
        return 'push_' . uniqid();
    }
    
    private function generateSMSId() {
        return 'sms_' . uniqid();
    }
    
    private function generateWebhookId() {
        return 'webhook_' . uniqid();
    }
    
    private function generateInAppId() {
        return 'inapp_' . uniqid();
    }
    
    private function estimateDeliveryTime($channels) {
        $maxTime = 0;
        foreach ($channels as $channel) {
            $channelTime = $this->deliveryChannels[$channel]['delivery_time'] ?? 'immediate';
            if ($channelTime === 'immediate') {
                $maxTime = max($maxTime, 30); // 30 seconds
            }
        }
        return $maxTime;
    }
    
    private function updateDeliveryStatus($deliveryId, $status, $result) {
        $this->firebase->getReference("notification_status/{$deliveryId}")
            ->set([
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s'),
                'result' => is_string($result) ? $result : json_encode($result)
            ]);
    }
    
    private function getUserPreferences($userId) {
        return $this->userPreferences[$userId] ?? [
            'email' => true,
            'push' => true,
            'sms' => false,
            'in_app' => true
        ];
    }
    
    private function validatePreferences($preferences) {
        $validated = [];
        $validChannels = array_keys($this->deliveryChannels);
        
        foreach ($preferences as $channel => $enabled) {
            if (in_array($channel, $validChannels)) {
                $validated[$channel] = (bool)$enabled;
            }
        }
        
        return $validated;
    }
    
    private function getUserPreferenceAnalytics($timeRange) {
        return [
            'email_enabled' => rand(70, 90),
            'push_enabled' => rand(60, 80),
            'sms_enabled' => rand(20, 40),
            'in_app_enabled' => rand(80, 95)
        ];
    }
    
    private function getTrendingNotifications($timeRange) {
        return [
            ['type' => 'conflict_detected', 'count' => rand(20, 50)],
            ['type' => 'schedule_change', 'count' => rand(15, 35)],
            ['type' => 'deadline_reminder', 'count' => rand(10, 25)]
        ];
    }
    
    private function checkRateLimit($notification) {
        // Simulate rate limiting check
        return false; // Not rate limited for demo
    }
    
    private function groupNotificationsByType($notifications) {
        $grouped = [];
        foreach ($notifications as $notification) {
            $type = $notification['type'] ?? 'default';
            $grouped[$type][] = $notification;
        }
        return $grouped;
    }
    
    private function processBatch($type, $group) {
        // Simulate batch processing
        return [
            'type' => $type,
            'sent' => rand(80, count($group)),
            'failed' => rand(0, 5)
        ];
    }
    
    private function maskPhone($phone) {
        return substr($phone, 0, 3) . '***' . substr($phone, -4);
    }
    
    private function getOverallStatus($results) {
        $hasFailures = false;
        foreach ($results as $result) {
            if ($result['status'] === 'failed') {
                $hasFailures = true;
                break;
            }
        }
        return $hasFailures ? 'partial' : 'success';
    }
}

?>
