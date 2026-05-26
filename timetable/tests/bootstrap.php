<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    $dotenv = new Dotenv();
    // Lade zuerst die Basis-.env-Datei
    $dotenv->load(dirname(__DIR__).'/.env');
    // Lade dann die .env.test-Datei, um Test-spezifische Variablen zu überschreiben
    $dotenv->load(dirname(__DIR__).'/.env.test');

    // Stelle sicher, dass APP_ENV auf 'test' gesetzt ist, falls es nicht bereits durch phpunit.xml.dist erfolgt ist
    $_SERVER['APP_ENV'] = 'test';
    $_ENV['APP_ENV'] = 'test';
}

if ($_SERVER['APP_DEBUG'] ?? false) { // Verwende den Null-Coalescing-Operator für Sicherheit
    umask(0000);
}
