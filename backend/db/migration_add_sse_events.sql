-- Migration: Add SSE Events Table
-- For real-time guest search updates

CREATE TABLE IF NOT EXISTS sse_events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    search_query VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_search_query (search_query),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Clean up old events (older than 1 minute)
DELETE FROM sse_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE);

SELECT 'Migration completed successfully!' AS message;