# TODO: Keycloak Integration Tasks

Kurzfristige Aufgaben (Priorität hoch)

- [ ] Keycloak Admin Console erreichbar machen (http://localhost:8080)
  - Ursache prüfen: Container-Status & Logs
  - Falls DB-Verbindung fehlschlägt: Keycloak konfigurationswerte auf vorhandene Postgres-Credentials anpassen
- [ ] Realm `bib-app` anlegen
- [ ] Client `bib-app-backend` anlegen (OIDC, confidential)
- [ ] Client Secret notieren und in `.env.local` setzen
- [ ] Testuser in Keycloak anlegen (z.B. `admin:admin`) oder temporären Benutzer verwenden
- [ ] Test-Token per CURL anfordern (Resource Owner Password for quick local test)
- [ ] API-Aufruf gegen `http://localhost:8000/api/profile/me` mit Bearer-Token testen

Mittelfristige Aufgaben (nach Erreichbarkeit)

- [ ] Rollen (Realm/Client) anlegen: `admin`, `teacher`, `student`
- [ ] Testdaten in Keycloak einpflegen und Rollen zuweisen
- [ ] Feinabstimmung: role‑mapping → Symfony `ROLE_*`
- [ ] Optional: Persistente Keycloak DB (separates DB oder init.sql erweitern)

Langfristig / optional

- [ ] Integrationstest für End‑to‑End Authentifizierung (Keycloak live)
- [ ] Einrichtung eines separaten Keycloak DB-Users via `init.sql` (für Produktionsnähe)
- [ ] Migration-Pfad zu AWS Cognito vorbereiten (OIDC_ISSUER wechselbar)

---

Datei automatisch erzeugt am: 2026-06-23

