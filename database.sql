-- FastAid Database Creation Script
-- Emergency Medical Assistance System

-- Create database
CREATE DATABASE IF NOT EXISTS fastaid_db;
USE fastaid_db;

-- Drop tables if exists (for clean setup)
DROP TABLE IF EXISTS service_requests;
DROP TABLE IF EXISTS patients;
DROP TABLE IF EXISTS volunteers;
DROP TABLE IF EXISTS admins;

-- Admins table
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Volunteers table
CREATE TABLE volunteers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    location VARCHAR(255) NOT NULL,
    degree VARCHAR(100) NOT NULL,
    certificate_file VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Patients table
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    age INT NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    location VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Service requests table
CREATE TABLE service_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    volunteer_id INT DEFAULT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('pending', 'accepted', 'completed', 'cancelled') DEFAULT 'pending',
    request_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (volunteer_id) REFERENCES volunteers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert fixed admin (password: admin123 - hashed using bcrypt)
INSERT INTO admins (email, password) VALUES 
('admin@fastaid.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
