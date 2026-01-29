-- Create messaging tables for admin-faculty communication

-- Table for storing messages sent by admin to faculty
CREATE TABLE IF NOT EXISTS admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_email VARCHAR(255) NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('notification', 'alert', 'general') DEFAULT 'general',
    status ENUM('sent', 'delivered', 'read') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_email),
    INDEX idx_sender (sender_email),
    INDEX idx_created (created_at)
);

-- Table for storing FCM message delivery status
CREATE TABLE IF NOT EXISTS message_delivery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message_id INT NOT NULL,
    faculty_email VARCHAR(255) NOT NULL,
    fcm_token VARCHAR(500),
    delivery_status ENUM('pending', 'sent', 'delivered', 'failed') DEFAULT 'pending',
    error_message TEXT,
    sent_at TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES admin_messages(id) ON DELETE CASCADE,
    INDEX idx_message_faculty (message_id, faculty_email),
    INDEX idx_status (delivery_status)
);
