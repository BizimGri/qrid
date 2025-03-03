<?php

// Load required files
require_once __DIR__ . '/api_required_files.php';

// Get request URL and method
$requestUri = trim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 5), '/'); // /api/users/1 -> users/1  & /api/users/ -> users & /api/ -> ''
$requestMethod = $_SERVER['REQUEST_METHOD'];

// Load routes from list.php
$routes = require_once __DIR__ . '/../Routes/list.php';

// Check for route matching
$matchedRoute = matchRoute($requestUri, $requestMethod, $routes["apiRoutes"], $routes["publicRoutes"]);

if ($matchedRoute) {
    [$action, $params] = $matchedRoute;
    [$controller, $method] = $action;

    // Call the controller and pass the parameters
    $controllerInstance = new $controller();
    $controllerInstance->$method(...$params);
} else {
    response(NULL, 404, 'Not Found');
}