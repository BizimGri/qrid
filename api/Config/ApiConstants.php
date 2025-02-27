<?php

class Constants
{
    private static $words = [];

    public static function load()
    {
        if (!empty(self::$words)) {
            return;
        }

        $filePath = __DIR__ . "/../Languages/api/key-values.json";
        self::$words = json_decode(file_get_contents($filePath), true) ?? [];
    }

    public static function getWord($key)
    {
        return self::$words[$key] ?? "[$key]";
    }
}