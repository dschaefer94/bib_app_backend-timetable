# Symfony DDD Migration - Implementierungs-Leitfaden

## 📌 Überblick

Diese Dokumentation erklärt, wie die Migration von einem PHP/Laravel System zu Symfony mit Domain-Driven Design durchgeführt wird.

## 🎯 Was wurde bereits implementiert

✅ **Vollständige DDD-Struktur** mit 4 Bounded Contexts:
- User Management
- Class Management  
- Calendar Management
- Password Reset

✅ **Alle Entities und DTOs**
✅ **Alle Domain Services** mit Geschäftslogik
✅ **Application Services** für Use Cases
✅ **Repository Interfaces & Implementations**
✅ **Exception Handling** mit typisierten Exceptions
✅ **Controller Stubs** bereit für OpenAPI-Generator
✅ **Dependency Injection** vollständig konfiguriert
✅ **Database Migrations** vorbereitet

## 🚀 Schritt-für-Schritt Implementierung

### Phase 1: Projekt Setup (1-2 Stunden)

#### 1.1 Symfony Project erzeugen
```bash
symfony new bib-app --full
cd bib-app
```

#### 1.2 Abhängigkeiten installieren
```bash
composer require doctrine/orm doctrine/migrations
composer require symfony/security-bundle
composer require symfony/mailer
composer require ramsey/uuid
```

#### 1.3 Alle erstellten Dateien kopieren
```bash
# src/ Ordnerstruktur kopieren
# config/services.yaml kopieren
# migrations/ kopieren
```

#### 1.4 .env.local konfigurieren
```env
DATABASE_URL="mysql://root:password@127.0.0.1:3306/bib_app?serverVersion=5.7"
MAILER_DSN="smtp://localhost:1025"
```

### Phase 2: Datenbank Setup (30 Minuten)

```bash
# Datenbank erstellen
php bin/console doctrine:database:create

# Migrations validieren
php bin/console doctrine:migrations:status

# Migrations ausführen
php bin/console doctrine:migrations:migrate

# Oder: Schema aus Entities generieren
php bin/console doctrine:schema:create
```

### Phase 3: OpenAPI-Generator Integration (1-2 Stunden)

#### 3.1 OpenAPI-Generator CLI installieren
```bash
npm install -g @openapitools/openapi-generator-cli
```

#### 3.2 Controller generieren
```bash
openapi-generator-cli generate \
  -i openapi.yaml \
  -g php-symfony \
  -o ./generated \
  -c openapi-config.json
```

#### 3.3 Generierte Controller einbinden
```bash
# Generierte Dateien in src/ verschieben
# (überschreibt die stubs)
```

### Phase 4: Kalender-Integration (2-3 Stunden)

#### 4.1 Alten Kalender-Code in Symfony integrieren

```php
// src/Infrastructure/Kalender/KalenderAdapter.php
// (bereits vorbereitet)

public static function updateCalendar(
    string $klassenname,
    string $icalLink,
    \PDO $pdo
): void {
    // Integration mit altem Code
    // \SDP\Updater\kalenderupdater($klassenname, $pdo);
}
```

#### 4.2 Event Listeners konfigurieren
```php
// Wird automatisch nach Klasse-Erstellung aufgerufen
// src/Infrastructure/EventListener/ClassEntityListener.php
```

#### 4.3 Kalender-Tabellen Schema
```bash
# Zusätzliche Migrations für dynamische Tabellen
# "{klassenname}_alter_stundenplan"
# "{klassenname}_neuer_stundenplan"  
# "{klassenname}_aenderungen"
```

### Phase 5: Authentication/Security (1-2 Stunden)

#### 5.1 Security Bundle konfigurieren
```yaml
# config/packages/security.yaml
security:
  providers:
    app_user_provider:
      entity:
        class: App\Domain\User\Entity\User
        property: email
  
  firewalls:
    api:
      stateless: true
      # JWT oder SessionBasedAuth konfigurieren
```

#### 5.2 UserContext mit Security integrieren
```php
// src/Application/Shared/UserContext.php
// Mit Symfony Security Component füllen
```

#### 5.3 Middleware für UserContext
```php
// Erstelle EventListener der UserContext füllt
// aus Symfony Security
```

### Phase 6: Testing (1-2 Stunden)

#### 6.1 PHPUnit Setup
```bash
composer require --dev phpunit/phpunit
php bin/console make:test
```

#### 6.2 Service Tests schreiben
```php
// tests/Domain/User/Service/UserServiceTest.php
// tests/Domain/Class/Service/ClassServiceTest.php
// tests/Application/*/ApplicationServiceTest.php
```

#### 6.3 Integration Tests
```php
// tests/Integration/Api/CalendarApiTest.php
// tests/Integration/Api/ClassApiTest.php
// tests/Integration/Api/UserApiTest.php
```

### Phase 7: Deployment (1 Stunde)

#### 7.1 Production Environment
```bash
# .env.prod konfigurieren
APP_ENV=prod
APP_DEBUG=0
```

#### 7.2 Cache aufwärmen
```bash
php bin/console cache:warmup --env=prod
```

#### 7.3 Migrations in Production
```bash
php bin/console doctrine:migrations:migrate --env=prod
```

---

## 📝 Wichtige Implementierungs-Details

### UserContext-Handling

**Problem**: Wie wird UserContext zwischen Requests gefüllt?

