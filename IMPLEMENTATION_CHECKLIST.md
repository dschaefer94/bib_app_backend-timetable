# Implementation Checklist & Test Plan

## ✅ Implemented Components

### 1. Custom JWT Authenticator
- **File:** `src/Security/JwtAuthenticator.php`
- **Funktionalität:**
  - Extrahiert JWT aus `Authorization: Bearer` Header
  - Validiert Token gegen Keycloak JWKS (`.well-known/jwks.json`)
  - Überprüft Token Issuer, Expiration
  - Konvertiert JWT Claims in User-Objekt
  - JWKS Caching (1 Stunde)
  - Error Handling mit Problem+JSON Response

### 2. User Provisioning Service
- **File:** `src/Service/KeycloakUserProvisioningService.php`
- **Funktionalität:**
  - Erstellt neue `Benutzer` aus JWT `sub`-Claim
  - Erstellt `PersoenlicheDaten` mit Name/Vorname
  - Aktualisiert existierende User mit neuesten Daten
  - Extrahiert Rollen aus JWT (Keycloak + Cognito Format)
  - Logging für Audit-Trail

### 3. Rolle Extraction (Multi-Format)
**Keycloak Realm Roles:**
```json
{
  "realm_access": {
    "roles": ["admin", "user"]
  }
}
```
→ Konvertiert zu: `["ROLE_ADMIN", "ROLE_USER"]`

**Keycloak Client Roles:**
```json
{
  "resource_access": {
    "bib-app-backend": {
      "roles": ["teacher", "student"]
    }
  }
}
```
→ Konvertiert zu: `["ROLE_TEACHER", "ROLE_STUDENT"]`

**AWS Cognito Gruppen:**
```json
{
  "cognito:groups": ["admins", "teachers"]
}
```
→ Konvertiert zu: `["ROLE_ADMINS", "ROLE_TEACHERS"]`

### 4. Integration Tests
- **File:** `tests/Integration/KeycloakAuthTest.php`
- **Tests:**
  - `testUserProvisioningFromJwtClaims()` - JWT → Benutzer
  - `testUserProvisioningUpdatesExistingUser()` - Update Scenario
  - `testUserProvisioningFromCognitoJwtClaims()` - Cognito Format
  - `testUserProvisioningMissingSub()` - Error Handling
  - `testBenutzerProviderLoadsProvisionedUser()` - Provider Integration

### 5. Configuration Updates
- **`config/packages/security.yaml`** - JWT Authenticator enabled
- **`config/services.yaml`** - Services registered
- **`.env`** - OIDC_ISSUER configured
- **`Dockerfile`** - PHP Server statt Symfony Server

---

## 📋 Next Manual Steps

### Step 1: Keycloak Realm Setup (Manual)

1. **Access Keycloak Admin Console**
   - URL: http://localhost:8080
   - Username: admin
   - Password: admin

2. **Create Realm**
   - Click "Master" dropdown
   - "Create Realm"
   - Name: `bib-app`
   - Click "Create"

3. **Create OpenID Connect Client**
   - Menu: Clients → Create Client
   - Client ID: `bib-app-backend`
   - Next
   - Toggle "Client authentication": ON
   - Toggle "Authorization": ON
   - Next
   - Valid Redirect URIs:
     ```
     http://localhost:8000/*
     http://localhost:3000/*
     ```
   - Save

4. **Get Client Secret**
   - Client: bib-app-backend
   - Tab: Credentials
   - Copy: Client Secret
   - **Save this!** → `KEYCLOAK_CLIENT_SECRET`

### Step 2: Environment Configuration

In `.env.local`:
```dotenv
OIDC_ISSUER=http://localhost:8080/realms/bib-app
KEYCLOAK_CLIENT_ID=bib-app-backend
KEYCLOAK_CLIENT_SECRET=<paste-from-step-1.4>
```

### Step 3: Database Setup (in Docker)

```bash
docker exec timetable-php-1 php bin/console doctrine:migrations:migrate --no-interaction
docker exec timetable-php-1 php bin/console doctrine:fixtures:load --no-interaction
```

### Step 4: Run Unit Tests

```bash
# Local (outside Docker)
php bin/phpunit tests/Integration/KeycloakAuthTest.php

# Inside Docker
docker exec timetable-php-1 php bin/phpunit tests/Integration/KeycloakAuthTest.php
```

**Expected Output:**
```
PHPUnit 13.1.13 by Sebastian Bergmann
......                                                                    6 / 6 (100%)

OK (6 tests, 12 assertions)
```

### Step 5: Manual Integration Test

