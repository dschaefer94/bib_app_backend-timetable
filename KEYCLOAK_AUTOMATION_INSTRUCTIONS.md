# Keycloak Automation (REST) - Step-by-step Instructions

This document explains how the automation script `scripts/create_keycloak_realm.ps1` works and how to run it manually elsewhere.

Prerequisites
- Keycloak reachable at http://localhost:8080 (running in Docker or elsewhere)
- Admin credentials (default dev bootstrap: `admin` / `admin`)
- PowerShell (Windows PowerShell 5.1 or PowerShell 7+)

Files
- `scripts/create_keycloak_realm.ps1` - PowerShell script that:
  - obtains an admin token
  - creates realm `bib-app` if missing
  - creates client `bib-app-backend` if missing
  - fetches client secret
  - writes `KEYCLOAK_AUTOMATION_RESULT.md` and `.env.local`

How it works (high level)
1. Calls Keycloak token endpoint:
   `POST /realms/master/protocol/openid-connect/token` with `grant_type=password`, `client_id=admin-cli`, `username=admin`, `password=admin`
2. Uses returned `access_token` for Admin REST API calls
3. Checks presence of realm `bib-app` (GET `/admin/realms/bib-app`)
4. Creates realm if missing (POST `/admin/realms`)
5. Creates client `bib-app-backend` in the realm (POST `/admin/realms/{realm}/clients`)
6. Fetches client details by clientId (GET `/admin/realms/{realm}/clients?clientId=bib-app-backend`)
7. Retrieves client secret (GET `/admin/realms/{realm}/clients/{id}/client-secret`)
8. Writes `.env.local` and `KEYCLOAK_AUTOMATION_RESULT.md`

Running the script locally
1. Open PowerShell in this project root (where `docker-compose.yaml` is located).
2. Run:

```powershell
# Make script executable if needed
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass

# Run the script
.\scripts\create_keycloak_realm.ps1
```

If Keycloak runs on a different host/port, open the script and change the `$baseUrl` variable at the top accordingly.

Manual REST commands (for learning / debugging)

1) Get admin token (example with curl):

```bash
curl -X POST "http://localhost:8080/realms/master/protocol/openid-connect/token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "grant_type=password&username=admin&password=admin&client_id=admin-cli"
```

2) Check if realm exists (example with curl):

```bash
curl -H "Authorization: Bearer <ADMIN_TOKEN>" http://localhost:8080/admin/realms/bib-app
```

3) Create realm:

```bash
curl -X POST -H "Authorization: Bearer <ADMIN_TOKEN>" -H "Content-Type: application/json" \
  -d '{"realm":"bib-app","enabled":true}' \
  http://localhost:8080/admin/realms
```

4) Create client:

```bash
curl -X POST -H "Authorization: Bearer <ADMIN_TOKEN>" -H "Content-Type: application/json" \
  -d '{"clientId":"bib-app-backend","enabled":true,"publicClient":false,"protocol":"openid-connect","redirectUris":["http://localhost:8000/*","http://localhost:3000/*"],"directAccessGrantsEnabled":true,"standardFlowEnabled":true}' \
  http://localhost:8080/admin/realms/bib-app/clients
```

5) Get client id & secret:

```bash
# Find client internal id
curl -H "Authorization: Bearer <ADMIN_TOKEN>" "http://localhost:8080/admin/realms/bib-app/clients?clientId=bib-app-backend"

# Then (replace {id})
curl -H "Authorization: Bearer <ADMIN_TOKEN>" http://localhost:8080/admin/realms/bib-app/clients/{id}/client-secret
```

Notes & Troubleshooting
- If the script fails with 401 when calling Admin endpoints, ensure the admin token was obtained (check credentials) and the admin user is bootstraped.
- If Keycloak uses a non-default admin client or different auth method, adapt the token request.


