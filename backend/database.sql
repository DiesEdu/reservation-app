-- Database Schema for Reservation App
-- Run this SQL script to create the database and tables

-- Create database
CREATE DATABASE IF NOT EXISTS reservation_app;
USE reservation_app;

-- Create reservations table
CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    time TIME NOT NULL,
    guests INT NOT NULL DEFAULT 2,
    table_preference VARCHAR(100) NOT NULL DEFAULT 'Window Table',
    status ENUM('pending', 'confirmed', 'cancelled') NOT NULL DEFAULT 'pending',
    special_requests TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_date (date),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample data
INSERT INTO reservations (name, email, phone, date, time, guests, table_preference, status, special_requests, created_at) VALUES
('Sarah Johnson', 'sarah.j@email.com', '+1 (555) 123-4567', '2026-03-20', '19:00:00', 4, 'Window Table', 'confirmed', 'Anniversary dinner, please prepare a small cake', '2026-03-15 10:00:00'),
('Michael Chen', 'mchen@email.com', '+1 (555) 987-6543', '2026-03-21', '20:30:00', 2, 'VIP Booth', 'pending', 'Vegetarian menu options', '2026-03-15 11:30:00'),
('Emma Williams', 'emma.w@email.com', '+1 (555) 456-7890', '2026-03-22', '18:00:00', 6, 'Private Room A', 'confirmed', 'Birthday celebration, need high chair for toddler', '2026-03-14 09:00:00'),
('James Rodriguez', 'j.rodriguez@email.com', '+1 (555) 234-5678', '2026-03-19', '19:30:00', 3, 'Window Table', 'cancelled', '', '2026-03-14 14:00:00'),
('Lisa Thompson', 'lisa.t@email.com', '+1 (555) 876-5432', '2026-03-23', '21:00:00', 2, 'Champagne Bar', 'confirmed', 'Wine pairing menu preferred', '2026-03-13 16:00:00');
