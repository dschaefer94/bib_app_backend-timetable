<?php

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\ErrorHandler\Debug;
use Symfony\Component\HttpFoundation\Request;

$autoloadPath = __DIR__.'/vendor/autoload.php';

if (!file_exists($autoloadPath)) {
    die("Error: Autoload file not found at " . $autoloadPath . "\n");
}

require $autoloadPath;

// The check is to ensure we don't use .env in production
if (!isset($_SERVER['APP_ENV'])) {
    (new Dotenv())->bootEnv(__DIR__.'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

$_SERVER['APP_ENV'] = $_SERVER['APP_ENV'] ?? 'dev';
$_SERVER['APP_DEBUG'] = $_SERVER['APP_DEBUG'] ?? ($_SERVER['APP_ENV'] !== 'prod');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);

try {
    $kernel->boot();
    // echo "Kernel booted successfully!\n"; // Debug-Ausgabe entfernt
} catch (\Throwable $e) {
    echo "Kernel boot FAILED with exception:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
} finally {
    $kernel->shutdown();
    // echo "Kernel shut down.\n"; // Debug-Ausgabe entfernt
}
