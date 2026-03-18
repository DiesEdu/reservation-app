<?php
/**
 * Authentication Middleware
 * Handles JWT token validation and user authentication
 */

require_once __DIR__ . '/../db.php';

// JWT Configuration
define('JWT_SECRET', getenv('JWT_SECRET') ?: 'your-super-secret-key-change-in-production');
define('JWT_EXPIRY', 3600); // 1 hour in seconds
define('JWT_REFRESH_EXPIRY', 604800); // 7 days in seconds

/**
 * Generate JWT Token
 */
function generateToken($userId, $email, $role, $type = 'access')
{
    $expiry = $type === 'refresh' ? JWT_REFRESH_EXPIRY : JWT_EXPIRY;

    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode([
        'userId' => $userId,
        'email' => $email,
        'role' => $role,
        'type' => $type,
        'iat' => time(),
        'exp' => time() + $expiry
    ]));

    $signature = hash_hmac('sha256', "$header.$payload", JWT_SECRET);

    return "$header.$payload.$signature";
}

/**
 * Validate JWT Token
 */
function validateToken($token)
{
    $parts = explode('.', $token);

    if (count($parts) !== 3) {
        return ['valid' => false, 'error' => 'Invalid token format'];
    }

    [$header, $payload, $signature] = $parts;

    // Verify signature
    $expectedSignature = hash_hmac('sha256', "$header.$payload", JWT_SECRET);

    if ($signature !== $expectedSignature) {
        return ['valid' => false, 'error' => 'Invalid token signature'];
    }

    // Decode payload
    $payloadData = json_decode(base64_decode($payload), true);

    if (!$payloadData) {
        return ['valid' => false, 'error' => 'Invalid token payload'];
    }

    // Check expiration
    if ($payloadData['exp'] < time()) {
        return ['valid' => false, 'error' => 'Token has expired'];
    }

    return [
        'valid' => true,
        'data' => [
            'userId' => $payloadData['userId'],
            'email' => $payloadData['email'],
            'role' => $payloadData['role'],
            'type' => $payloadData['type']
        ]
    ];
}

/**
 * Get Bearer Token from Header
 */
function getBearerToken()
{
    $headers = getallheaders();

    if (isset($headers['Authorization'])) {
        if (preg_match('/Bearer\s+(.+)$/i', $headers['Authorization'], $matches)) {
            return $matches[1];
        }
    }

    return null;
}

/**
 * Authenticate User Middleware
 * Returns user data if authenticated, null otherwise
 */
function authenticate()
{
    $token = getBearerToken();

    if (!$token) {
        return ['authenticated' => false, 'error' => 'No token provided'];
    }

    $result = validateToken($token);

    if (!$result['valid']) {
        return ['authenticated' => false, 'error' => $result['error']];
    }

    // Check if token type is access token
    if ($result['data']['type'] !== 'access') {
        return ['authenticated' => false, 'error' => 'Invalid token type'];
    }

    // Get user from database
    $db = Database::getInstance();
    $pdo = $db->getConnection();

    try {
        $stmt = $pdo->prepare("
            SELECT id, email, name, role, is_active, email_verified 
            FROM users 
            WHERE id = ? AND is_active = TRUE
        ");
        $stmt->execute([$result['data']['userId']]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['authenticated' => false, 'error' => 'User not found or inactive'];
        }

        return [
            'authenticated' => true,
            'user' => $user,
            'tokenData' => $result['data']
        ];
    } catch (PDOException $e) {
        return ['authenticated' => false, 'error' => 'Database error: ' . $e->getMessage()];
    }
}

/**
 * Require Authentication - Exit if not authenticated
 */
function requireAuth()
{
    $auth = authenticate();

    if (!$auth['authenticated']) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => $auth['error']
        ]);
        exit;
    }

    return $auth;
}

/**
 * Check if user has required role
 */
function requireRole($allowedRoles)
{
    $auth = requireAuth();

    if (!in_array($auth['user']['role'], $allowedRoles)) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'error' => 'Insufficient permissions'
        ]);
        exit;
    }

    return $auth;
}

/**
 * Hash Password using bcrypt
 */
function hashPassword($password)
{
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify Password
 */
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

/**
 * Generate Random Token
 */
function generateRandomToken($length = 32)
{
    return bin2hex(random_bytes($length / 2));
}

/**
 * Sanitize Input
 */
function sanitizeInput($input)
{
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate Email
 */
function isValidEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
