# Keycloak & JWT Authentication Setup Guide

## Overview

Diese Anleitung zeigt, wie Keycloak als Identity Provider mit dem Symfony Backend integriert wird. Der Flow ist:

```
Benutzer -> Keycloak (Login) -> JWT Token -> Symfony API
                                  (Validierung via JWKS)
                                  (User Auto-Provisioning)
```

---

## Phase 1: Keycloak Server Setup (Docker)

Keycloak läuft bereits über `docker-compose` auf Port **8080**.

### 1.1 Keycloak Console Zugriff

1. Öffne: **http://localhost:8080**
2. Login als Admin:
   - Username: `admin`
   - Password: `admin`

### 1.2 Realm erstellen

1. Klicke auf **Master Realm** (oben links) → **Create Realm**
2. Realm-Name: `bib-app`
3. Klicke **Create**

### 1.3 OpenID Connect Client erstellen

1. In Realm `bib-app`: Gehe zu **Clients** (linkes Menü)
2. Klicke **Create Client**
3. Client ID: `bib-app-backend`
4. Klicke **Next**
5. Aktiviere: **Client authentication** (Toggle An)
6. Aktiviere: **Authorization**
7. Klicke **Next**
8. Valid Redirect URIs: 
   ```
   http://localhost:8000/*
   http://localhost:3000/*
   ```
9. Klicke **Save**

### 1.4 Client Secret abrufen

1. Gehe zur `bib-app-backend` Client
2. Tab: **Credentials**
3. Copy **Client Secret**
4. Speichern für später: `KEYCLOAK_CLIENT_SECRET`

### 1.5 Realm Public Key abrufen

1. Gehe zu **Realm Settings** (linkes Menü)
2. Tab: **Keys**
3. Finder Algorithm `RSA-GENERATED` mit Status `Active`
4. Klicke auf **Public Key**
5. Copy den Key

---

## Phase 2: Backend Konfiguration

### 2.1 Umgebungsvariablen aktualisieren

In `.env.local` oder `.env`:

```dotenv
# OIDC/Keycloak Configuration
OIDC_ISSUER=http://localhost:8080/realms/bib-app
KEYCLOAK_CLIENT_ID=bib-app-backend
KEYCLOAK_CLIENT_SECRET=<client-secret-from-step-1.4>
```

### 2.2 Services registriert

Die folgenden Services sind bereits in `config/services.yaml` registriert:

- `App\Security\JwtAuthenticator` - Validiert JWT gegen Keycloak JWKS
- `App\Service\KeycloakUserProvisioningService` - Erstellt/aktualisiert User aus JWT Claims

### 2.3 Security Firewall konfiguriert

Die `config/packages/security.yaml` nutzt den Custom `JwtAuthenticator`:

```yaml
main:
    stateless: true
    custom_authenticators:
        - App\Security\JwtAuthenticator
```

---

## Phase 3: JWT Flow (Technisch)

### 3.1 Token Request (Client-seitig)

Der Frontend/Client sendet Keycloak-Credentials an `/realms/bib-app/protocol/openid-connect/token`:

```bash
curl -X POST http://localhost:8080/realms/bib-app/protocol/openid-connect/token \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "client_id=bib-app-backend" \
  -d "client_secret=<CLIENT_SECRET>" \
  -d "username=testuser" \
  -d "password=testpassword" \
  -d "grant_type=password"
```

**Response:**
```json
{
  "access_token": "eyJhbGc...",
  "token_type": "Bearer",
  "expires_in": 300,
  "refresh_token": "..."
}
```

### 3.2 API Request (mit JWT)

Client sendet GET/POST an Symfony API mit Bearer Token:

```bash
curl -X GET http://localhost:8000/api/profile/me \
  -H "Authorization: Bearer eyJhbGc..."
```

### 3.3 Backend Validierung

1. `JwtAuthenticator` extrahiert Bearer Token aus Header
2. Fetcht Keycloak JWKS (`/.well-known/jwks.json`)
3. Validiert Token-Signatur gegen JWKS
4. Extrahiert Claims (`sub`, `email`, `given_name`, `family_name`, `realm_access.roles`)
5. Ruft `KeycloakUserProvisioningService` auf → erstellt/aktualisiert User
6. `BenutzerProvider` lädt User aus Datenbank
7. Request wird mit `$this->getUser()` verarbeitet

---

## Phase 4: User Provisioning & Roles

### 4.1 Automatische User-Erstellung

Wenn ein JWT-Token von Keycloak kommt und der User nicht in der DB existiert:

1. `Benutzer` wird erstellt mit `identityId` = JWT `sub`-Claim
2. `PersoenlicheDaten` wird erstellt mit Name/Vorname aus JWT
3. Rollen werden aus JWT extrahiert und gespeichert

