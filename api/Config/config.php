<?php
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../Storage/Logs/php_error.log');

// .env dosyasını oku
$envFile = dirname(__DIR__, 2) . '/api/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
