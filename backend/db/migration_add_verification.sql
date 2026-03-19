-- Migration: Add Verification System
-- Run this if you have an existing database

-- Add verified fields to reservations table
ALTER TABLE reservations 
ADD COLUMN IF NOT EXISTS verified BOOLEAN NOT NULL DEFAULT FALSE AFTER qr_code,
ADD COLUMN IF NOT EXISTS verified_at DATETIME NULL AFTER verified,
ADD INDEX IF NOT EXISTS idx_verified (verified);

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

-- Generate QR codes for existing reservations (if not already present)
UPDATE reservations 
SET qr_code = CONCAT('RES-', id, '-', UNIX_TIMESTAMP(created_at))
WHERE qr_code IS NULL OR qr_code = '';

-- Verification complete
SELECT 'Migration completed successfully!' AS message;
