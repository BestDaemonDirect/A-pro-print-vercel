<?php
// Router for PHP's built-in server to serve static files directly
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$requested = __DIR__ . $uri;

// If the request is for an existing file (static), let the server handle it
if ($uri !== '/' && file_exists($requested) && is_file($requested)) {
    return false;
}

// Otherwise, route to index.php
require_once __DIR__ . '/index.php';
