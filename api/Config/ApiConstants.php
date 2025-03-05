<?php

class ApiConstants
{
    private static $words = [];

    public static function load()
    {
        if (!empty(self::$words)) {
            return;
        }

        $filePath = __DIR__ . "/constants.json";
        self::$words = json_decode(file_get_contents($filePath), true) ?? [];
    }

    public static function getWord(string $type, string $key): string
    {
        return self::$words[$type][$key] ?? "[{$type}.{$key}]";
    }
}

ApiConstants::load();
