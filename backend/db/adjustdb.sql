ALTER TABLE reservations ADD COLUMN generate_ticket DATETIME NULL AFTER qr_code;
-----------------------------------------------------------------------------------
ALTER TABLE reservations ADD COLUMN table_color ENUM('Purple', 'Cyan', 'Orange', 'Cream', 'White') NOT NULL DEFAULT 'White' AFTER table_preference;