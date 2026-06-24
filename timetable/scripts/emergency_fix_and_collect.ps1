<#
emergency_fix_and_collect.ps1

Notfallskript (PowerShell) für lokale Ausführung im Projekt-Root:
- sichert .env.local
- entfernt BOM hostseitig
- kopiert .env.local in Container (falls vorhanden)
- entfernt BOM im Container (falls vorhanden)
- leert Symfony-Cache im Container
- restarted php-Container
- führt Keycloak-Realm + Demo-User Scripts aus (optional)
- holt Token und testet /api/profile/me
- sammelt Logs (php, keycloak, database, worker) in tmp/emergency_*.log

USAGE (in PowerShell, als Entwickler auf deinem Rechner):
  Set-Location 'C:\bib\bib-App\bib_app\backends\timetable\timetable'
  .\scripts\emergency_fix_and_collect.ps1

WARNUNG: Das Skript manipuliert Dateien und Container; es ist destruktiv, aber vorsichtig.
#>

param(
    [switch]$RunKeycloakScripts,
    [int]$WaitKeycloakSec = 60
)

$ErrorActionPreference = 'Stop'
$root = (Get-Location).ProviderPath
$tmpDir = Join-Path $root 'tmp'
if (-not (Test-Path $tmpDir)) { New-Item -ItemType Directory -Path $tmpDir | Out-Null }
$logFile = Join-Path $tmpDir "emergency_$(Get-Date -Format 'yyyyMMdd_HHmmss').log"
function Log { param($m) "$((Get-Date).ToString('o'))`t$m" | Tee-Object -FilePath $logFile -Append }

Log 'START emergency_fix_and_collect'

# 1) Backup .env.local
$envPath = Join-Path $root '.env.local'
if (Test-Path $envPath) {
    $bak = Join-Path $tmpDir ('.env.local.bak.' + (Get-Date -Format 'yyyyMMdd_HHmmss'))
    Copy-Item -Path $envPath -Destination $bak -Force
    Log "Backed up .env.local to $bak"
} else {
    Log '.env.local not found on host'
}

# 2) Remove BOM host-side
try {
    $bytes = [System.IO.File]::ReadAllBytes($envPath)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        Log '.env.local contains BOM -> removing host-side'
        $newBytes = $bytes[3..($bytes.Length - 1)]
        $enc = New-Object System.Text.UTF8Encoding $false
        $text = [System.Text.Encoding]::UTF8.GetString($newBytes)
        [System.IO.File]::WriteAllText($envPath, $text, $enc)
        Log 'Removed BOM from host .env.local'
    } else { Log 'No BOM found in host .env.local' }
} catch { Log "Error checking/removing BOM host-side: $_" }

# 3) If docker available, attempt container-side fix
$dockerAvailable = $false
try { Get-Command docker -ErrorAction Stop | Out-Null; $dockerAvailable = $true } catch { $dockerAvailable = $false }
if (-not $dockerAvailable) { Log 'Docker CLI not found in PATH on this machine. Exiting container-side actions.'; Write-Host "Done. See log: $logFile"; exit 0 }

# Determine docker-compose command
$dc = 'docker-compose'
if (-not (Get-Command docker-compose -ErrorAction SilentlyContinue)) { $dc = 'docker compose' }
Log "Using docker command: $dc"

# Get php container name
try {
    $phpCid = & $dc ps -q php 2>$null
    if (-not $phpCid) { $phpCid = (docker ps --filter "name=php" --quiet) }
    Log "php container id: $phpCid"
} catch { Log "Failed to determine php container id: $_" }

# Copy host .env.local into container path /var/www/html/.env.local
try {
    if ($phpCid) {
        Log 'Copying host .env.local into php container at /var/www/html/.env.local'
        # Use docker cp
        docker cp $envPath "${phpCid}:/var/www/html/.env.local" 2>&1 | Tee-Object -FilePath $logFile -Append
        Log 'Copied .env.local into container'
        # Remove BOM inside container (python3 fallback)
        Log 'Removing BOM inside container if present'
        $pyCmd = @'
p='/var/www/html/.env.local'
s=open(p,'rb').read()
if s.startswith(b'\xef\xbb\xbf'): s=s[3:]; open(p,'wb').write(s); print('removed')
else: print('no')
'@
        docker exec -i $phpCid sh -lc "python3 - <<'PY' $pyCmd PY" 2>&1 | Tee-Object -FilePath $logFile -Append
    } else {
        Log 'php container id not found; skipping container copy'
    }
} catch { Log "Error copying/removing BOM in container: $_" }

# 4) Clear Symfony cache inside container
try {
    Log 'Clearing Symfony cache inside php container'
    & $dc exec -T php php bin/console cache:clear 2>&1 | Tee-Object -FilePath $logFile -Append
    Log 'cache:clear finished'
} catch { Log "cache:clear failed: $_" }

# Restart php container
try { Log 'Restarting php container'; & $dc restart php | Tee-Object -FilePath $logFile -Append } catch { Log 'Failed to restart php via docker-compose restart' }
Start-Sleep -Seconds 3

# 5) Optionally run Keycloak scripts to ensure realm exists
if ($RunKeycloakScripts) {
    try {
        Set-Location (Join-Path $root 'scripts')
        Log 'Running create_keycloak_realm.ps1'
        .\create_keycloak_realm.ps1 2>&1 | Tee-Object -FilePath $logFile -Append
        Log 'Running create_keycloak_demo_users.ps1'
        .\create_keycloak_demo_users.ps1 2>&1 | Tee-Object -FilePath $logFile -Append
    } catch { Log "Keycloak scripts failed: $_" }
}

# 6) Try to get token and call /api/profile/me
try {
    Set-Location (Join-Path $root 'scripts')
    Log 'Attempting to get token for studentuser'
    .\get_token.ps1 -User studentuser 2>&1 | Tee-Object -FilePath $logFile -Append
} catch { Log "get_token failed: $_" }

# Test API call
try {
    Set-Location $root
    $envJson = Get-Content 'http-client.private.env.json' -Raw | ConvertFrom-Json
    $token = $envJson.dev.token
    if (-not $token) { Log 'No token found in http-client.private.env.json' } else {
        Log 'Testing GET /api/profile/me'
        try {
            $resp = Invoke-RestMethod -Method Get -Uri 'http://127.0.0.1:8000/api/profile/me' -Headers @{ Authorization = "Bearer $token"; Accept='application/json' } -UseBasicParsing -TimeoutSec 15
            $out = $resp | ConvertTo-Json -Depth 6
            Log "API success: $out"
            Write-Host 'API call successful. See log for details.'
        } catch { Log "API call failed: $_" }
    }
} catch { Log "Error during API test: $_" }

# 7) Collect logs
try {
    Log 'Collecting last 500 lines of php, keycloak, database, worker logs'
    & $dc logs --tail 500 php 2>&1 | Tee-Object -FilePath $logFile -Append
    & $dc logs --tail 500 keycloak 2>&1 | Tee-Object -FilePath $logFile -Append
    & $dc logs --tail 500 database 2>&1 | Tee-Object -FilePath $logFile -Append
    & $dc logs --tail 500 worker 2>&1 | Tee-Object -FilePath $logFile -Append
} catch { Log "Failed to collect some logs: $_" }

Log 'FINISHED emergency_fix_and_collect'
Write-Host "Done. Log written to: $logFile"

