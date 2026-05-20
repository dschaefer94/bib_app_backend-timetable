# Erstellte Dateien - Übersicht

## 📋 Struktur nach Domain-Driven Design (DDD)

### Shared (Gemeinsame Utilities)
```
src/Shared/
├── Exception/
│   ├── EntityNotFoundException.php      # 404 Errors
│   ├── UnauthorizedException.php        # 401/403 Errors
│   ├── ValidationException.php          # 400 Errors
│   └── ConflictException.php            # 409 Errors
└── ValueObject/
    └── Email.php                        # Email Value Object mit Validierung
```

### Domain Layer - User Context
```
src/Domain/User/
├── Entity/
│   ├── User.php                         # User Entity
│   └── PersonalData.php                 # Persönliche Daten Entity
├── Repository/
│   ├── UserRepositoryInterface.php      # Repository Interface
│   └── PersonalDataRepositoryInterface.php
├── Service/
│   └── UserService.php                  # Geschäftslogik: User-Verwaltung
└── DTO/
    ├── UserDTO.php                      # Benutzer-Daten DTO
    ├── UserDataDTO.php                  # Profil-Daten DTO
    ├── RegisterUserDTO.php              # Registrierungs-DTO
    └── UpdateProfileDTO.php             # Profil-Update DTO
```

### Domain Layer - Class Context
```
src/Domain/Class/
├── Entity/
│   └── ClassEntity.php                  # Klasse Entity
├── Repository/
│   └── ClassRepositoryInterface.php     # Repository Interface
├── Service/
│   └── ClassService.php                 # Geschäftslogik: Klassen-Verwaltung
└── DTO/
    ├── ClassDTO.php                     # Klasse DTO
    └── CreateClassDTO.php               # Erstellung/Update DTO
```

### Domain Layer - Calendar Context
```
src/Domain/Calendar/
├── Entity/
│   └── NotedChange.php                  # Gelesene Änderungen Entity
├── Repository/
│   └── NotedChangeRepositoryInterface.php
├── Service/
│   └── CalendarService.php              # Geschäftslogik: Stundenplan & Termine
└── DTO/
    ├── CalendarEventDTO.php             # Termin DTO
    ├── ChangeDTO.php                    # Änderung DTO
    └── NotedChangeDTO.php               # Gelesene Änderung DTO
```

### Domain Layer - Password Context
```
src/Domain/Password/
├── Service/
│   └── PasswordResetService.php         # Passwort-Reset Logik
└── DTO/
    ├── RequestPasswordResetDTO.php      # Reset-Anfrage DTO
    └── ResetPasswordDTO.php             # Password-Update DTO
```

### Application Layer
```
src/Application/
├── Shared/
│   └── UserContext.php                  # Benutzer-Kontext während Request
├── Calendar/
│   └── CalendarApplicationService.php   # Use Cases: Stundenplan
├── Class/
│   └── ClassApplicationService.php      # Use Cases: Klassen
└── User/
    ├── UserApplicationService.php       # Use Cases: Benutzer
    └── PasswordApplicationService.php   # Use Cases: Passwort
```

### Infrastructure Layer (Persistierung)
```
src/Infrastructure/Repository/
├── UserRepository.php                   # User Repository Implementierung
├── PersonalDataRepository.php           # PersonalData Repository Implementierung
├── ClassRepository.php                  # Class Repository Implementierung
└── NotedChangeRepository.php            # NotedChange Repository Implementierung
```

### Presentation Layer (HTTP API)
```
src/Presentation/Http/Controller/
├── CalendarController.php               # Stundenplan-API Endpoints
├── ClassController.php                  # Klassen-API Endpoints
└── UserController.php                   # Benutzer & Passwort-API Endpoints
```

### Configuration
```
config/
└── services.yaml                        # Dependency Injection Konfiguration
```

### Migrations
```
migrations/
└── Version20260520000000.php            # Initial Database Schema
```

### Documentation
```
DDD_ARCHITECTURE.md                      # Architektur-Dokumentation
QUICKSTART.md                            # Installation & Workflow
```

---

## 📊 Zusammenfassung der Erstellten Dateien

| Kategorie | Anzahl | Dateien |
|-----------|--------|---------|
| **Exceptions** | 4 | EntityNotFoundException, UnauthorizedException, ValidationException, ConflictException |
| **Value Objects** | 1 | Email |
| **User Domain** | 9 | 2 Entities, 2 Repositories, 1 Service, 4 DTOs |
| **Class Domain** | 6 | 1 Entity, 1 Repository, 1 Service, 2 DTOs |
| **Calendar Domain** | 7 | 1 Entity, 1 Repository, 1 Service, 3 DTOs |
| **Password Domain** | 3 | 1 Service, 2 DTOs |
| **Application Services** | 4 | UserContext, 3 ApplicationServices |
| **Infrastructure** | 4 | 4 Repository Implementierungen |
| **Controllers** | 3 | CalendarController, ClassController, UserController |
| **Config & Migration** | 2 | services.yaml, Migration |
| **Documentation** | 2 | DDD_ARCHITECTURE.md, QUICKSTART.md |
| **Total** | **47** Dateien | |

---

## 🔗 Dependencies & Verknüpfungen

### Dependency Injection Flow

```
Controller
  ↓ injects
ApplicationService
  ↓ injects
DomainService + Repository
  ↓ uses
Entity + DTO
  ↓ persists
Infrastructure Repository
  ↓
Doctrine ORM
  ↓
Database
```

### Entity Relationships

```
User (1) ──── (1) PersonalData
PersonalData (N) ──── (1) ClassEntity
NotedChange (N) ──── (1) User
```

---