**Lösung**:
```php
// EventListener bei jedem Request
class UserContextListener implements EventSubscriberInterface
{
    public function __construct(private UserContext $userContext) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        $user = $event->getRequest()->attributes->get('_user');
        if ($user) {
            $this->userContext->setUserId($user->getId());
            $this->userContext->setAdmin($user->isAdmin());
            $this->userContext->setKlassenname($user->getKlassenname());
        }
    }
}
```

### Kalender-Tabellen Handling

**Problem**: Dynamische Tabellen pro Klasse - wie handhaben?

**Lösung**:
```php
// Event Listener bei Klasse-Erstellung
public function postPersist(PostPersistEventArgs $args): void
{
    $class = $args->getObject();
    KalenderAdapter::updateCalendar(
        $class->getKlassenname(),
        $class->getIcalLink(),
        $pdo
    );
}
```

### Email-Versand

**Problem**: Mailer Service für Password Reset

**Lösung**:
```php
// services.yaml
App\Domain\Password\Service\PasswordResetService:
  arguments:
    - '@App\Domain\User\Repository\UserRepositoryInterface'
    - '@mailer'

// PHP
$emailMessage = (new Email())
    ->from('noreply@domain.de')
    ->to($email)
    ->subject('Passwort zurücksetzen')
    ->html(...);
$mailer->send($emailMessage);
```

---

## 🧪 Testing-Strategie

### Unit Tests (Domain Services)
```php
public function testRegisterUser(): void
{
    $userRepo = $this->createMock(UserRepositoryInterface::class);
    $service = new UserService($userRepo);
    
    $result = $service->registerUser($dto);
    $this->assertTrue($result);
}
```

### Integration Tests (Controller + Service)
```php
public function testRegisterUserApi(): void
{
    $response = $this->client->post('/api/users/register', [
        'json' => [
            'email' => 'test@example.com',
            'passwort' => 'password123',
            'name' => 'Test',
            'vorname' => 'User',
            'klassenname' => 'DummyKlasse'
        ]
    ]);
    
    $this->assertEquals(201, $response->getStatusCode());
}
```

---

## 📋 Checkliste für die Migration

- [ ] Symfony Projekt erstellt
- [ ] Dependencies installiert
- [ ] DDD-Struktur kopiert
- [ ] Services konfiguriert
- [ ] Datenbank Migrations ausgeführt
- [ ] OpenAPI-Generator integriert
- [ ] Kalender-Adapter implementiert
- [ ] Security konfiguriert
- [ ] UserContext Listener erstellt
- [ ] Tests geschrieben
- [ ] Production Environment konfiguriert
- [ ] Deployed & getestet

---

## 🔗 Integration mit bestehendem Code

### Alte PHP-Code Nutzung

```php
// src/Infrastructure/Kalender/KalenderAdapter.php
// Wrapper für alten Code
require_once __DIR__ . '/../../Kalender/kalenderrunner.php';

$runner = new \SDP\Kalender\kalenderrunner();
```

### Migrationsweg (optional)

1. **Phase 1 (Jetzt)**: Neuer Code mit Symfony, alter Kalender-Code
2. **Phase 2**: Kalender-Code schrittweise nach Symfony migrieren
3. **Phase 3**: Alten Code entfernen

---

## 🐛 Häufige Fehler bei der Migration

### Fehler 1: PDO vs. Doctrine ORM

**Problem**: Kalender-Code nutzt rohes PDO

**Lösung**: KalenderAdapter als Brücke nutzen oder in DDD umschreiben

### Fehler 2: Zirkuläre Dependencies

**Problem**: ClassService braucht UserService, UserService braucht ClassService

**Lösung**: Services durch Interfaces injizieren, nicht durch konkrete Klassen

### Fehler 3: Fehlende Session-Handling

**Problem**: Alter Code nutzt $_SESSION, Symfony nutzt Request

**Lösung**: SecurityBundle nutzen, UserContext füllen

### Fehler 4: Keine Migrationen

**Problem**: Manuelle SQL-Änderungen in Migrations

**Lösung**: Doctrine Migrations immer verwenden

---

## 📊 Zeitabschätzung

| Phase | Aufwand | Priorität |
|-------|---------|-----------|
| Setup | 1-2h | 🔴 Critical |
| DB & Migrations | 30min | 🔴 Critical |
| OpenAPI-Generator | 1-2h | 🟡 High |
| Kalender-Integration | 2-3h | 🟡 High |
| Security | 1-2h | 🟡 High |
| Testing | 1-2h | 🟢 Medium |
| Deployment | 1h | 🟢 Medium |
| **Total** | **7-12h** | |

---

## ✅ Erfolgs-Kriterien

- [ ] Alle API-Endpoints funktionieren
- [ ] Tests bestehen zu 90%+
- [ ] Dokumentation ist aktuell
- [ ] Performance ist vergleichbar mit alter Version
- [ ] Fehlerbehandlung ist konsistent
- [ ] Kalender-Updates funktionieren
- [ ] Email-Versand funktioniert
- [ ] Deployment ohne Fehler

---

## 📞 Support & Debugging

### Logs anschauen
```bash
tail -f var/log/dev.log
```

### Debug Toolbar
```
http://localhost:8000/_profiler
```

### Doctrine Debugging
```bash
php bin/console doctrine:mapping:info
php bin/console doctrine:query:sql "SELECT * FROM benutzer"
```

### Service Debugging
```bash
php bin/console debug:container App\\Domain\\User\\Service\\UserService
```


