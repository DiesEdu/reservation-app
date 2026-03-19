<?php
/**
 * Router script for PHP built-in server
 * This handles URL routing for the API
 */

// Get the request URI and remove query strings
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route to api files based on path
if (strpos($uri, '/api/auth') === 0) {
    // Route to auth API
    require __DIR__ . '/api_auth.php';
    return true;
} elseif (strpos($uri, '/api/user') === 0) {
    // Route to user profile API (wishlist, etc)
    require __DIR__ . '/api/user_profile.php';
    return true;
} elseif (strpos($uri, '/api/users') === 0) {
    // Route to user management API
    require __DIR__ . '/api/user.php';
    return true;
} elseif (strpos($uri, '/api/reservations') === 0 || strpos($uri, '/api') === 0) {
    // Route to reservations API
    require __DIR__ . '/api/reservation.php';
    return true;
}

// For all other requests, serve static files or show 404
return false;
