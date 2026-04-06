<?php
/**
 * API: Clear sse_events by user_email
 * This endpoint deletes sse_events for a specific user email
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
$userEmail = isset($_GET['email']) ? trim($_GET['email']) : '';

if (empty($userEmail)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'User email is required'
    ]);
    return;
}

$db = Database::getInstance();
$pdo = $db->getConnection();

try {
    $stmt = $pdo->prepare("DELETE FROM sse_events WHERE user_email = ?");
    $stmt->execute([$userEmail]);

    $deletedCount = $stmt->rowCount();

    echo json_encode([
        'success' => true,
        'message' => 'SSE events cleared',
        'deleted_count' => $deletedCount
    ]);
} catch (PDOException $e) {
    error_log("SSE clear error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to clear SSE events: ' . $e->getMessage()
    ]);
}