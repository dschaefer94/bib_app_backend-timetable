# ✅ Implementierungs-Checkliste

## 📋 Vor dem Start

- [ ] PHP 8.0+ installiert (`php -v`)
- [ ] Composer installiert (`composer --version`)
- [ ] MySQL/MariaDB läuft
- [ ] Editor/IDE mit PHP-Support
- [ ] Git konfiguriert (optional)

## 🚀 Phase 1: Projekt Setup (1-2 Stunden)

### Symfony Installation
- [ ] Neues Symfony-Projekt erstellen
  ```bash
  symfony new bib-app --full
  cd bib-app
  ```
- [ ] Komposer-Dependencies installieren
  ```bash
  composer require doctrine/orm doctrine/migrations
  composer require ramsey/uuid
  composer require symfony/mailer
  ```
- [ ] Optional: Dev-Dependencies
  ```bash
  composer require --dev symfony/debug-bundle
  composer require --dev phpunit/phpunit
  ```

### Dateistruktur
- [ ] Alle `src/` Ordner kopieren
- [ ] `config/services.yaml` kopieren/zusammenführen
- [ ] `migrations/` Ordner kopieren
- [ ] `openapi.yaml` kopieren

### Umgebung
- [ ] `.env.local` erstellen
  ```
  DATABASE_URL="mysql://user:password@127.0.0.1:3306/bib_app"
  MAILER_DSN="smtp://localhost:1025"
  APP_ENV=dev
  APP_DEBUG=1
  ```
- [ ] Datenbankverbindung testen
  ```bash
  php bin/console doctrine:database:create
  ```

## 🗄️ Phase 2: Datenbank Setup (30 Minuten)

### Migrations
- [ ] Migrations-Status prüfen
  ```bash
  php bin/console doctrine:migrations:status
  ```
- [ ] Migrations ausführen
  ```bash
  php bin/console doctrine:migrations:migrate
  ```
- [ ] Schema validieren
  ```bash
  php bin/console doctrine:schema:validate
  ```

### Test-Daten (optional)
- [ ] DummyKlasse prüfen (sollte ID=1 sein)
- [ ] Test-User erstellen (manuell oder via Fixture)

## 🌐 Phase 3: API & OpenAPI-Generator (1-2 Stunden)

### OpenAPI-Generator Setup
- [ ] `openapi-generator-cli` installieren
  ```bash
  npm install -g @openapitools/openapi-generator-cli
  ```
- [ ] OpenAPI-Generator testen
  ```bash
  openapi-generator-cli version
  ```

### Controller Generierung
- [ ] OpenAPI-Config erstellen (optional: `openapi-config.json`)
- [ ] Controller generieren
  ```bash
  openapi-generator-cli generate \
    -i openapi.yaml \
    -g php-symfony \
    -o ./generated
  ```
- [ ] Generierte Controller in `src/Presentation/Http/Controller/` integrieren
- [ ] Imports anpassen (auf Application Services)
- [ ] Routes registrieren (falls nicht auto-generiert)

### Testing
- [ ] Server starten
  ```bash
  symfony server:start
  ```
- [ ] Endpoints mit Postman/Insomnia testen
  ```bash
  GET http://localhost:8000/api/classes
  ```

## 🔐 Phase 4: Security & Authentication (1-2 Stunden)

### Security Bundle
- [ ] Symfony Security Bundle konfigurieren
  ```yaml
  # config/packages/security.yaml
  security:
    providers:
      app:
        entity:
          class: App\Domain\User\Entity\User
          property: email
  ```
- [ ] Firewall konfigurieren (stateless für API)
- [ ] Access Control einrichten

### UserContext Integration
- [ ] `UserContextListener` erstellen
  ```php
  // Füllt UserContext aus Symfony Security
  ```
- [ ] In `services.yaml` registrieren
- [ ] Testen mit authenticated Requests

### Password Hashing
- [ ] Hasher konfigurieren
  ```yaml
  # config/packages/security.yaml
  password_hashers:
    App\Domain\User\Entity\User: bcrypt
  ```

## 📅 Phase 5: Kalender-Integration (2-3 Stunden)

### KalenderAdapter Setup
- [ ] Alten Kalender-Code analysieren
- [ ] `KalenderAdapter::updateCalendar()` implementieren
- [ ] Mit bestehendem `kalenderrunner.php` verknüpfen

### Event Listeners
- [ ] `ClassEntityListener` aktivieren
- [ ] POST_PERSIST Event: Kalender aktualisieren
- [ ] POST_UPDATE Event: Änderungstabelle leeren
- [ ] Testen: Neue Klasse erstellen, Tabellen prüfen

### Dynamic Tables
- [ ] Bestätigen dass Tabellen erstellt werden:
  - `{klassenname}_alter_stundenplan`
  - `{klassenname}_neuer_stundenplan`
  - `{klassenname}_aenderungen`

