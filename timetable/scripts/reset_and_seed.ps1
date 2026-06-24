<#
reset_and_seed.ps1

Skript, das lokal (PowerShell) die Compose-Umgebung zurücksetzt, Volumes entfernt, Container neu startet,
Migrationen & Fixtures einspielt, Keycloak Realm + Demo-User anlegt, Token holt und /api/profile/me testet.

Benutzung (ausführen im Windows PowerShell):
  Set-Location 'C:\bib\bib-App\bib_app\backends\timetable\timetable'
  .\scripts\reset_and_seed.ps1

Achtung: dieses Skript löscht die Docker-Volumes (DB + Keycloak) und ist destruktiv.
#>

param(
    [switch]$NoKeycloakScripts,
    [int]$DbHealthTimeoutSec = 180,
    [int]$KeycloakTimeoutSec = 120
)

$ErrorActionPreference = 'Stop'
$scriptStart = Get-Date
$logPath = Join-Path -Path (Get-Location) -ChildPath "tmp\reset_and_seed_$(Get-Date -Format 'yyyyMMdd_HHmmss').log"
# Ensure tmp dir exists
New-Item -ItemType Directory -Path (Join-Path (Get-Location) 'tmp') -ErrorAction SilentlyContinue | Out-Null

function Log { param($m) $ts = (Get-Date).ToString('o'); "$ts`t$m" | Tee-Object -FilePath $logPath -Append }

Log "Starting reset_and_seed.ps1"

# Ensure we are in repo root (where docker-compose.yaml is)
$repoRoot = Get-Location
Log "PWD: $repoRoot"

# Detect docker CLI command to use
$dcCmd = $null
if (Get-Command docker-compose -ErrorAction SilentlyContinue) { $dcCmd = 'docker-compose' }
elseif (Get-Command docker -ErrorAction SilentlyContinue) { $dcCmd = 'docker compose' }
else { Log 'ERROR: Keinen docker/docker-compose CLI-Befehl gefunden in PATH.'; throw 'docker CLI missing' }
Log "Using docker command: $dcCmd"

# Down (remove volumes)
Log 'Bringing compose down (removing volumes)...'
try {
    iex "$dcCmd down -v --remove-orphans" | Tee-Object -FilePath $logPath -Append
    Log 'Compose down completed.'
} catch {
    Log "WARN: docker compose down failed: $_"
}

# Up
Log 'Starting compose up -d'
try {
    iex "$dcCmd up -d" | Tee-Object -FilePath $logPath -Append
    Log 'Compose up completed.'
} catch {
    Log "ERROR: docker compose up failed: $_"
    throw $_
}

# Wait for DB health
Log "Waiting for database health (timeout ${DbHealthTimeoutSec}s)..."
$max = $DbHealthTimeoutSec / 2; $i = 0; $dbHealthy = $false
while ($i -lt $max) {
    try {
        $dbCid = (& $dcCmd ps -q database) -replace '\s','' 2>$null
    } catch { $dbCid = '' }
    if (-not $dbCid) { try { $dbCid = (docker ps --filter 'name=database' --quiet) -replace '\s','' } catch { $dbCid = '' } }
    if ($dbCid) {
        try { $st = docker inspect --format '{{.State.Health.Status}}' $dbCid 2>$null } catch { $st = '' }
        Log "  DB container health: $st"
        if ($st -eq 'healthy') { $dbHealthy = $true; break }
    } else {
        Log '  DB container id not available yet'
    }
    Start-Sleep -Seconds 2; $i++
}
if (-not $dbHealthy) { Log 'WARNING: DB not healthy within timeout. Continuing may fail.' }
else { Log 'Database reports healthy.' }

# Wait for Keycloak HTTP
Log "Waiting for Keycloak HTTP (http://localhost:8080) (timeout ${KeycloakTimeoutSec}s)..."
$max = [int]($KeycloakTimeoutSec / 2); $i = 0; $kcOk = $false
while ($i -lt $max) {
    try {
        $r = Invoke-WebRequest -Uri 'http://localhost:8080' -UseBasicParsing -TimeoutSec 3
        if ($r.StatusCode -eq 200) { $kcOk = $true; break }
    } catch { }
    Start-Sleep -Seconds 2; $i++
}
if (-not $kcOk) { Log 'WARNING: Keycloak not responding in time.' } else { Log 'Keycloak HTTP is reachable.' }