**JWT Claims Mapping:**

| JWT Claim | Symfony/DB Feld |
|-----------|-----------------|
| `sub` | `Benutzer.identityId` |
| `email` | `Benutzer.email` |
| `given_name` | `PersoenlicheDaten.vorname` |
| `family_name` | `PersoenlicheDaten.name` |
| `realm_access.roles` | `Benutzer.roles` (ROLE_ Prefix) |

### 4.2 Rollen in Keycloak konfigurieren

1. In Realm `bib-app`: Gehe zu **Roles** (linkes Menü)
2. Klicke **Create Role**
3. Role Name: `admin` (oder andere)
4. Klicke **Save**
5. Repeat für weitere Rollen: `teacher`, `student`, etc.

### 4.3 User Rollen zuweisen

1. Gehe zu **Users** → finde User
2. Tab: **Role mapping**
3. **Assign role** → wähle Rollen
4. Klicke **Assign**

---

## Phase 5: Testing

### 5.1 Unit Tests (lokal)

```bash
php bin/phpunit tests/Integration/KeycloakAuthTest.php
```

Tests:
- User Provisioning von JWT Claims
- Role Extraction (Keycloak + Cognito Format)
- BenutzerProvider loads provisioned users

### 5.2 Integration Tests (mit Docker)

```bash
docker exec timetable-php-1 php bin/phpunit tests/Integration/KeycloakAuthTest.php
```

### 5.3 Manual Test mit curl

1. **Starte den Server:**
   ```bash
   docker exec timetable-php-1 php -S 127.0.0.1:8000 -t public
   ```

2. **Token von Keycloak abrufen (als Admin):**
   ```bash
   curl -X POST http://localhost:8080/realms/bib-app/protocol/openid-connect/token \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "client_id=bib-app-backend" \
     -d "client_secret=<SECRET>" \
     -d "username=admin" \
     -d "password=admin" \
     -d "grant_type=password"
   ```

3. **Testet Protected Endpoint:**
   ```bash
   curl -X GET http://localhost:8000/api/profile/me \
     -H "Authorization: Bearer <ACCESS_TOKEN>"
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

## Phase 6: Migration zu AWS Cognito (später)

Die `JwtAuthenticator` ist abstrakt genug, um auch mit AWS Cognito zu arbeiten:

### 6.1 Cognito OIDC Issuer

```dotenv
# Für Production (AWS Cognito)
OIDC_ISSUER=https://cognito-idp.eu-central-1.amazonaws.com/eu-central-1_xxxxx
```

### 6.2 JWT Claims (Cognito Format)

Cognito nutzt andere Rollen-Claims:

```json
{
  "sub": "user-uuid",
  "email": "user@example.com",
  "cognito:groups": ["admin", "teachers"]
}
```

Die `KeycloakUserProvisioningService` handled beide Formate automatisch!

---

## Common Issues

### Issue 1: "Invalid token issuer"
- **Ursache:** OIDC_ISSUER in `.env` stimmt nicht mit JWT-Token überein
- **Fix:** Überprüfe Keycloak `Realm Settings` → **General** → **Frontend URL**

### Issue 2: "Failed to fetch JWKS"
- **Ursache:** Keycloak Server nicht erreichbar
- **Fix:** Überprüfe `docker-compose ps` und `docker-compose logs keycloak`

### Issue 3: "User not found after provisioning"
- **Ursache:** User wurde nicht erstellt
- **Fix:** Überprüfe Logs: `docker-compose logs php` → sollte "User provisioned successfully" zeigen

### Issue 4: "Token has expired"
- **Ursache:** JWT TTL zu kurz (Standard 5 min)
- **Fix:** Neue Token anfordern oder TTL in Keycloak erhöhen

---

## Files Created

- `src/Security/JwtAuthenticator.php` - JWT Validation gegen Keycloak JWKS
- `src/Service/KeycloakUserProvisioningService.php` - User Auto-Provisioning
- `tests/Integration/KeycloakAuthTest.php` - Integration Tests
- `config/packages/security.yaml` - Updated mit Custom Authenticator
- `config/services.yaml` - Registered Services

## Environment Variables Required

```
OIDC_ISSUER=http://localhost:8080/realms/bib-app
KEYCLOAK_CLIENT_ID=bib-app-backend
KEYCLOAK_CLIENT_SECRET=<from-keycloak-console>
```

---

## Next Steps

1. ✅ Keycloak Server Setup (Docker - läuft)
2. ✅ JWT Authenticator & Provisioning (Code - erstellt)
3. ⏳ **Manual Keycloak Realm/Client Setup** (siehe Phase 1)
4. ⏳ Tests ausführen & validieren
5. ⏳ Frontend-Integration (Token Request)
6. ⏳ Cognito Migration (später)

