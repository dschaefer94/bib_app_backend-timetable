# get_token.ps1
# Holt ein Keycloak Access Token fuer einen Demo-User,
# kopiert es in die Zwischenablage und aktualisiert http-client.private.env.json.
# Verwendung: .\scripts\get_token.ps1 [-User studentuser|teacheruser|adminuser]
param(
    [ValidateSet('studentuser', 'teacheruser', 'adminuser')]
    [string]$User = 'studentuser'
)
$ErrorActionPreference = 'Stop'
# Konfiguration
$baseUrl   = 'http://localhost:8080'
$realmName = 'bib-app'
$clientId  = 'bib-app-backend'
$credentials = @{
    studentuser = 'studentpass'
    teacheruser = 'teacherpass'
    adminuser   = 'adminpass'
}
# Client Secret aus .env.local lesen
function Get-EnvValue([string]$key) {
    $candidates = @(
        (Join-Path $PSScriptRoot '..\\.env.local'),
        (Join-Path (Get-Location) '.env.local')
    )
    foreach ($path in $candidates) {
        $resolved = [System.IO.Path]::GetFullPath($path)
        if (Test-Path $resolved) {
            $line = Get-Content $resolved | Where-Object { $_ -match "^$key=" } | Select-Object -First 1
            if ($line) { return ($line -replace "^$key=", '').Trim().Trim('"') }
        }
    }
    throw "$key nicht in .env.local gefunden."
}
# Token holen
$clientSecret = Get-EnvValue 'KEYCLOAK_CLIENT_SECRET'
$password     = $credentials[$User]
Write-Host ""
Write-Host ">>  Hole Token fuer Benutzer '$User' ..." -ForegroundColor Cyan
$body = @{
    grant_type    = 'password'
    username      = $User
    password      = $password
    client_id     = $clientId
    client_secret = $clientSecret
}
try {
    $resp = Invoke-RestMethod -Method Post `
        -Uri "$baseUrl/realms/$realmName/protocol/openid-connect/token" `
        -ContentType 'application/x-www-form-urlencoded' `
        -Body $body
} catch {
    Write-Host ""
    Write-Host "[FEHLER]  Token-Request fehlgeschlagen:" -ForegroundColor Red
    $err = $_ | Out-String
    Write-Host "    $err" -ForegroundColor Red
    # Development convenience: if realm/client config is out of sync, repair and retry once.
    $shouldRetry = ($err -match 'Realm does not exist') -or ($err -match 'invalid_client') -or ($err -match 'Client not found') -or ($err -match 'unauthorized_client')
    if ($shouldRetry) {
        Write-Host "Realm/Client scheint nicht synchron. Versuche create_keycloak_realm.ps1 und danach einen Retry..." -ForegroundColor Yellow
        try {
            $scriptPath = Join-Path $PSScriptRoot 'create_keycloak_realm.ps1'
            if (Test-Path $scriptPath) {
                & $scriptPath
                Start-Sleep -Seconds 2
                Write-Host 'Retrying token request...'
                $resp = Invoke-RestMethod -Method Post -Uri "$baseUrl/realms/$realmName/protocol/openid-connect/token" -ContentType 'application/x-www-form-urlencoded' -Body $body
                if ($resp -and $resp.access_token) {
                    Write-Host "Retry erfolgreich." -ForegroundColor Green
                } else {
                    throw 'Retry lieferte kein access_token.'
                }
            } else {
                Write-Host "create_keycloak_realm.ps1 not found in $PSScriptRoot" -ForegroundColor Yellow
                exit 1
            }
        } catch {
            Write-Host "Retry after realm creation failed: $_" -ForegroundColor Red
            Write-Host ""
            Write-Host "Ist Keycloak gestartet?  ->  docker-compose up -d" -ForegroundColor Yellow
            exit 1
        }
    } else {
        Write-Host ""
        Write-Host "Ist Keycloak gestartet?  ->  docker-compose up -d" -ForegroundColor Yellow
        exit 1
    }
}
$token = $resp.access_token
if (-not $token) {
    Write-Error "Keycloak hat kein access_token zurueckgegeben."
    exit 1
}
# In Zwischenablage kopieren
Set-Clipboard -Value $token
# http-client.private.env.json aktualisieren (fuer JetBrains HTTP Client)
$privateEnvCandidates = @(
    (Join-Path $PSScriptRoot '..\http-client.private.env.json'),
    (Join-Path (Get-Location) 'http-client.private.env.json')
)
foreach ($candidate in $privateEnvCandidates) {
    $resolved = [System.IO.Path]::GetFullPath($candidate)
    if (Test-Path $resolved) {
        try {
            $envJson = Get-Content $resolved -Raw | ConvertFrom-Json
            if (-not $envJson.dev) {
                $envJson | Add-Member -NotePropertyName 'dev' -NotePropertyValue ([pscustomobject]@{}) -Force
            }
            $envJson.dev | Add-Member -NotePropertyName 'token' -NotePropertyValue $token -Force
            $envJson | ConvertTo-Json -Depth 5 | Set-Content -Path $resolved -Encoding UTF8
            Write-Host "     http-client.private.env.json aktualisiert." -ForegroundColor DarkGray
        } catch {
            Write-Host "     (Hinweis: http-client.private.env.json konnte nicht aktualisiert werden: $_)" -ForegroundColor DarkGray
        }
        break
    }
}
# Token-Infos anzeigen (JWT-Payload dekodieren)
$expiresIn = $resp.expires_in
$tokenLen  = $token.Length
try {
    $parts   = $token.Split('.')
    $payload = $parts[1]
    $payload = $payload.Replace('-', '+').Replace('_', '/')
    switch ($payload.Length % 4) {
        2 { $payload += '==' }
        3 { $payload += '=' }
    }
    $json    = [System.Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($payload))
    $claims  = $json | ConvertFrom-Json
    $sub     = $claims.sub
    $roles   = ($claims.realm_access.roles | Where-Object { $_ -notin @('default-roles-bib-app','offline_access','uma_authorization') }) -join ', '
    $expTime = (Get-Date '1970-01-01').AddSeconds($claims.exp).ToLocalTime()
} catch {
    $sub = '(nicht lesbar)'
    $roles = '(nicht lesbar)'
    $expTime = '(unbekannt)'
}
Write-Host ""
Write-Host "[OK]  Token erfolgreich geholt & in Zwischenablage kopiert!" -ForegroundColor Green
Write-Host ""
Write-Host "  Benutzer   : $User" -ForegroundColor White
Write-Host "  Subject    : $sub" -ForegroundColor White
Write-Host "  Rollen     : $roles" -ForegroundColor White
Write-Host "  Gueltig bis: $expTime  ($expiresIn s)" -ForegroundColor White
Write-Host "  Tokenlaenge: $tokenLen Zeichen" -ForegroundColor White
Write-Host ""
Write-Host "-->  JetBrains HTTP Client: api-tests.http oeffnen, Umgebung 'dev' waehlen, Request ausfuehren." -ForegroundColor Cyan
Write-Host "     Token wurde automatisch in http-client.private.env.json geschrieben." -ForegroundColor Cyan
Write-Host ""
