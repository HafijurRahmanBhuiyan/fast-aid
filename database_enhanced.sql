-- FastAid v2 - Enhanced Database Schema
-- Add notifications, locations, and enhanced features

USE fastaid_db;

-- Add notification preferences table
CREATE TABLE IF NOT EXISTS notification_preferences (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_type ENUM('patient', 'volunteer') NOT NULL,
    email_notifications TINYINT(1) DEFAULT 1,
    sms_notifications TINYINT(1) DEFAULT 1,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Add location tracking to service requests
ALTER TABLE service_requests 
ADD COLUMN patient_lat DECIMAL(10, 8) DEFAULT NULL,
ADD COLUMN patient_lng DECIMAL(11, 8) DEFAULT NULL,
ADD COLUMN volunteer_lat DECIMAL(10, 8) DEFAULT NULL,
ADD COLUMN volunteer_lng DECIMAL(11, 8) DEFAULT NULL,
ADD COLUMN accepted_at TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL;

-- Add activity log table for security
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Add password reset tokens table
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- Insert sample notification preferences for existing users (optional)
-- This will be handled by the application when users update their profiles
