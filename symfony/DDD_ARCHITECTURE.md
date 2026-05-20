# Symfony DDD Struktur - Dokumentation

## Übersicht

Die Anwendung folgt Domain-Driven Design (DDD) Prinzipien und ist in Symfony implementiert. Die Architektur ist in vier Schichten unterteilt:

### Architektur-Schichten

```
┌─────────────────────────────────────────┐
│   Presentation Layer                    │
│   (HTTP Controller)                     │
└──────────────────┬──────────────────────┘
                   │
┌──────────────────▼──────────────────────┐
│   Application Layer                     │
│   (Use Cases / Application Services)    │
└──────────────────┬──────────────────────┘
                   │
┌──────────────────▼──────────────────────┐
│   Domain Layer                          │
│   (Business Logic / Services / Entities)│
└──────────────────┬──────────────────────┘
                   │
┌──────────────────▼──────────────────────┐
│   Infrastructure Layer                  │
│   (Repository Implementations)          │
└─────────────────────────────────────────┘
```

## Verzeichnisstruktur

```
src/
├── Domain/                          # Geschäftslogik & Entities
│   ├── Calendar/                    # Bounded Context: Stundenplan
│   │   ├── Entity/                  # CalendarEvent, Change, NotedChange
│   │   ├── Repository/              # Interfaces für Persistierung
│   │   ├── Service/                 # CalendarService (Geschäftslogik)
│   │   └── DTO/                     # Data Transfer Objects
│   ├── Class/                       # Bounded Context: Klassen
│   │   ├── Entity/                  # ClassEntity
│   │   ├── Repository/              # ClassRepositoryInterface
│   │   ├── Service/                 # ClassService
│   │   └── DTO/                     # ClassDTO, CreateClassDTO
│   ├── User/                        # Bounded Context: Benutzer
│   │   ├── Entity/                  # User, PersonalData
│   │   ├── Repository/              # UserRepositoryInterface, PersonalDataRepositoryInterface
│   │   ├── Service/                 # UserService
│   │   └── DTO/                     # UserDTO, RegisterUserDTO, UpdateProfileDTO
│   ├── Password/                    # Bounded Context: Passwort-Reset
│   │   ├── Service/                 # PasswordResetService
│   │   └── DTO/                     # RequestPasswordResetDTO, ResetPasswordDTO
│   └── Shared/                      # Gemeinsame Utilities
│       ├── Exception/               # Exceptions
│       └── ValueObject/             # Email, etc.
│
├── Application/                     # Use Cases & Orchestrierung
│   ├── Calendar/                    # CalendarApplicationService
│   ├── Class/                       # ClassApplicationService
│   ├── User/                        # UserApplicationService, PasswordApplicationService
│   └── Shared/                      # UserContext
│
├── Infrastructure/                  # Persistierung & externe Services
│   ├── Repository/                  # Repository Implementierungen
│   └── Persistence/                 # DB-Konfiguration
│
└── Presentation/                    # HTTP API Layer
    └── Http/
        └── Controller/              # API Controller
```

## Domain-Driven Design Konzepte

### Bounded Contexts

Die Anwendung ist in vier unabhängige Bounded Contexts unterteilt:

1. **Calendar Context**: Verwaltung von Stundenplänen und Terminänderungen
2. **Class Context**: Verwaltung von Klassen
3. **User Context**: Benutzerverwaltung und Authentifizierung
4. **Password Context**: Passwort-Reset Funktionalität

Jeder Context hat:
- **Entities**: Domain-Objekte mit Identität
- **Services**: Geschäftslogik
- **Repositories**: Persistierungs-Abstraktion
- **DTOs**: Datenübertragung zwischen Schichten

### Value Objects

```php
Email // Validiert E-Mail-Format
```

### Repositories

Repositories abstrarahieren die Persistierung:
- **Interface**: `App\Domain\{Context}\Repository\*RepositoryInterface`
- **Implementation**: `App\Infrastructure\Repository\*Repository`

## Workflow: Von der Anfrage bis zur Antwort

```
HTTP Request
    ↓
Controller (Presentation Layer)
    ↓ (RequestDTO erstellen)
ApplicationService (Application Layer)
    ↓ (Use Case orchestrieren)
DomainService (Domain Layer)
    ↓ (Geschäftslogik)
Repository (Persistierung)
    ↓
Datenbank
    ↓ (Antwort)
ResponseDTO
    ↓
HTTP Response
```

### Beispiel: Benutzer registrieren

1. **Controller** empfängt POST Request
2. **Controller** erstellt `RegisterUserDTO` aus Request
3. **ApplicationService** ruft Domain-Logic auf
4. **UserService** validiert Daten, erstellt User Entity
5. **Repository** speichert User in Datenbank
6. **Controller** returniert JSON Response

## Services und ihre Verantwortung

### Domain Services
- **Geschäftslogik** - Was sind die Geschäftsregeln?
- **Validierung** - Sind die Daten korrekt?
- **Aggregation** - Zusammenhang zwischen Entities
- **Persistierungs-Abstraktion** - Repositories verwenden

### Application Services
- **Use Cases** - Was möchte der Benutzer tun?
- **Orchestrierung** - Koordination von Domain Services
- **Autorisierung** - Berechtigungen prüfen
- **Kontext** - Benutzer-Informationen nutzen

### Controller
- **HTTP-Handling** - Request/Response Mapping
- **Input-Validierung** - BasicValidation
- **Error-Handling** - Exception zu Response
- **Weitergabe an ApplicationService**

## DTOs (Data Transfer Objects)

DTOs sind Datencontainer für die Kommunikation zwischen Schichten:

```php
// Eingehend (Request)
RegisterUserDTO::fromRequest($data)

// Ausgehend (Response)
UserDTO::toArray()
```

## Exception Handling

```
UnauthorizedException (401)
    - notAuthenticated()
    - notAuthorized()
    - adminRequired()

ValidationException (400)
    - withErrors(array)
    - missingField(string)

EntityNotFoundException (404)
    - userNotFound()
    - classNotFound()
    - changeNotFound()

ConflictException (409)
    - alreadyExists(string)
    - classAlreadyExists()
    - userAlreadyExists()
```

## Dependency Injection

Die Services sind in `config/services.yaml` konfiguriert:

```yaml
App\Domain\User\Repository\UserRepositoryInterface: 
  '@App\Infrastructure\Repository\UserRepository'
```

Symfony injiziert automatisch die Implementierungen.

## Nächste Schritte

1. **OpenAPI-Generator**: Controller mit `openapi-generator-cli` generieren
2. **Migrations**: Doctrine Migrations für Datenbankschema
3. **Tests**: PHPUnit Tests für Services
4. **Security**: Symfony Security für Authentication/Authorization
5. **Event Listener**: Für kalenderupdater etc.

## Wichtige Dateien

- `config/services.yaml`: Dependency Injection Konfiguration
- `src/Domain/*/Entity/*.php`: Doctrine Entities
- `src/Domain/*/Service/*.php`: Geschäftslogik
- `src/Application/*/ApplicationService.php`: Use Cases
- `src/Infrastructure/Repository/*.php`: Datenpersistierung
- `openapi.yaml`: API Spezifikation


