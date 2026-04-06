<?php
/**
 * Server-Sent Events endpoint for real-time reservation search
 * Streams events when admin searches for a guest
 */

// Disable output buffering for streaming
while (ob_get_level()) {
    ob_end_clean();
}

// Set headers first
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

// Prevent PHP from timing out
set_time_limit(0);
ignore_user_abort(false);

// Clean any existing output
ob_flush();
flush();

function sendEvent($event, $data) {
    echo "event: {$event}\n";
    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();
}

function getReservationsBySearch($pdo, $search) {
    if (empty($search)) {
        return [];
    }
    
    $pattern = '%' . $search . '%';
    $stmt = $pdo->prepare("
        SELECT id, name, company, position, sales_connection, seat_code, status, verified
        FROM reservations 
        WHERE name LIKE ? OR company LIKE ?
        ORDER BY name ASC
        LIMIT 20
    ");
    $stmt->execute([$pattern, $pattern]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

try {
    require_once __DIR__ . '/../db.php';
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    // Send initial connection event
    sendEvent('connected', ['status' => 'ok', 'timestamp' => time()]);
    
} catch (PDOException $e) {
    sendEvent('error', ['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

$lastSearch = '';
$checkInterval = 1;

while (true) {
    if (connection_aborted()) {
        break;
    }
    
    try {
        // Clean up old events periodically
        if (time() % 60 === 0) {
            $cleanupStmt = $pdo->prepare("DELETE FROM sse_events WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)");
            $cleanupStmt->execute();
        }
        
        $stmt = $pdo->query("SELECT search_query FROM sse_events ORDER BY id DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $currentSearch = $row['search_query'] ?? '';
        
        if ($currentSearch !== $lastSearch && !empty($currentSearch)) {
            $lastSearch = $currentSearch;
            
            $reservations = getReservationsBySearch($pdo, $currentSearch);
            
            sendEvent('search-results', [
                'search' => $currentSearch,
                'results' => $reservations,
                'count' => count($reservations),
                'timestamp' => time()
            ]);
        }
        
    } catch (PDOException $e) {
        sendEvent('error', ['message' => 'Database error: ' . $e->getMessage()]);
    }
    
    sleep($checkInterval);
}