# 📚 BIB-App - Symfony DDD Implementation

> Eine vollständige Domain-Driven Design Implementation in Symfony mit modernen PHP 8.0+ Features

## 🎯 Features

- ✨ **Domain-Driven Design** mit 4 Bounded Contexts
- 🏗️ **Clean Architecture** mit strikter Separation of Concerns
- 🔌 **Dependency Injection** vollständig konfiguriert
- 📚 **10+ REST API Endpoints** mit OpenAPI Spezifikation
- 🛡️ **Typsichere Exceptions** für konsistentes Error-Handling
- 📝 **DTOs** für sichere Datenkommunikation zwischen Schichten
- 🗄️ **Doctrine ORM** mit Migrations
- 🧪 **Testbar** - alle Services mockbar
- 📖 **Umfassend dokumentiert** - 5 Guides + API Spec
- 🔄 **Integriert mit bestehendem Code** via KalenderAdapter

## 🚀 Quick Start

### 1. Installation
```bash
# Symfony Projekt erstellen
symfony new bib-app --full

# Dependencies installieren
composer require doctrine/orm doctrine/migrations ramsey/uuid

# Alle DDD-Dateien kopieren (src/, config/services.yaml, migrations/)
```

### 2. Datenbank Setup
```bash
# .env.local: DATABASE_URL konfigurieren

# Datenbank erstellen & Migrations ausführen
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 3. Server starten
```bash
symfony server:start
# oder: php -S 127.0.0.1:8000 -t public/
```

### 4. API testen
```bash
# Benutzer registrieren
curl -X POST http://localhost:8000/api/users/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","passwort":"pwd123","name":"Test","vorname":"User","klassenname":"DummyKlasse"}'
```

## 📁 Projekt-Struktur

```
src/
├── Domain/                    # Geschäftslogik & Entities
│   ├── Calendar/             # Stundenplan Management
│   ├── Class/                # Klassen Management
│   ├── User/                 # Benutzer Management
│   ├── Password/             # Passwort-Reset
│   └── Shared/               # Exceptions & Value Objects
├── Application/              # Use Cases & Orchestrierung
│   ├── Calendar/             # Calendar Use Cases
│   ├── Class/                # Class Use Cases
│   └── User/                 # User & Password Use Cases
├── Infrastructure/           # Persistierung & externe Services
│   ├── Repository/           # Doctrine Repository Implementations
│   ├── Kalender/             # Adapter für alten Kalender-Code
│   └── EventListener/        # Doctrine Event Listeners
└── Presentation/             # HTTP Controller
    └── Http/Controller/      # REST API Endpoints
```

## 📚 Dokumentation

| Datei | Inhalt |
|-------|--------|
| **FINAL_SUMMARY.md** | 👈 START HERE - Komplette Übersicht |
| **DDD_ARCHITECTURE.md** | Architektur & DDD Konzepte |
| **QUICKSTART.md** | Installation, Commands, Tipps |
| **MIGRATION_GUIDE.md** | Step-by-Step Implementierung |
| **FILE_OVERVIEW.md** | Detaillierte Datei-Dokumentation |
| **openapi.yaml** | API-Spezifikation |

## 🔑 Wichtige Klassen

### Domain Services
- `UserService` - Benutzer Management
- `ClassService` - Klassen Management  
- `CalendarService` - Stundenplan & Termine
- `PasswordResetService` - Passwort-Reset

### Application Services
- `UserApplicationService` - User Use Cases
- `ClassApplicationService` - Class Use Cases
- `CalendarApplicationService` - Calendar Use Cases
- `PasswordApplicationService` - Password Use Cases

### Controllers (REST API)
- `CalendarController` - /api/calendar
- `ClassController` - /api/classes
- `UserController` - /api/users

## 🏗️ Schichtenmodell

```
┌─────────────────────────────────┐
│  Presentation Layer             │
│  Controllers (HTTP API)         │
├─────────────────────────────────┤
│  Application Layer              │
│  Use Cases & Orchestrierung     │
├─────────────────────────────────┤
│  Domain Layer                   │
│  Business Logic & Entities      │
├─────────────────────────────────┤
│  Infrastructure Layer           │
│  Repository Implementations     │
├─────────────────────────────────┤
│  Database (Doctrine ORM)        │
└─────────────────────────────────┘
```

## 🛠️ Commands

```bash
# Datenbank
php bin/console doctrine:database:create
php bin/console doctrine:database:drop --force
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:status

