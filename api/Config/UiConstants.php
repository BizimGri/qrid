<?php

class UiConstants
{
    private static $words = [];
    private static $defaultLang = "en";
    private static $languages = ["en", "tr"];

    // Method to load the language file (only reads the file on the first call)
    public static function load($lang = self::getBrowserLanguage())
    {
        if (!empty(self::$words) && self::getWord("lang") === $lang) {
            return; // If already loaded, do not load again
        }

        $filePath = __DIR__ . "/../Languages/ui/{$lang}.json";
        if (!file_exists($filePath)) {
            $filePath = __DIR__ . "/../Languages/ui/" . self::$defaultLang . ".json";
        }

        self::$words = json_decode(file_get_contents($filePath), true) ?? [];
    }

    // Get a specific word (if missing, return the word in the default language)
    public static function getWord($key)
    {
        return self::$words[$key] ?? "[$key]"; // If translation is not found, return the key itself
    }

    public static function getBrowserLanguage($default = "en")
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return $default;
        }

        $acceptedLanguages = explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']);

        // For values like "tr-TR", take the first two characters (language code)
        foreach ($acceptedLanguages as $lang) {
            $lang = substr($lang, 0, 2);
            if (in_array($lang, self::$languages)) { // Supported languages
                return $lang;
            }
        }

        return $default;
    }
}

// Usage:
// UiConstants::load("tr");
// echo UiConstants::getWord("welcome"); // If "welcome" translation exists, it shows, otherwise returns "[welcome]"