## 🧪 Phase 6: Testing (1-2 Stunden)

### Unit Tests
- [ ] Testverzeichnis `tests/` erstellen
- [ ] UserServiceTest schreiben
- [ ] ClassServiceTest schreiben
- [ ] CalendarServiceTest schreiben
- [ ] Tests ausführen
  ```bash
  php bin/phpunit
  ```

### Integration Tests
- [ ] WebTestCase für Controller schreiben
- [ ] API Endpoints testen
- [ ] Authentication testen
- [ ] Error Cases testen

### Code Coverage
- [ ] Coverage Report generieren
  ```bash
  php bin/phpunit --coverage-html coverage/
  ```
- [ ] Ziel: > 80% Coverage

## 📖 Phase 7: Dokumentation & Deployment (1 Stunde)

### Code-Dokumentation
- [ ] README.md updaten
- [ ] API-Dokumentation aktualisieren
- [ ] Deployment-Guide schreiben

### Production Setup
- [ ] Production `.env` erstellen
  ```
  APP_ENV=prod
  APP_DEBUG=0
  ```
- [ ] Cache aufwärmen
  ```bash
  php bin/console cache:warmup --env=prod
  ```
- [ ] Migrations in Production durchführen
- [ ] Log-Verzeichnis prüfen

### Monitoring
- [ ] Error Logging konfigurieren
- [ ] Performance Monitoring setup (optional)
- [ ] Backup-Strategie definieren

## ✅ Go-Live Checklist

### Functionality
- [ ] Alle 10+ Endpoints funktionieren
- [ ] Registration/Login funktioniert
- [ ] Kalender wird aktualisiert
- [ ] Profil kann aktualisiert werden
- [ ] Passwort-Reset funktioniert
- [ ] Benutzer kann Klasse wechseln
- [ ] Admin kann Klasse löschen

### Quality
- [ ] Tests bestehen > 80%
- [ ] Code Review durchgeführt
- [ ] Performance akzeptabel (< 500ms)
- [ ] Memory Usage OK (< 100MB)
- [ ] Database Indizes optimiert

### Security
- [ ] HTTPS konfiguriert
- [ ] CORS konfiguriert (falls nötig)
- [ ] CSRF Protection aktiv
- [ ] Passwords gehashed
- [ ] Sensitive Data geloggt? Nein!
- [ ] SQL Injection nicht möglich (ORM)

### Operations
- [ ] Datenbank Backups funktionieren
- [ ] Error Logging aktiv
- [ ] Monitoring aktiv
- [ ] Runbooks dokumentiert
- [ ] Support-Kontakt definiert

## 🐛 Troubleshooting Checklist

Wenn etwas nicht funktioniert:

### Database Issues
- [ ] Datenbankverbindung prüfen
  ```bash
  php bin/console doctrine:query:sql "SELECT 1"
  ```
- [ ] Migrations-Status prüfen
  ```bash
  php bin/console doctrine:migrations:status
  ```
- [ ] Schema validieren
  ```bash
  php bin/console doctrine:schema:validate
  ```

### Routing Issues
- [ ] Routes auflisten
  ```bash
  php bin/console debug:router
  ```
- [ ] Controller korrekt registriert?
- [ ] Imports korrekt?

### DI Issues
- [ ] Container debuggen
  ```bash
  php bin/console debug:container
  ```
- [ ] Services registriert?
- [ ] Dependencies korrekt?

### Permission Issues
- [ ] var/ Verzeichnis beschreibbar?
  ```bash
  chmod -R 777 var/
  ```
- [ ] Logs Verzeichnis beschreibbar?

## 📚 Dokumentation Referenzen

- [ ] README.md gelesen
- [ ] FINAL_SUMMARY.md gelesen
- [ ] DDD_ARCHITECTURE.md verstanden
- [ ] QUICKSTART.md durchgearbeitet
- [ ] MIGRATION_GUIDE.md als Referenz

## 🎯 Finale Schritte

- [ ] Deployment durchführen
- [ ] Smoke Tests durchführen
- [ ] User Training durchführen
- [ ] Go-Live durchführen
- [ ] Support Ticket System aufsetzen
- [ ] Post-Launch Monitoring

---

## 📊 Zeitabschätzung

| Phase | Aufwand | Status |
|-------|---------|--------|
| 1. Setup | 1-2h | ⬜ TODO |
| 2. Datenbank | 30min | ⬜ TODO |
| 3. API | 1-2h | ⬜ TODO |
| 4. Security | 1-2h | ⬜ TODO |
| 5. Kalender | 2-3h | ⬜ TODO |
| 6. Testing | 1-2h | ⬜ TODO |
| 7. Doku | 1h | ⬜ TODO |
| **Total** | **7-12h** | ⬜ TODO |

---

## 🎉 Fertig!

Wenn alle Häkchen gesetzt sind, ist das Projekt produktionsreif! 🚀


