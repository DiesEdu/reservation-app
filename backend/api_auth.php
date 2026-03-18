<?php
/**
 * Authentication API
 * Handles user registration, login, logout, and token refresh
 */

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/middleware/auth.php';

// Get the request method and parse the URL
$method = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove /api prefix if present
$path = str_replace('/api', '', $path);

// Route handling
if ($path === '/auth/register' && $method === 'POST') {
    registerUser();
} elseif ($path === '/auth/login' && $method === 'POST') {
    loginUser();
} elseif ($path === '/auth/logout' && $method === 'POST') {
    logoutUser();
} elseif ($path === '/auth/refresh' && $method === 'POST') {
    refreshToken();
} elseif ($path === '/auth/me' && $method === 'GET') {
    getCurrentUser();
} elseif ($path === '/auth/verify' && $method === 'POST') {
    verifyEmail();
} elseif ($path === '/auth/forgot-password' && $method === 'POST') {
    forgotPassword();
} elseif ($path === '/auth/reset-password' && $method === 'POST') {
    resetPassword();
} else {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Endpoint not found']);
}

/**
 * POST - Register new user
 */
function registerUser()
{
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

        // Generate verification token
        $verificationToken = generateRandomToken();

        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (email, password, name, role, verification_token)
            VALUES (?, ?, ?, ?, ?)
        ");

        $stmt->execute([$email, $hashedPassword, $name, $role, $verificationToken]);

        $userId = (int) $pdo->lastInsertId();

        // Generate tokens
        $accessToken = generateToken($userId, $email, $role, 'access');
        $refreshToken = generateToken($userId, $email, $role, 'refresh');

        // Store session
        storeSession($pdo, $userId, $refreshToken, 'refresh');

        http_response_code(201);
        echo json_encode([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $userId,
                    'email' => $email,
                    'name' => $name,
                    'role' => $role
                ],
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Registration failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Login user
 */
function loginUser()
{
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    $input = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    if (empty($input['email']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email and password are required'
        ]);
        return;
    }

    $email = strtolower(sanitizeInput($input['email']));
    $password = $input['password'];

    try {
        // Get user by email
        $stmt = $pdo->prepare("
            SELECT id, email, password, name, role, is_active, email_verified
            FROM users WHERE email = ?
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid email or password'
            ]);
            return;
        }

        // Check if user is active
        if (!$user['is_active']) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Account is deactivated. Please contact support.'
            ]);
            return;
        }

        // Verify password
        if (!verifyPassword($password, $user['password'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid email or password'
            ]);
            return;
        }

        // Generate tokens
        $accessToken = generateToken($user['id'], $user['email'], $user['role'], 'access');
        $refreshToken = generateToken($user['id'], $user['email'], $user['role'], 'refresh');

        // Store session
        storeSession($pdo, $user['id'], $refreshToken, 'refresh');

        // Update last login
        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateStmt->execute([$user['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => (int) $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role'],
                    'emailVerified' => (bool) $user['email_verified']
                ],
                'accessToken' => $accessToken,
                'refreshToken' => $refreshToken
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Login failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Logout user
 */
function logoutUser()
{
    $token = getBearerToken();

    if (!$token) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'No token provided'
        ]);
        return;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Delete session from database
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE token = ?");
        $stmt->execute([$token]);

        echo json_encode([
            'success' => true,
            'message' => 'Logout successful'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Logout failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Refresh token
 */
function refreshToken()
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['refreshToken'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Refresh token is required'
        ]);
        return;
    }

    $result = validateToken($input['refreshToken']);

    if (!$result['valid'] || $result['data']['type'] !== 'refresh') {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid or expired refresh token'
        ]);
        return;
    }

    $userId = $result['data']['userId'];

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Get user
        $stmt = $pdo->prepare("
            SELECT id, email, name, role, is_active
            FROM users WHERE id = ? AND is_active = TRUE
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'error' => 'User not found or inactive'
            ]);
            return;
        }

        // Generate new tokens
        $newAccessToken = generateToken($user['id'], $user['email'], $user['role'], 'access');
        $newRefreshToken = generateToken($user['id'], $user['email'], $user['role'], 'refresh');

        // Delete old refresh token session
        $deleteStmt = $pdo->prepare("DELETE FROM user_sessions WHERE token = ?");
        $deleteStmt->execute([$input['refreshToken']]);

        // Store new refresh token
        storeSession($pdo, $user['id'], $newRefreshToken, 'refresh');

        echo json_encode([
            'success' => true,
            'data' => [
                'accessToken' => $newAccessToken,
                'refreshToken' => $newRefreshToken
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Token refresh failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * GET - Get current user
 */
function getCurrentUser()
{
    $auth = requireAuth();

    echo json_encode([
        'success' => true,
        'data' => [
            'user' => [
                'id' => (int) $auth['user']['id'],
                'email' => $auth['user']['email'],
                'name' => $auth['user']['name'],
                'role' => $auth['user']['role'],
                'emailVerified' => (bool) $auth['user']['email_verified']
            ]
        ]
    ]);
}

/**
 * POST - Verify email
 */
function verifyEmail()
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['token'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Verification token is required'
        ]);
        return;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET email_verified = TRUE, verification_token = NULL
            WHERE verification_token = ?
        ");
        $stmt->execute([$input['token']]);

        if ($stmt->rowCount() === 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid or expired verification token'
            ]);
            return;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Email verified successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Verification failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Forgot password
 */
function forgotPassword()
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['email'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Email is required'
        ]);
        return;
    }

    $email = strtolower(sanitizeInput($input['email']));

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->fetch()) {
            // Generate reset token
            $resetToken = generateRandomToken();
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store reset token
            $updateStmt = $pdo->prepare("
                UPDATE users SET reset_token = ?, reset_token_expires = ?
                WHERE email = ?
            ");
            $updateStmt->execute([$resetToken, $expiresAt, $email]);

            // In production, send email with reset link
            // For now, return the token (for testing purposes)
            echo json_encode([
                'success' => true,
                'message' => 'Password reset instructions sent to your email',
                'debug_token' => $resetToken // Remove in production
            ]);
        } else {
            // Return same message to prevent email enumeration
            echo json_encode([
                'success' => true,
                'message' => 'If the email exists, password reset instructions will be sent'
            ]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to process request: ' . $e->getMessage()
        ]);
    }
}

