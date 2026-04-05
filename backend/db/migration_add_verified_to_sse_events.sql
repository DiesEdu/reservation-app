-- Migration: Add verified column to sse_events table
-- For tracking verification status of search events

ALTER TABLE sse_events ADD COLUMN verified BOOLEAN NOT NULL DEFAULT FALSE AFTER user_email;
ALTER TABLE sse_events ADD INDEX idx_verified (verified);

SELECT 'Migration completed: Added verified column to sse_events!' AS message;