# Run migrations in php container
Log 'Running doctrine:migrations:migrate inside php container...'
try {
    iex "$dcCmd exec -T php php bin/console doctrine:migrations:migrate --no-interaction" | Tee-Object -FilePath $logPath -Append
    Log 'Migrations finished.'
} catch {
    Log "ERROR: Migrations failed: $_"
    # Still attempt fixtures? fail here
    throw $_
}

# Load fixtures
Log 'Loading fixtures...'
try {
    iex "$dcCmd exec -T php php bin/console doctrine:fixtures:load --no-interaction" | Tee-Object -FilePath $logPath -Append
    Log 'Fixtures loaded.'
} catch {
    Log "ERROR: Fixtures load failed: $_"
    throw $_
}

# Clear cache
Log 'Clearing Symfony cache...'
try { iex "$dcCmd exec -T php php bin/console cache:clear" | Tee-Object -FilePath $logPath -Append; Log 'Cache cleared.' } catch { Log 'WARN: cache:clear failed' }

# Run Keycloak automation scripts (unless suppressed)
Set-Location -Path (Join-Path $repoRoot 'scripts')
Log "PWD: $PWD"
if (-not $NoKeycloakScripts) {
    Log 'Running create_keycloak_realm.ps1'
    try { .\create_keycloak_realm.ps1 2>&1 | Tee-Object -FilePath $logPath -Append; Log 'create_keycloak_realm finished.' } catch { Log "ERROR: create_keycloak_realm failed: $_"; throw $_ }
    Log 'Running create_keycloak_demo_users.ps1'
    try { .\create_keycloak_demo_users.ps1 2>&1 | Tee-Object -FilePath $logPath -Append; Log 'create_keycloak_demo_users finished.' } catch { Log "ERROR: create_keycloak_demo_users failed: $_"; throw $_ }
} else { Log 'Skipping Keycloak scripts as requested.' }

# Get token for studentuser
Log 'Getting token for studentuser with get_token.ps1'
try { .\get_token.ps1 -User studentuser 2>&1 | Tee-Object -FilePath $logPath -Append; Log 'get_token finished.' } catch { Log "ERROR: get_token failed: $_"; throw $_ }

# Read token and test API endpoint
$envFile = Join-Path $repoRoot 'http-client.private.env.json'
if (-not (Test-Path $envFile)) { Log "ERROR: $envFile not found"; throw 'env file missing' }
$envJson = Get-Content $envFile -Raw | ConvertFrom-Json
$token = $envJson.dev.token
if (-not $token) { Log 'ERROR: token not found in http-client.private.env.json'; throw 'token missing' }
Log "Token length: $($token.Length)"

Log 'Testing GET /api/profile/me'
try {
    $resp = Invoke-RestMethod -Method Get -Uri 'http://localhost:8000/api/profile/me' -Headers @{ Authorization = "Bearer $token"; Accept='application/json' } -UseBasicParsing -TimeoutSec 15
    $json = $resp | ConvertTo-Json -Depth 8
    Log "API response: $json"
    Write-Host "SUCCESS: /api/profile/me returned (see log: $logPath)"
} catch {
    Log "ERROR: API request failed: $_"
    # collect logs
    Log 'Collecting php and keycloak logs (tail 200)'
    try { iex "$dcCmd logs php --tail 200" | Tee-Object -FilePath $logPath -Append } catch { Log 'Failed to collect php logs' }
    try { iex "$dcCmd logs keycloak --tail 200" | Tee-Object -FilePath $logPath -Append } catch { Log 'Failed to collect keycloak logs' }
    Write-Host "FAIL: /api/profile/me failed. Logs written to $logPath"
    exit 1
}

$runtime = (Get-Date) - $scriptStart
Log "Completed reset_and_seed in $($runtime.TotalSeconds) seconds"
Write-Host "Done. Log: $logPath"