/**
 * POST - Reset password
 */
function resetPassword()
{
    $input = json_decode(file_get_contents('php://input'), true);

    if (empty($input['token']) || empty($input['password'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Token and new password are required'
        ]);
        return;
    }

    if (strlen($input['password']) < 6) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Password must be at least 6 characters'
        ]);
        return;
    }

    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        // Find user with valid reset token
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE reset_token = ? AND reset_token_expires > NOW()
        ");
        $stmt->execute([$input['token']]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid or expired reset token'
            ]);
            return;
        }

        // Hash new password
        $hashedPassword = hashPassword($input['password']);

        // Update password and clear reset token
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expires = NULL
            WHERE id = ?
        ");
        $updateStmt->execute([$hashedPassword, $user['id']]);

        // Delete all user sessions (force logout)
        $deleteSessions = $pdo->prepare("DELETE FROM user_sessions WHERE user_id = ?");
        $deleteSessions->execute([$user['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Password reset successful'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Password reset failed: ' . $e->getMessage()
        ]);
    }
}

/**
 * Store session in database
 */
function storeSession($pdo, $userId, $token, $type)
{
    $expiresAt = date('Y-m-d H:i:s', strtotime('+' . ($type === 'refresh' ? '7 days' : '1 hour')));
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    $stmt = $pdo->prepare("
        INSERT INTO user_sessions (user_id, token, token_type, expires_at, ip_address, user_agent)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([$userId, $token, $type, $expiresAt, $ipAddress, $userAgent]);
}
