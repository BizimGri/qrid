<?php

require_once __DIR__.'/../Helpers/jwtHandler.php';

class AuthMiddleware
{
    public static function handle()
    {
        self::authenticate(); // Giriş kontrolü yap
    }

    private static function authenticate()
    {
        if (!isset($_COOKIE['jwt_token'])) {
            response(NULL, 401, "Unauthorized: Missing token.");
        }

        $token = $_COOKIE['jwt_token'];
        $decoded = JwtHandler::decode($token);

        if (!$decoded) {
            response(NULL, 401, "Unauthorized: Invalid or expired token.");
        }

        return (array) $decoded; // Kullanıcı bilgilerini döndür
    }
}
