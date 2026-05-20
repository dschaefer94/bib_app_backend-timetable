# Symfony DDD - Quickstart Guide

## Installation & Setup

### 1. Voraussetzungen

- PHP 8.0+
- Composer
- Symfony 6.0+

### 2. Projekt-Struktur laden

```bash
# In das Projektverzeichnis gehen
cd bib-App

# Composer Dependencies installieren
composer install

# Doctrine Mapping generieren (falls noch nicht geschehen)
php bin/console doctrine:schema:validate
```

### 3. Datenbank konfigurieren

`.env.local` anpassen:
```
DATABASE_URL="mysql://user:password@127.0.0.1:3306/bib_app"
MAILER_DSN="smtp://localhost:1025"
```

### 4. Migrations ausführen

```bash
# Migrations anzeigen
php bin/console doctrine:migrations:status

# Migrations ausführen
php bin/console doctrine:migrations:migrate

# (Optional) Migration rückgängig machen
php bin/console doctrine:migrations:execute --down 'DoctrineMigrations\Version20260520000000'
```

### 5. Server starten

```bash
# Entwicklungs-Server starten
symfony server:start

# Oder mit PHP built-in Server
php -S 127.0.0.1:8000 -t public/
```

## Workflow: Neuen Endpoint implementieren

### Schritt 1: OpenAPI-Spec aktualisieren

Aktualisiere `openapi.yaml` mit dem neuen Endpoint.

### Schritt 2: OpenAPI-Generator verwenden

```bash
openapi-generator-cli generate \
  -i openapi.yaml \
  -g php-symfony \
  -o ./generated
```

Dies generiert die Controller-Stubs.

### Schritt 3: ApplicationService implementieren

In `src/Application/*/ApplicationService.php`:

```php
public function myUseCase(): array
{
    // Business Logic orchestrieren
    return $this->domainService->doSomething();
}
```

### Schritt 4: DomainService implementieren

In `src/Domain/*/Service/*Service.php`:

```php
public function doSomething(): array
{
    // Geschäftslogik und Validierung
    // Repositories verwenden
    // Exceptions werfen bei Fehler
}
```

### Schritt 5: Controller ausfüllen

Der generierte Controller wird zur Verfügung stehen:

```php
public function myEndpoint(Request $request): JsonResponse
{
    try {
        $result = $this->applicationService->myUseCase();
        return $this->json($result);
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
    }
}
```

## Wichtige Commands

```bash
# Datenbank-Schema anzeigen
php bin/console doctrine:schema:display

# Entity generieren (falls benötigt)
php bin/console make:entity

# Migration generieren (falls benötigt)
php bin/console make:migration

# Services debuggen
php bin/console debug:container

# Routes auflisten
php bin/console debug:router

# Testing
php bin/phpunit

# Code-Analyse
php bin/phpstan analyse src/

# Code-Style
php vendor/bin/php-cs-fixer fix src/
```

## Fehlerbehandlung

### Exception Handling im Controller

```php
try {
    $result = $this->applicationService->doSomething();
    return $this->json($result);
} catch (UnauthorizedException $e) {
    return $this->json(['error' => $e->getMessage()], 401);
} catch (ValidationException $e) {
    return $this->json(['error' => $e->getMessage(), 'details' => $e->getErrors()], 400);
} catch (EntityNotFoundException $e) {
    return $this->json(['error' => $e->getMessage()], 404);
} catch (ConflictException $e) {
    return $this->json(['error' => $e->getMessage()], 409);
} catch (\Exception $e) {
    return $this->json(['error' => 'Internal Server Error'], 500);
}
```

## Beispiel: Neue Entität hinzufügen

### 1. Entity erstellen

```php
// src/Domain/MyContext/Entity/MyEntity.php
#[ORM\Entity]
#[ORM\Table(name: '`my_table`')]
class MyEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;
    
    // ... Eigenschaften ...
}
```

### 2. Repository Interface erstellen

```php
// src/Domain/MyContext/Repository/MyRepositoryInterface.php
interface MyRepositoryInterface
{
    public function findById(int $id): ?MyEntity;
    public function save(MyEntity $entity): void;
}
```

### 3. Repository implementieren

```php
// src/Infrastructure/Repository/MyRepository.php
class MyRepository implements MyRepositoryInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}
    
    public function findById(int $id): ?MyEntity
    {
        return $this->entityManager->find(MyEntity::class, $id);
    }
    
    public function save(MyEntity $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }
}
```

### 4. In services.yaml registrieren

```yaml
App\Domain\MyContext\Repository\MyRepositoryInterface: 
  '@App\Infrastructure\Repository\MyRepository'

App\Infrastructure\Repository\MyRepository:
  arguments:
    - '@Doctrine\ORM\EntityManagerInterface'
```

## Testing

### Unit Test für Service

```php
// tests/Domain/User/Service/UserServiceTest.php
class UserServiceTest extends TestCase
{
    private UserService $service;
    private MockObject $userRepository;
    
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new UserService($this->userRepository);
    }
    
    public function testRegisterUser(): void
    {
        $dto = new RegisterUserDTO(/* ... */);
        $result = $this->service->registerUser($dto);
        $this->assertTrue($result);
    }
}
```

## Häufige Probleme

### Problem: Entity nicht persistiert
**Lösung**: `$entityManager->flush()` nicht vergessen

### Problem: Zirkuläre Dependencies
**Lösung**: Services über Interfaces injizieren

### Problem: 404 auf Entity
**Lösung**: `EntityNotFoundException` werfen, nicht null zurückgeben

## Debugging

```bash
# Doctrine SQL Logger aktivieren (in .env)
DOCTRINE_DEBUG=1

# Symfony Debug Toolbar
# http://localhost:8000/_profiler

# Logs anschauen
tail -f var/log/dev.log
```

## Performance Tipps

- Doctrine Query einschränken (nur benötigte Felder)
- Lazy Loading vs Eager Loading beachten
- Caching für häufig gelesene Daten
- Indizes auf Foreign Keys

## Weitere Ressourcen

- [OpenAPI Specification](https://spec.openapis.org/)
- [Symfony Documentation](https://symfony.com/doc)
- [Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)
- [DDD in PHP](https://thevaluable.dev/ddd-php/)


