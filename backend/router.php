<?php
/**
 * Router script for PHP built-in server
 * This handles URL routing for the API
 */

// Get the request URI and remove query strings
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Route to api.php for /api routes
if (strpos($uri, '/api/auth') === 0) {
    // Route to auth API
    require __DIR__ . '/api_auth.php';
    return true;
} elseif (strpos($uri, '/api') === 0) {
    // Remove /api prefix and route to api.php
    require __DIR__ . '/api.php';
    return true;
}

// For all other requests, serve static files or show 404
return false;
