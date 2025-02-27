<?php
// For Testing
require_once __DIR__ . '/../Controllers/UserController.php';

require_once __DIR__ . '/../Config/ApiConstants.php';
require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../Controllers/MainController.php';
require_once __DIR__ . '/../Controllers/PersonController.php';
require_once __DIR__ . '/../Controllers/DataController.php';

Constants::load();

// Load routes from list.php
$routes = require_once __DIR__ . '/../Routes/list.php';

// Get request URL and method
$requestUri = trim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 5), '/'); // /api/users/1 -> users/1  & /api/users/ -> users & /api/ -> ''
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 🛠 Helper Function: Route matching
function matchRoute($requestUri, $requestMethod, $apiRoutes, $publicRoutes)
{
    // If request URI is completely empty
    if (empty($requestUri)) {
        response(NULL, 418, 'Welcome To The Desert Of The Real!');
    }

    // Check the valid HTTP method
    if (!isset($apiRoutes[$requestMethod])) {
        return null;
    }

    // Split URL into segments // for now, we only use 0 (baseRoute) and 1 (subRoute/id) parts
    $uriSegments = explode('/', $requestUri);
    $baseRoute = $uriSegments[0] ?? '';

    // If it's not a public route, run AuthMiddleware
    if (!in_array($uriSegments[1] ?? '', $publicRoutes[$requestMethod][$baseRoute])) {
        AuthMiddleware::handle();
    }

    // If the main route (e.g., users, images) is not found, return 404
    if (!isset($apiRoutes[$requestMethod][$baseRoute])) {
        return null;
    }

    // Get sub-routes
    $subRoutes = $apiRoutes[$requestMethod][$baseRoute];
    $subPath = $uriSegments[1] ?? null; // users/{id} -> {id}

    // If there is a direct match
    if ($subPath === null && isset($subRoutes[''])) {
        return [$subRoutes[''], []]; // A request like /users
    }

    // First, check for statically defined paths (e.g., users/campaign)
    if ($subPath !== null && isset($subRoutes[$subPath])) {
        return [$subRoutes[$subPath], []];
    }

    // If it's in the form of users/{id}, directly get the ID
    if ($subPath !== null && isset($subRoutes['{id}'])) {
        $cleanId = sanitizeId($subPath); // Make it safe
        return [$subRoutes['{id}'], [$cleanId]];
    }

    return null;
}

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