<?php
require_once 'config/firebase.php';

// Add this to your FCMService.kt onMessageReceived method to handle admin messages

/*
Add this case to your onMessageReceived method in FCMService.kt:

// Handle admin messages
if (type == "admin_message") {
    Log.d("FCMService", "Processing admin message")
    showAdminMessageNotification(remoteMessage)
    return
}

And add this new function to FCMService.kt:

private fun showAdminMessageNotification(remoteMessage: RemoteMessage) {
    try {
        val messageId = remoteMessage.data["message_id"] ?: ""
        val subject = remoteMessage.data["subject"] ?: ""
        val message = remoteMessage.data["message"] ?: ""
        val senderEmail = remoteMessage.data["sender_email"] ?: ""
        val fullMessageUrl = remoteMessage.data["full_message_url"] ?: ""

        val title = "Admin Message"
        val notificationText = if (message.length > 50) {
            message.take(50) + "..."
        } else {
            message
        }

        // Create intent to open full message
        val intent = Intent(applicationContext, MainActivity::class.java).apply {
            flags = Intent.FLAG_ACTIVITY_NEW_TASK or Intent.FLAG_ACTIVITY_CLEAR_TASK
            putExtra("open_message_url", fullMessageUrl)
            putExtra("message_id", messageId)
        }

        val pendingIntent = PendingIntent.getActivity(
            applicationContext,
            messageId.hashCode(),
            intent,
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        // Ensure channel exists
        NotificationScheduler().createNotificationChannel(applicationContext)

        val notificationBuilder = NotificationCompat.Builder(applicationContext, "admin_messages")
            .setSmallIcon(R.drawable.ic_notification)
            .setContentTitle(title)
            .setContentText(notificationText)
            .setStyle(NotificationCompat.BigTextStyle().bigText(message))
            .setPriority(NotificationCompat.PRIORITY_HIGH)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)

        val notificationManager = getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        val notificationId = (System.currentTimeMillis() % Int.MAX_VALUE).toInt()
        notificationManager.notify(notificationId, notificationBuilder.build())

    } catch (t: Throwable) {
        Log.e("FCMService", "Failed to show admin message notification", t)
    }
}
*/

echo "FCM service update instructions generated. Please update your Android FCMService.kt file with the code above.";
?>
