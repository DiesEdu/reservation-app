-- Migration: Add email column to sse_events table
-- For tracking which user performed the search

ALTER TABLE sse_events ADD COLUMN user_email VARCHAR(255) NULL AFTER search_query;
ALTER TABLE sse_events ADD INDEX idx_user_email (user_email);

SELECT 'Migration completed: Added user_email column to sse_events!' AS message;
