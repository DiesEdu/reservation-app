ALTER TABLE reservations ADD COLUMN generate_ticket DATETIME NULL AFTER qr_code;
-----------------------------------------------------------------------------------
ALTER TABLE reservations ADD COLUMN table_color ENUM('Purple', 'Cyan', 'Orange', 'Cream', 'White') NOT NULL DEFAULT 'White' AFTER table_preference;
-----------------------------------------------------------------------------------
ALTER TABLE reservations ADD COLUMN sales_connection VARCHAR(255) AFTER company;
ALTER TABLE reservations DROP COLUMN email;
ALTER TABLE reservations DROP COLUMN phone;
ALTER TABLE reservations DROP COLUMN guests;