## ✅ Was wurde implementiert

### Entities
- ✅ User (mit Email, Passwort, Admin-Flag, Reset-Token)
- ✅ PersonalData (mit Name, Vorname, Klasse-Referenz)
- ✅ ClassEntity (mit Klassenname, iCal-Link)
- ✅ NotedChange (Gelesene Terminänderungen)

### Services (Domain Layer)
- ✅ UserService (Registrierung, Profil, Update)
- ✅ ClassService (CRUD für Klassen)
- ✅ CalendarService (Stundenplan, Änderungen)
- ✅ PasswordResetService (Reset-Token, Email)

### Application Services (Use Cases)
- ✅ UserApplicationService (Benutzer Use Cases)
- ✅ ClassApplicationService (Klassen Use Cases)
- ✅ CalendarApplicationService (Stundenplan Use Cases)
- ✅ PasswordApplicationService (Passwort-Reset)

### Repositories
- ✅ UserRepository
- ✅ PersonalDataRepository
- ✅ ClassRepository
- ✅ NotedChangeRepository

### Controllers (Stubs für OpenAPI-Generator)
- ✅ CalendarController (4 Endpoints)
- ✅ ClassController (5 Endpoints)
- ✅ UserController (6 Endpoints)

---

## 🚀 Nächste Schritte

1. **OpenAPI-Generator nutzen**
   ```bash
   openapi-generator-cli generate -i openapi.yaml -g php-symfony -o ./generated
   ```

2. **Migrations ausführen**
   ```bash
   php bin/console doctrine:migrations:migrate
   ```

3. **Event-Listener für Kalender-Updates**
   - Nach Klasse-Erstellen: kalenderupdater aufrufen
   - Nach Klasse-Update: Änderungstabelle leeren

4. **Security/Authentication**
   - Symfony Security Guards implementieren
   - JWT-Tokens (optional)

5. **Tests schreiben**
   - PHPUnit für Services
   - Integration Tests für Controller

6. **Logging & Monitoring**
   - Monolog konfigurieren
   - Error-Tracking (z.B. Sentry)

---

## 📖 File-by-File Übersicht

### Exceptions (src/Shared/Exception/)
- **EntityNotFoundException.php**: 404 Errors für nicht gefundene Entities
- **UnauthorizedException.php**: 401/403 für Authentifizierung/Autorisierung
- **ValidationException.php**: 400 für Validierungsfehler
- **ConflictException.php**: 409 für Duplikate/Konflikte

### Value Objects (src/Shared/ValueObject/)
- **Email.php**: Email-Validierung mit FILTER_VALIDATE_EMAIL

### User Domain (src/Domain/User/)
- **Entity/User.php**: Benutzer mit UUID, Email (unique), Passwort, Admin-Flag
- **Entity/PersonalData.php**: Name, Vorname, Klasse-Referenz
- **Service/UserService.php**: registerUser, getUserById, updateProfile, deleteUser
- **DTO/*.php**: DTOs für Kommunikation zwischen Schichten
- **Repository/*.php**: Interfaces für Persistierung

### Class Domain (src/Domain/Class/)
- **Entity/ClassEntity.php**: Klassenname (unique), iCal-Link
- **Service/ClassService.php**: CRUD Operationen mit Validierung
- **DTO/*.php**: ClassDTO, CreateClassDTO

### Calendar Domain (src/Domain/Calendar/)
- **Entity/NotedChange.php**: Benutzer-ID, Termin-ID
- **Service/CalendarService.php**: getCalendar, getChanges, getNotedChanges
- **DTO/*.php**: CalendarEventDTO, ChangeDTO, NotedChangeDTO

### Password Domain (src/Domain/Password/)
- **Service/PasswordResetService.php**: Token-Generierung, Email-Versand
- **DTO/*.php**: RequestPasswordResetDTO, ResetPasswordDTO

### Application Layer (src/Application/)
- **Shared/UserContext.php**: Kontext des aktuellen Benutzers
- ****/ApplicationService.php**: Orchestrierung der Use Cases

### Infrastructure (src/Infrastructure/Repository/)
- **UserRepository.php**: Doctrine-basierte User-Persistierung
- **PersonalDataRepository.php**: Doctrine-basierte PersonalData-Persistierung
- **ClassRepository.php**: Doctrine-basierte Class-Persistierung
- **NotedChangeRepository.php**: Doctrine-basierte NotedChange-Persistierung

### Presentation (src/Presentation/Http/Controller/)
- **CalendarController.php**: REST Endpoints für Stundenplan (GET, POST)
- **ClassController.php**: REST Endpoints für Klassen (GET, POST, PUT, DELETE)
- **UserController.php**: REST Endpoints für Benutzer (GET, POST, PUT)

### Configuration & Migrations
- **config/services.yaml**: Dependency Injection Setup
- **migrations/Version20260520000000.php**: Initiales DB-Schema

### Documentation
- **DDD_ARCHITECTURE.md**: Vollständige Architektur-Dokumentation
- **QUICKSTART.md**: Installation, Commands, Fehlerbehandlung

---

## 🎯 Design-Highlights

✨ **Separation of Concerns**: Jede Schicht hat klare Verantwortung
✨ **DDD Bounded Contexts**: Unabhängige, wartbare Domains
✨ **Testbarkeit**: Services verwenden Interfaces (mockbar)
✨ **Fehlerbehandlung**: Typisierte Exceptions mit HTTP-Codes
✨ **DTOs**: Type-Safe Datenübertragung
✨ **Doctrine ORM**: Flexible Persistierung mit Migrations
✨ **OpenAPI Ready**: Controllers vorbereitet für Code-Generator


