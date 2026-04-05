<?php
/**
 * Router script for PHP built-in server
 * This handles URL routing for the API
 */

// Get the request URI and remove query strings
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Latest search poll endpoint
if ($uri === '/api/get-latest-search') {
    require __DIR__ . '/api/get-latest-search.php';
    return true;
}

// Save search endpoint
if ($uri === '/api/save-search') {
    require __DIR__ . '/api/save-search.php';
    return true;
}

// Get users list endpoint
if ($uri === '/api/get-users') {
    require __DIR__ . '/api/get-users.php';
    return true;
}

// Route to api files based on path
if (strpos($uri, '/api/auth') === 0) {
    require __DIR__ . '/api_auth.php';
    return true;
} elseif (strpos($uri, '/api/user') === 0) {
    require __DIR__ . '/api/user_profile.php';
    return true;
} elseif (strpos($uri, '/api/users') === 0) {
    require __DIR__ . '/api/user.php';
    return true;
} elseif (strpos($uri, '/api/blast-info-') === 0) {
    require __DIR__ . '/api/blast_information.php';
    return true;
} elseif (strpos($uri, '/api/reservations') === 0 || $uri === '/api') {
    require __DIR__ . '/api/reservation.php';
    return true;
}

// For all other requests, serve static files or show 404
return false;