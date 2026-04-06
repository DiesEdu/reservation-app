-- Database Schema for Reservation App
-- Run this SQL script to create the database and tables

-- Create database
CREATE DATABASE IF NOT EXISTS reservation_app;
USE reservation_app;

-- Create reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255),
    company VARCHAR(255),
    sales_connection VARCHAR(255),
    date DATE NOT NULL,
    time TIME NOT NULL,
    seat_code VARCHAR(100) NOT NULL DEFAULT 'Window Table',
    table_color ENUM('Purple', 'Cyan', 'Orange', 'Cream', 'White') NOT NULL DEFAULT 'White',
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    qr_code VARCHAR(255),
    generate_ticket DATETIME NULL,
    send_email DATETIME NULL,
    send_whatsapp DATETIME NULL,
    verified BOOLEAN NOT NULL DEFAULT FALSE,
    verified_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_date (date),
    INDEX idx_qr_code (qr_code),
    INDEX idx_verified (verified)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create reservation_verifications table
CREATE TABLE IF NOT EXISTS reservation_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id INT NOT NULL,
    qr_code VARCHAR(255) NOT NULL,
    verified_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    verification_method ENUM('qr_scan', 'manual_entry') NOT NULL DEFAULT 'qr_scan',
    ip_address VARCHAR(45),
    user_agent TEXT,
    FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE,
    INDEX idx_reservation_id (reservation_id),
    INDEX idx_qr_code (qr_code),
    INDEX idx_verified_at (verified_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO reservations (name, position, company, sales_connection, date, time, seat_code, status, created_at) VALUES
('Sarah Johnson', 'Chief Executive Officer', 'Bank Central Asia', 'sales1@resonanz.id', '2026-03-20', '19:00:00', 'Window Table', 'confirmed', '2026-03-15 10:00:00'),
('Michael Chen', 'Business Development Manager', 'Technology Company', 'sales2@resonanz.id', '2026-03-21', '20:30:00', 'VIP Booth', 'pending', '2026-03-15 11:30:00'),
('Emma Williams', 'Manager', 'PT. Personal', 'sales1@resonanz.id', '2026-03-22', '18:00:00', 'Private Room A', 'confirmed', '2026-03-14 09:00:00'),
('James Rodriguez', 'Engineer', 'PT Batu Bara', 'sales3@resonanz.id', '2026-03-19', '19:30:00', 'Window Table', 'cancelled', '2026-03-14 14:00:00'),
('Lisa Thompson', 'Management Trainee', 'Company XYZ', 'sales2@resonanz.id', '2026-03-23', '21:00:00', 'Champagne Bar', 'confirmed', '2026-03-13 16:00:00');

-- Update sample reservations with QR codes
UPDATE reservations SET qr_code = CONCAT('RES-', id, '-', UNIX_TIMESTAMP(created_at)) WHERE qr_code IS NULL;