# Server
symfony server:start
symfony server:stop

# Debug
php bin/console debug:container
php bin/console debug:router
php bin/console debug:autowiring

# Tests
php bin/phpunit
php bin/phpunit --coverage-html coverage/

# Code Quality
php vendor/bin/php-cs-fixer fix src/
php vendor/bin/phpstan analyse src/
```

## 📝 API Endpoints

### User Management
```
POST   /api/users/register              # Registrieren
GET    /api/users/me                    # Aktuelle User-Daten
GET    /api/users/profile               # Profil abrufen
PUT    /api/users/profile               # Profil aktualisieren
POST   /api/users/password/reset-request # Reset anfordern
POST   /api/users/password/reset        # Passwort zurücksetzen
```

### Class Management
```
GET    /api/classes                     # Alle Klassen
POST   /api/classes                     # Klasse erstellen
GET    /api/classes/user                # Benutzer-Klasse
PUT    /api/classes/{id}                # Klasse aktualisieren
DELETE /api/classes/{id}                # Klasse löschen
```

### Calendar Management
```
GET    /api/calendar                    # Stundenplan abrufen
GET    /api/calendar/changes            # Änderungen abrufen
GET    /api/calendar/noted-changes      # Gelesene Änderungen
POST   /api/calendar/noted-changes      # Als gelesen markieren
```

## 🧪 Testing

```php
// Unit Test beispiel
public function testRegisterUser(): void
{
    $userRepo = $this->createMock(UserRepositoryInterface::class);
    $service = new UserService($userRepo);
    
    $dto = new RegisterUserDTO(...);
    $result = $service->registerUser($dto);
    
    $this->assertTrue($result);
}
```

## 🔐 Sicherheit

- ✅ Password Hashing mit PASSWORD_DEFAULT
- ✅ Email-Validierung mit Value Objects
- ✅ Role-based Authorization (ist_admin Flag)
- ✅ Reset-Token mit Expiration
- ✅ Typsichere Exceptions für Security

## 📊 Statistik

- **48** PHP-Dateien
- **4** Bounded Contexts
- **10+** API Endpoints
- **100%** Typisiert (PHP 8.0+)
- **0** Zirkuläre Dependencies

## 🚀 Nächste Schritte

1. [ ] Dateien in Symfony Projekt kopieren
2. [ ] Datenbank Migrations ausführen
3. [ ] OpenAPI-Generator für Controller nutzen
4. [ ] Kalender-Integration via KalenderAdapter
5. [ ] Security/Authentication konfigurieren
6. [ ] Tests schreiben
7. [ ] Deployen!

## 📖 Weitere Ressourcen

- [Symfony Documentation](https://symfony.com/doc)
- [Domain-Driven Design](https://en.wikipedia.org/wiki/Domain-driven_design)
- [OpenAPI Specification](https://spec.openapis.org/)
- [Doctrine ORM](https://www.doctrine-project.org/)

## 💬 Support

- Siehe **QUICKSTART.md** für häufige Fehler
- Siehe **MIGRATION_GUIDE.md** für Implementierungsdetails
- Siehe **DDD_ARCHITECTURE.md** für Architektur-Fragen

## 📄 Lizenz

Projekt für bib-App Klassenverwaltung

---

**Status**: ✅ Fertig zur Implementierung
**Erstellt**: 20.05.2026
**Symfony Version**: 6.0+
**PHP Version**: 8.0+


