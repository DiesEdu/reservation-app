ALTER TABLE reservations ADD COLUMN generate_ticket DATETIME NULL AFTER qr_code;
-----------------------------------------------------------------------------------
ALTER TABLE reservations ADD COLUMN table_color ENUM('Purple', 'Cyan', 'Orange', 'Cream', 'White') NOT NULL DEFAULT 'White' AFTER table_preference;
-----------------------------------------------------------------------------------
ALTER TABLE reservations ADD COLUMN sales_connection VARCHAR(255) AFTER company;
ALTER TABLE reservations DROP COLUMN email;
ALTER TABLE reservations DROP COLUMN phone;
ALTER TABLE reservations DROP COLUMN guests;
ALTER TABLE reservations DROP COLUMN special_requests;
ALTER TABLE reservations CHANGE COLUMN table_preference seat_code VARCHAR(100) NOT NULL DEFAULT 'Z99';
ALTER TABLE sse_events ADD COLUMN user_email VARCHAR(255) AFTER search_query;
ALTER TABLE sse_events ADD COLUMN verified BOOLEAN;
------------------------------------------------------------------------------------
ALTER TABLE reservations MODIFY table_color VARCHAR(20) NOT NULL DEFAULT '#ffffff';
ALTER TABLE reservations ADD COLUMN awardee ENUM('AWARD', 'NON-AWARD') NOT NULL DEFAULT 'NON-AWARD' AFTER company;
-------------------------------------------------------------------------------------