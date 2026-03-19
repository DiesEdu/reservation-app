<?php
/**
 * Users API
 * Handles user management operations (admin)
 */

// Set CORS headers to allow frontend access
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
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
if ($path === '/users' || $path === '/users/') {
    // GET all users or POST new user
    if ($method === 'GET') {
        getUsers();
    } elseif ($method === 'POST') {
        createUser();
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} elseif (preg_match('#^/users/(\d+)$#', $path, $matches)) {
    // Single user: GET, PUT, DELETE
    $id = (int) $matches[1];
    if ($method === 'GET') {
        getUser($id);
    } elseif ($method === 'PUT') {
        updateUser($id);
    } elseif ($method === 'DELETE') {
        deleteUser($id);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} elseif (preg_match('#^/users/(\d+)/status$#', $path, $matches) && $method === 'PATCH') {
    // Update user status (activate/deactivate)
    updateUserStatus((int) $matches[1]);
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
}

/**
 * GET all users (admin only)
 */
function getUsers()
{
    requireRole(['admin']);

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check for role filter
    $role = $_GET['role'] ?? null;
    $active = $_GET['active'] ?? null;

    try {
        $query = "SELECT id, email, name, role, is_active, email_verified, created_at, last_login FROM users";
        $conditions = [];
        $params = [];

        if ($role && in_array($role, ['admin', 'staff', 'customer'])) {
            $conditions[] = "role = ?";
            $params[] = $role;
        }

        if ($active !== null) {
            $conditions[] = "is_active = ?";
            $params[] = $active === 'true' ? 1 : 0;
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Format the data
        $formatted = array_map(function ($user) {
            return [
                'id' => (int) $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role'],
                'isActive' => (bool) $user['is_active'],
                'emailVerified' => (bool) $user['email_verified'],
                'createdAt' => $user['created_at'],
                'lastLogin' => $user['last_login']
            ];
        }, $users);

        echo json_encode([
            'success' => true,
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch users: ' . $e->getMessage()
        ]);
    }
}

/**
 * GET single user by ID
 */
function getUser($id)
{
    $auth = requireAuth();

    // Allow users to view their own profile, or admins to view any user
    if ($auth['user']['id'] !== $id && $auth['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient permissions to view this user'
        ]);
        return;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->prepare("
            SELECT id, email, name, role, is_active, email_verified, created_at, last_login 
            FROM users WHERE id = ?
        ");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'User not found'
            ]);
            return;
        }

        $formatted = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'isActive' => (bool) $user['is_active'],
            'emailVerified' => (bool) $user['email_verified'],
            'createdAt' => $user['created_at'],
            'lastLogin' => $user['last_login']
        ];

        echo json_encode([
            'success' => true,
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to fetch user: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Create new user (admin only)
 */
function createUser()
{
    requireRole(['admin']);

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $required = ['email', 'password', 'name'];
    $missing = [];

    foreach ($required as $field) {
        if (empty($input[$field])) {
            $missing[] = $field;
        }
    }

    if (!empty($missing)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        return;
    }

    // Sanitize and validate input
    $email = strtolower(sanitizeInput($input['email']));
    $name = sanitizeInput($input['name']);
    $password = $input['password'];
    $role = sanitizeInput($input['role'] ?? 'customer');

    // Validate email format
    if (!isValidEmail($email)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid email format'
        ]);
        return;
    }

    // Validate password strength
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Password must be at least 6 characters'
        ]);
        return;
    }

    // Validate role
    $allowedRoles = ['admin', 'staff', 'customer'];
    if (!in_array($role, $allowedRoles)) {
        $role = 'customer';
    }

    try {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'error' => 'Email already registered'
            ]);
            return;
        }

        // Hash password
        $hashedPassword = hashPassword($password);

        // Generate verification token (auto-verify if created by admin)
        $emailVerified = $input['auto_verify'] ?? false;
        $verificationToken = $emailVerified ? null : generateRandomToken();

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, name, role, email_verified, verification_token)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([$email, $hashedPassword, $name, $role, $emailVerified ? 1 : 0, $verificationToken]);

        $userId = (int) $pdo->lastInsertId();

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'User created successfully',
            'data' => [
                'id' => $userId,
                'email' => $email,
                'name' => $name,
                'role' => $role
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create user: ' . $e->getMessage()
        ]);
    }
}

/**
 * PUT - Update user
 */
function updateUser($id)
{
    $auth = requireAuth();

    // Allow users to update their own profile, or admins to update any user
    if ($auth['user']['id'] !== $id && $auth['user']['role'] !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient permissions to update this user'
        ]);
        return;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        return;
    }

    // Build update query
    $updates = [];
    $params = [];

    // Users can update their own name, admins can update name and role
    if (isset($input['name'])) {
        $updates[] = "name = ?";
        $params[] = sanitizeInput($input['name']);
    }

    // Only admin can change role
    if ($auth['user']['role'] === 'admin' && isset($input['role'])) {
        $role = sanitizeInput($input['role']);
        if (in_array($role, ['admin', 'staff', 'customer'])) {
            $updates[] = "role = ?";
            $params[] = $role;
        }
    }

    // User can change their own password
    if (isset($input['password'])) {
        if (strlen($input['password']) < 6) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Password must be at least 6 characters'
            ]);
            return;
        }
        $updates[] = "password = ?";
        $params[] = hashPassword($input['password']);
    }

    if (empty($updates)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'No valid fields to update'
        ]);
        return;
    }

    $params[] = $id;

    try {
        $stmt = $pdo->prepare("UPDATE users SET " . implode(", ", $updates) . " WHERE id = ?");
        $stmt->execute($params);

        // Fetch updated user
        $selectStmt = $pdo->prepare("
            SELECT id, email, name, role, is_active, email_verified, created_at, last_login 
            FROM users WHERE id = ?
        ");
        $selectStmt->execute([$id]);
        $user = $selectStmt->fetch();

        $formatted = [
            'id' => (int) $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'isActive' => (bool) $user['is_active'],
            'emailVerified' => (bool) $user['email_verified'],
            'createdAt' => $user['created_at'],
            'lastLogin' => $user['last_login']
        ];

        echo json_encode([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $formatted
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update user: ' . $e->getMessage()
        ]);
    }
}

/**
 * PATCH - Update user status (activate/deactivate)
 */
function updateUserStatus($id)
{
    requireRole(['admin']);

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['is_active'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing is_active field'
        ]);
        return;
    }

    $isActive = (bool) $input['is_active'];

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        return;
    }

    // Prevent admin from deactivating themselves
    $auth = requireAuth();
    if ($id === $auth['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot deactivate your own account'
        ]);
        return;
    }

    try {
        $updateStmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
        $updateStmt->execute([$isActive ? 1 : 0, $id]);

        // Delete all sessions for this user if deactivating
        if (!$isActive) {
            $deleteSessions = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
            $deleteSessions->execute([$id]);
        }

        echo json_encode([
            'success' => true,
            'message' => $isActive ? 'User activated successfully' : 'User deactivated successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to update user status: ' . $e->getMessage()
        ]);
    }
}

/**
 * DELETE - Delete user (admin only)
 */
function deleteUser($id)
{
    requireRole(['admin']);

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id, email FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        return;
    }

    // Get current admin user to prevent self-deletion
    $auth = requireAuth();
    if ($id === $auth['user']['id']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cannot delete your own account'
        ]);
        return;
    }

    try {
        // Delete user sessions first
        $deleteSessions = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $deleteSessions->execute([$id]);

        // Delete user
        $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to delete user: ' . $e->getMessage()
        ]);
    }
}
