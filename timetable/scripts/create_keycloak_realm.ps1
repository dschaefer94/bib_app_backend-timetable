# PowerShell script to automate Keycloak realm + client creation (Keycloak Admin REST API)
# Usage: Run from project root (PowerShell) where Keycloak is reachable at http://localhost:8080
# Requires PowerShell 7+ ideally, but works with Windows PowerShell 5.1 for basic Invoke-RestMethod usage.

$ErrorActionPreference = 'Stop'
$baseUrl = 'http://localhost:8080'
$adminUser = 'admin'
$adminPass = 'admin'
$realmName = 'bib-app'
$clientId = 'bib-app-backend'
$redirectUris = @('http://localhost:8000/*','http://localhost:3000/*')
$projectRoot = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))

function Wait-KeycloakReady([int]$timeoutSec = 120) {
    Write-Host "Waiting up to $timeoutSec seconds for Keycloak at $baseUrl..."
    $max = [math]::Ceiling($timeoutSec / 2)
    $i = 0
    while ($i -lt $max) {
        try {
            $r = Invoke-WebRequest -Uri $baseUrl -UseBasicParsing -TimeoutSec 3
            if ($r.StatusCode -eq 200) { Write-Host 'Keycloak HTTP reachable.'; return $true }
        } catch { }
        Start-Sleep -Seconds 2; $i++
    }
    Write-Warning 'Keycloak did not respond within timeout.'
    return $false
}

function Get-AdminToken {
    Write-Host "Requesting admin token from $baseUrl..."
    # ensure Keycloak is reachable first
    Wait-KeycloakReady | Out-Null
    $body = @{ grant_type='password'; username=$adminUser; password=$adminPass; client_id='admin-cli' }
    $tries = 0
    while ($tries -lt 5) {
        try {
            $resp = Invoke-RestMethod -Method Post -Uri "$baseUrl/realms/master/protocol/openid-connect/token" -ContentType 'application/x-www-form-urlencoded' -Body $body -TimeoutSec 10
            if ($resp -and $resp.access_token) { return $resp.access_token }
        } catch {
            Write-Host "Admin token request failed (attempt $($tries+1)): $_" -ForegroundColor Yellow
            Start-Sleep -Seconds 2
        }
        $tries++
    }
    throw 'Failed to obtain admin token after retries.'
}

function Realm-Exists($token, $realm) {
    try {
        Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm" -Headers @{ Authorization = "Bearer $token" }
        return $true
    } catch {
        return $false
    }
}

function Create-Realm($token, $realm) {
    Write-Host "Creating realm '$realm'..."
    $body = @{ realm=$realm; enabled=$true } | ConvertTo-Json
    Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $body
}

function Create-Client($token, $realm, $clientId, $redirectUris) {
    # Idempotent: reuse existing client when present; create only if missing
    $existing = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/clients?clientId=$clientId" -Headers @{ Authorization = "Bearer $token" }
    if ($existing) {
        if ($existing -is [System.Array]) { return $existing[0] }
        return $existing
    }

    Write-Host "Creating client '$clientId' in realm '$realm'..."
    $body = @{
        clientId = $clientId
        enabled = $true
        publicClient = $false
        protocol = 'openid-connect'
        redirectUris = $redirectUris
        directAccessGrantsEnabled = $true
        standardFlowEnabled = $true
        attributes = @{}
    } | ConvertTo-Json -Depth 6

    Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realm/clients" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $body

    # Retrieve client to get internal id
    Start-Sleep -Seconds 1
    $clients = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/clients?clientId=$clientId" -Headers @{ Authorization = "Bearer $token" }
    if ($clients -is [System.Array]) { $client = $clients[0] } else { $client = $clients }
    return $client
}

function Get-ClientSecret($token, $realm, $clientInternalId) {
    Write-Host "Fetching client secret for client id $clientInternalId..."
    $resp = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/clients/$clientInternalId/client-secret" -Headers @{ Authorization = "Bearer $token" }
    return $resp.value
}

try {
    $token = Get-AdminToken
    Write-Host "Got admin token (length: $($token.Length))"

    if (-not (Realm-Exists $token $realmName)) {
        Create-Realm $token $realmName
        Write-Host "Realm '$realmName' created."
    } else {
        Write-Host "Realm '$realmName' already exists."
    }

    # Create client
    $client = Create-Client $token $realmName $clientId $redirectUris
    if (-not $client) { throw "Failed to create or fetch client $clientId" }
    $clientInternalId = $client.id
    Write-Host "Client internal id: $clientInternalId"

    # Get secret
    $secret = Get-ClientSecret $token $realmName $clientInternalId
    Write-Host "Client secret obtained: $secret"

    # Write results to file (always in project root, not current shell location)
    $resultPath = Join-Path -Path $projectRoot -ChildPath 'KEYCLOAK_AUTOMATION_RESULT.md'
    $content = @()
    $content += "# Keycloak Automation Result"
    $content += "Generated at: $(Get-Date -Format o)"
    $content += ""
    $content += "Realm: $realmName"
    $content += "Client ID: $clientId"
    $content += "Client internal id: $clientInternalId"
    $content += "Client secret: $secret"
    $content += ""
    $content += "Add to .env.local (or your environment):"
    $content += "OIDC_ISSUER=http://localhost:8080/realms/$realmName"
    $content += "KEYCLOAK_CLIENT_ID=$clientId"
    $content += "KEYCLOAK_CLIENT_SECRET=$secret"

    $content | Out-File -FilePath $resultPath -Encoding utf8
    Write-Host "Wrote results to $resultPath"

    # Update .env.local in project root
    $envPath = Join-Path -Path $projectRoot -ChildPath '.env.local'
    $envLines = @()
    if (Test-Path $envPath) {
        $envLines = Get-Content $envPath
        # Remove existing keys if present
        $envLines = $envLines | Where-Object { $_ -notmatch "^OIDC_ISSUER=|^KEYCLOAK_CLIENT_ID=|^KEYCLOAK_CLIENT_SECRET=" }
    }
    $envLines += "OIDC_ISSUER=http://localhost:8080/realms/$realmName"
    $envLines += "KEYCLOAK_CLIENT_ID=$clientId"
    $envLines += "KEYCLOAK_CLIENT_SECRET=$secret"
    # Write UTF-8 without BOM (Symfony Dotenv rejects BOM)
    $utf8NoBom = New-Object System.Text.UTF8Encoding($false)
    [System.IO.File]::WriteAllLines($envPath, $envLines, $utf8NoBom)
    Write-Host "Wrote .env.local with Keycloak variables"

    Write-Host "Automation completed successfully."
} catch {
    Write-Error "Error during Keycloak automation: $_"
    exit 1
}

