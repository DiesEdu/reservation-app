<?php

// Configure allowed origins - only allow specific domains
$allowedOrigins = ['http://localhost:5173', 'http://localhost:8000'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/auth.php';

// Get the request method and parse the URL
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

if ($path === '/blast-info-email' || $path === '/blast-info-email/') {
    if ($method === 'GET') {
        getBlastInfo();
    } elseif ($method === 'POST') {
        // Validate content type for POST requests
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') === false) {
            http_response_code(415);
            echo json_encode(['success' => false, 'error' => 'Content-Type must be application/json']);
            return;
        }
        createBlastInfo();
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
}

function getBlastInfo()
{
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql = "SELECT * FROM blast_information ORDER BY created_at DESC";
    $result = $conn->query($sql);

    $blastInfo = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $blastInfo[] = $row;
        }
    }

    echo json_encode(['success' => true, 'data' => $blastInfo]);
}

function createBlastInfo()
{
    $auth = requireAuth();

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Get user verification data
        $stmt = $pdo->prepare("
            SELECT id, email, name, email_verified, verification_token
            FROM users WHERE id = ?
        ");
        $stmt->execute([$auth['user']['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
            return;
        }

        // Send verification email
        $sent = sendInformationEmail($user['email'], $user['name']);

        if (!$sent) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Unable to send email'
            ]);
            return;
        }

        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Email sent successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'An error occurred while processing your request'
        ]);
        // Log the actual error for debugging (don't expose to user)
        error_log('createBlastInfo error: ' . $e->getMessage());
    }
}

function sendInformationEmail($email, $username)
{
    $subject = "Premiere Dinner Ticket - {$username}";

    $message = "
    Hello {$username},
    I just try to say hello :D
    ";

    $headers = "From: admin@reserve.resonanz.id";
    return mail($email, $subject, $message, $headers);
}

?>