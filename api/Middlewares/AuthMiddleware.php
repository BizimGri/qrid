<?php

require_once __DIR__ . '/../Helpers/JwtHandler.php';

class AuthMiddleware
{
    public static $person;

    public static function handle()
    {
        self::authenticate(); // Giriş kontrolü yap
    }

    public static function check()
    {
        if (!isset($_COOKIE['jwt_token'])) {
            return;
        }

        $token = $_COOKIE['jwt_token'];
        $decoded = JwtHandler::decode($token);

        if (!$decoded) {
            return;
        }

        self::$person = (array) $decoded; // Kullanıcı bilgilerini döndür
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

        self::$person = (array) $decoded; // Kullanıcı bilgilerini döndür
    }
}
