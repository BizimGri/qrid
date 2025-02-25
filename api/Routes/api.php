<?php

require_once __DIR__ . '/../Middlewares/AuthMiddleware.php';
require_once __DIR__ . '/../Controllers/UserController.php';

// Middleware'leri çalıştır (route eşleşmesinden önce) 
//$middleware = new AuthMiddleware();
//$middleware->handle(); 

// Rotaları list.php'den yükle
$routes = require_once __DIR__ . '/../Routes/list.php';

// İstek URL'sini ve metodunu al
$requestUri = trim(substr(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), 5), '/'); // /api/users/1 -> users/1  & /api/users/ -> users & /api/ -> ''
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 🛠 Yardımcı Fonksiyon: Route eşleştirme
function matchRoute($requestUri, $requestMethod, $routes)
{
    // Eğer request URI tamamen boşsa
    if (empty($requestUri)) {
        response(NULL, 418, 'Welcome To The Desert Of The Real!');
    }

    // Geçerli HTTP metodunu kontrol et
    if (!isset($routes[$requestMethod])) {
        return null;
    }

    // URL parçalarına ayır // şimdilik sadece 0 (baseRoute) ve 1 (subRoute/id) inci parçaları kullanıyoruz
    $uriSegments = explode('/', $requestUri);
    $baseRoute = $uriSegments[0] ?? '';

    // Ana rota (ör: users, images) bulunamıyorsa 404
    if (!isset($routes[$requestMethod][$baseRoute])) {
        return null;
    }

    // Alt rotaları al
    $subRoutes = $routes[$requestMethod][$baseRoute];
    $subPath = $uriSegments[1] ?? null; // users/{id} -> {id}

    // Doğrudan eşleşme varsa
    if ($subPath === null && isset($subRoutes[''])) {
        return [$subRoutes[''], []]; // /users gibi bir istek
    }

    // Önce sabit tanımlı yolları kontrol et (ÖRN: users/kampanya)
    if ($subPath !== null && isset($subRoutes[$subPath])) {
        return [$subRoutes[$subPath], []];
    }

    // Eğer users/{id} gibi bir yapıdaysa, ID'yi doğrudan al
    if ($subPath !== null && isset($subRoutes['{id}'])) {
        $cleanId = sanitizeId($subPath); // Güvenli hale getir
        return [$subRoutes['{id}'], [$cleanId]];
    }

    return null;
}

// Rota eşleşmesini kontrol et
$matchedRoute = matchRoute($requestUri, $requestMethod, $routes);

if ($matchedRoute) {
    [$action, $params] = $matchedRoute;
    [$controller, $method] = $action;

    // Controller'ı çağır ve parametreleri ilet
    $controllerInstance = new $controller();
    $controllerInstance->$method(...$params);
} else {
    response(NULL, 404, 'Not Found');
}