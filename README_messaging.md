# Admin Messaging System

A complete messaging system for administrators to communicate with faculty members via FCM notifications and web interface.

## Features

### Admin Features
- ✅ **Send Messages** - Send notifications to specific faculty members
- ✅ **Message Types** - General, Notification, Alert categories
- ✅ **Delivery Tracking** - Real-time FCM delivery status
- ✅ **Message Management** - Edit, delete sent messages
- ✅ **Faculty Directory** - Auto-populated faculty list with FCM token status
- ✅ **Pagination** - Handle large message lists efficiently
- ✅ **Search & Filter** - Filter by delivery status

### Faculty Features
- ✅ **FCM Notifications** - Receive instant notifications on mobile
- ✅ **Message Viewing** - View full message details in web browser
- ✅ **Read Status** - Automatic read receipt tracking

## Installation

### 1. Database Setup
```sql
-- Run the SQL file to create required tables
SOURCE create_messaging_tables.sql;
```

### 2. File Structure
```
reminder/
├── admin_messaging.php      # Backend API
├── admin_messaging.html      # Admin interface
├── admin_messaging.js        # Frontend JavaScript
├── view_message.php         # Message viewing page
├── create_messaging_tables.sql  # Database schema
├── update_fcm_service.php  # Android update instructions
└── README_messaging.md      # This file
```

### 3. Android Integration
Update your `FCMService.kt` with the code from `update_fcm_service.php`:

1. Add admin message handling to `onMessageReceived()`
2. Add `showAdminMessageNotification()` function
3. Add notification channel for admin messages

## Usage

### Admin Interface
1. Access `admin_messaging.html` in your browser
2. Login as administrator
3. Use the interface to:
   - Send messages to faculty
   - View sent messages
   - Edit/delete messages
   - Check delivery status

### API Endpoints

#### GET/POST `admin_messaging.php`

**Actions:**
- `get_faculty_list` - Get all faculty with FCM tokens
- `send_message` - Send new message (POST)
- `get_messages` - Get paginated messages
- `edit_message` - Update existing message (POST)
- `delete_message` - Delete message (POST)
- `get_delivery_status` - Get delivery details

**Example Send Message:**
```javascript
fetch('admin_messaging.php?action=send_message', {
    method: 'POST',
    body: formData
})
```

## Database Schema

### admin_messages
- `id` - Primary key
- `sender_email` - Admin email
- `recipient_email` - Faculty email
- `subject` - Message subject
- `message` - Message content
- `message_type` - Type (general/notification/alert)
- `status` - Message status
- `created_at` - Creation timestamp
- `updated_at` - Last update timestamp

### message_delivery
- `id` - Primary key
- `message_id` - Foreign key to admin_messages
- `faculty_email` - Recipient email
- `fcm_token` - FCM token used
- `delivery_status` - Delivery status
- `error_message` - Error details if failed
- `sent_at` - Send timestamp
- `delivered_at` - Delivery timestamp

## Security Features

- ✅ **Session Authentication** - Admin login required
- ✅ **Input Validation** - All inputs sanitized
- ✅ **SQL Injection Protection** - Prepared statements
- ✅ **XSS Protection** - HTML escaping
- ✅ **CSRF Protection** - Form tokens recommended

## Configuration

### Database Connection
Ensure your `config/database.php` is properly configured with:
- Database credentials
- PDO connection
- Error handling

### Firebase Configuration
Ensure your `config/firebase.php` includes:
- FCM service account
- Messaging instance
- Token management

## Troubleshooting

### Common Issues

1. **FCM Not Sending**
   - Check FCM token exists in fcm_tokens table
   - Verify Firebase configuration
   - Check network connectivity

2. **Messages Not Showing**
   - Verify database tables exist
   - Check admin session
   - Review browser console for errors

3. **Delivery Status Issues**
   - Check message_delivery table
   - Verify FCM response handling
   - Review error logs

## Future Enhancements

- [ ] Message templates
- [ ] Bulk messaging
- [ ] Message scheduling
- [ ] File attachments
- [ ] Message threading
- [ ] Email fallback
- [ ] Analytics dashboard

## Support

For issues or questions:
1. Check browser console for JavaScript errors
2. Review PHP error logs
3. Verify database connectivity
4. Test FCM configuration separately
