<?php
/**
 * API: Get latest search results for polling
 * Returns search query and matching reservations
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';

$db = Database::getInstance();
$pdo = $db->getConnection();

// Get optional email filter from query parameter
$emailFilter = isset($_GET['email']) ? trim($_GET['email']) : '';

try {
    // Build query based on whether email filter is provided
    if (!empty($emailFilter)) {
        $stmt = $pdo->prepare("SELECT search_query, user_email FROM sse_events WHERE user_email = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$emailFilter]);
    } else {
        $stmt = $pdo->query("SELECT search_query, user_email FROM sse_events ORDER BY id DESC LIMIT 1");
    }
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $searchQuery = $row['search_query'] ?? '';
    $userEmail = $row['user_email'] ?? '';
    
    if (empty($searchQuery)) {
        echo json_encode([
            'success' => true,
            'search' => '',
            'results' => []
        ]);
        return;
    }
    
    // Search for matching reservations
    $pattern = '%' . $searchQuery . '%';
    $searchStmt = $pdo->prepare("
        SELECT id, name, company, position, email, phone, table_preference, status, verified
        FROM reservations 
        WHERE name LIKE ? OR email LIKE ? OR company LIKE ?
        ORDER BY name ASC
        LIMIT 20
    ");
    $searchStmt->execute([$pattern, $pattern, $pattern]);
    $reservations = $searchStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'search' => $searchQuery,
        'user_email' => $userEmail,
        'results' => $reservations,
        'count' => count($reservations)
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}