**Terminal 1 - Start PHP Server:**
```bash
docker exec -it timetable-php-1 php -S 127.0.0.1:8000 -t public
```

**Terminal 2 - Get JWT Token:**
```bash
curl -X POST http://localhost:8080/realms/bib-app/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=bib-app-backend" \
  -d "client_secret=<SECRET>" \
  -d "username=admin" \
  -d "password=admin" \
  -d "grant_type=password"
```

**Copy:** `access_token` value

**Terminal 2 - Call Protected Endpoint:**
```bash
curl -X GET http://localhost:8000/api/profile/me \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "Content-Type: application/json"
```

**Expected Response (200 OK):**
```json
{
  "identityId": "550e8400-e29b-41d4-a716-446655440000",
  "email": "admin@keycloak.local",
  "isAdmin": false,
  "roles": ["ROLE_USER"]
}
```

---

## 🔧 Troubleshooting

### Error: "Cannot connect to Keycloak"
```
Error: HTTP error validating JWT: Failed to validate JWT token
```
**Fix:** Überprüfe `OIDC_ISSUER` in `.env`

```bash
# Keycloak Status
docker-compose logs keycloak | grep -i "started"

# Keycloak erreichbar?
curl http://localhost:8080/realms/bib-app/.well-known/openid-configuration
```

### Error: "No JWT token provided"
```
Error: No JWT token provided
```
**Fix:** Request mit korrektem Header senden:
```bash
curl ... -H "Authorization: Bearer <TOKEN>"
```

### Error: "Token signature verification failed"
```
Error: Token signature verification failed
```
**Fix:** Überprüfe OIDC_ISSUER matches JWT `iss` claim:
```bash
# Decode JWT (ohne Verifikation)
# Nutze https://jwt.io und paste token
# Überprüfe "iss" claim entspricht OIDC_ISSUER
```

### Error: "User not found after provisioning"
```
Error: User with identityId 'xxx' not found after provisioning
```
**Fix:** Überprüfe Logs:
```bash
docker-compose logs php | grep "provisioned\|error"
```

---

## 📊 Testing Scenarios

### Scenario 1: New User (Auto-Provisioning)
1. Token mit neuem `sub` anfordern → User wird erstellt
2. API-Call mit Token → `$this->getUser()` gibt Benutzer zurück
3. DB-Query zeigt neuen Benutzer mit `identityId`, `email`, `PersoenlicheDaten`

### Scenario 2: Existing User (Update)
1. Token mit existierendem `sub` anfordern
2. JWT Claims haben neue `email` → wird aktualisiert
3. API-Call → User-Daten sind aktuell

### Scenario 3: User mit Rollen
1. Keycloak User hat Rollen `admin`, `teacher` zugewiesen
2. JWT enthält `realm_access.roles`: `["admin", "teacher"]`
3. Benutzer.roles in DB: `["ROLE_ADMIN", "ROLE_TEACHER"]`

### Scenario 4: Invalid Token
1. Expired Token → 401 Unauthorized
2. Wrong Signature → 401 Unauthorized
3. Missing `sub` → Provisioning Error → 401

---

## 🚀 Ready for Frontend Integration

Die API ist nun bereit für Frontend-Integration:

**Frontend muss:**
1. Keycloak Token anfordern (oder Keycloak-Adapter nutzen)
2. JWT in `Authorization: Bearer` Header senden
3. Backend validiert und provisioned User automatisch

**Keine weiteren Änderungen am Backend nötig für:**
- React/Vue/Angular Frontend
- Mobile Apps (iOS/Android)
- Third-party Services

---

## 📝 Files Summary

| Datei | Beschreibung |
|-------|-------------|
| `src/Security/JwtAuthenticator.php` | JWT Validierung & Extraction |
| `src/Service/KeycloakUserProvisioningService.php` | Auto User-Provisioning |
| `tests/Integration/KeycloakAuthTest.php` | Integration Tests |
| `config/packages/security.yaml` | Security Firewall Config |
| `config/services.yaml` | Service Registrierung |
| `KEYCLOAK_SETUP.md` | Keycloak Admin Setup Anleitung |
| `Dockerfile` | Updated für PHP Built-in Server |
| `.env` | OIDC_ISSUER Configuration |

---

## ✨ Nächste Features (Optional)

1. **Refresh Token Handling** - Token Rotation
2. **Role-Based Access Control (RBAC)** - Granulare Permissions
3. **User Logout** - Token Blacklisting
4. **Social Login** - Google/GitHub Integration in Keycloak
5. **MFA** - Multi-Factor Authentication in Keycloak
6. **API Scopes** - Fine-grained OAuth2 Scopes

