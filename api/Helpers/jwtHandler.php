<?php

class JwtHandler
{
    private static $algorithm = "HS256";

    private static function getSecretKey()
    {
        return getenv("JWT_SECRET") ?? "1b2dfa39435c6abce7be59b22eccbd88095eecfb1341de481a092b7d9deb6a51"; // use default if .env is not available
    }

    public static function encode(array $payload, $expireTime = 3600)
    {
        $header = json_encode(["alg" => self::$algorithm, "typ" => "JWT"]);
        $payload["exp"] = time() + $expireTime;
        $payload = json_encode($payload);

        $base64UrlHeader = self::base64UrlEncode($header);
        $base64UrlPayload = self::base64UrlEncode($payload);

        $signature = hash_hmac("sha256", "$base64UrlHeader.$base64UrlPayload", self::getSecretKey(), true);
        $base64UrlSignature = self::base64UrlEncode($signature);

        return "$base64UrlHeader.$base64UrlPayload.$base64UrlSignature";
    }

    public static function decode($jwt, $ignoreExpiration = false)
    {
        $parts = explode(".", $jwt);
        if (count($parts) !== 3) {
            return false;
        }

        list($header, $payload, $signature) = $parts;

        $expectedSignature = hash_hmac("sha256", "$header.$payload", self::getSecretKey(), true);
        if (!hash_equals(self::base64UrlEncode($expectedSignature), $signature)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($payload), true);
        if (isset($payload["exp"]) && $payload["exp"] < time() && !$ignoreExpiration) {
            return false;
        }

        return $payload;
    }

    private static function base64UrlEncode($data)
    {
        return str_replace(["+", "/", "="], ["-", "_", ""], base64_encode($data));
    }

    private static function base64UrlDecode($data)
    {
        $data = str_replace(["-", "_"], ["+", "/"], $data);
        return base64_decode($data);
    }
}
