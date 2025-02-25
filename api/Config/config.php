<?php

// .env dosyasını oku
$envFile = dirname(__DIR__, 2) . '/api/.env';
if (file_exists($envFile)) {
    $env = parse_ini_file($envFile);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}
