<?php
putenv('APP_ENV=dev');
putenv('APP_DEBUG=true');
putenv('DEFAULT_URI=http://localhost:8000');
putenv('DATABASE_URL=sqlite:///%kernel.project_dir%/var/data.db');
putenv('MAILER_DSN=null://null');

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Dotenv\Dotenv;

require_once dirname(__DIR__).'/vendor/autoload.php';

// Lade .env Datei
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new Kernel($_ENV['APP_ENV'] ?? 'dev', $_ENV['APP_DEBUG'] ?? true);

// Teste neue RESTful API
$request = Request::create(
    '/api/users/register',
    'POST',
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/json'],
    json_encode([
        'email' => 'max@example.de',
        'passwort' => 'password123',
        'name' => 'Mustermann',
        'vorname' => 'Max',
        'klassenname' => '10A'
    ])
);

$response = $kernel->handle($request);
echo "=== Registrierungs-Test ===\r\n";
echo "Status: " . $response->getStatusCode() . "\r\n";
echo "Response:\r\n" . $response->getContent() . "\r\n";
?>

