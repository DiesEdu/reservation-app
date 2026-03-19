<?php
/**
 * User Profile API
 * Handles user-specific operations like wishlist
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
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

// Route handling
if ($path === '/user/wishlist' || $path === '/user/wishlist/') {
    // GET wishlist or POST new wishlist item
    if ($method === 'GET') {
        getWishlist();
    } elseif ($method === 'POST') {
        addToWishlist();
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} elseif (preg_match('#^/user/wishlist/(\d+)$#', $path, $matches)) {
    // DELETE wishlist item
    $id = (int) $matches[1];
    if ($method === 'DELETE') {
        removeFromWishlist($id);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
}

/**
 * GET - Get user's wishlist
 */
function getWishlist()
{
    $auth = requireAuth();

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Check if wishlist table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'wishlist'");
        if ($tableCheck->rowCount() === 0) {
            // Table doesn't exist, return empty array
            echo json_encode([
                'success' => true,
                'data' => []
            ]);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT id, user_id, restaurant_id, name, description, created_at
            FROM wishlist 
            WHERE user_id = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$auth['user']['id']]);
        $wishlist = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'data' => $wishlist
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch wishlist: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Add item to wishlist
 */
function addToWishlist()
{
    $auth = requireAuth();

    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['restaurant_id']) || empty($input['name'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Restaurant ID and name are required'
        ]);
        return;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Check if wishlist table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'wishlist'");
        if ($tableCheck->rowCount() === 0) {
            // Create wishlist table if it doesn't exist
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS wishlist (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    restaurant_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    UNIQUE KEY unique_user_restaurant (user_id, restaurant_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
        }

        // Check if already in wishlist
        $checkStmt = $pdo->prepare("
            SELECT id FROM wishlist 
            WHERE user_id = ? AND restaurant_id = ?
        ");
        $checkStmt->execute([$auth['user']['id'], $input['restaurant_id']]);

        if ($checkStmt->fetch()) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'Item already in wishlist'
            ]);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO wishlist (user_id, restaurant_id, name, description)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $auth['user']['id'],
            $input['restaurant_id'],
            $input['name'],
            $input['description'] ?? null
        ]);

        $id = (int) $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Added to wishlist',
            'data' => [
                'id' => $id,
                'restaurant_id' => $input['restaurant_id'],
                'name' => $input['name'],
                'description' => $input['description'] ?? null
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to add to wishlist: ' . $e->getMessage()
        ]);
    }
}

/**
 * DELETE - Remove item from wishlist
 */
function removeFromWishlist($id)
{
    $auth = requireAuth();

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Check if wishlist table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'wishlist'");
        if ($tableCheck->rowCount() === 0) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Wishlist not found'
            ]);
            return;
        }

        // Check if item belongs to user
        $checkStmt = $pdo->prepare("SELECT id FROM wishlist WHERE id = ? AND user_id = ?");
        $checkStmt->execute([$id, $auth['user']['id']]);

        if (!$checkStmt->fetch()) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Wishlist item not found'
            ]);
            return;
        }

        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $auth['user']['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Removed from wishlist'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to remove from wishlist: ' . $e->getMessage()
        ]);
    }
}
