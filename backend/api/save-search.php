<?php
/**
 * API: Save search query for SSE
 * This endpoint receives search queries and stores them for SSE to pick up
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';

$input = json_decode(file_get_contents('php://input'), true);
$searchQuery = isset($input['search']) ? trim($input['search']) : '';
$userEmail = isset($input['email']) ? trim($input['email']) : '';

if (empty($searchQuery)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Search query is required'
    ]);
    return;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $stmt = $pdo->prepare("INSERT INTO sse_events (search_query, user_email) VALUES (?, ?)");
    $stmt->execute([$searchQuery, $userEmail]);

    echo json_encode([
        'success' => true,
        'message' => 'Search query saved',
        'search' => $searchQuery,
        'email' => $userEmail
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save search query: ' . $e->getMessage()
    ]);
}