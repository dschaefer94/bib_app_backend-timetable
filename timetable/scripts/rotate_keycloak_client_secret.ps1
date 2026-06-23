# Rotate the Keycloak client secret for the timetable backend client and update .env.local.
# This uses the Admin REST API to generate a new secret for the existing confidential client.

$ErrorActionPreference = 'Stop'

$baseUrl = 'http://localhost:8080'
$realmName = 'bib-app'
$clientId = 'bib-app-backend'
$adminUser = 'admin'
$adminPass = 'admin'

function Get-AdminToken {
    $body = @{ grant_type='password'; username=$adminUser; password=$adminPass; client_id='admin-cli' }
    $resp = Invoke-RestMethod -Method Post -Uri "$baseUrl/realms/master/protocol/openid-connect/token" -ContentType 'application/x-www-form-urlencoded' -Body $body
    return $resp.access_token
}

function Get-ClientByClientId([string]$token) {
    $clients = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/clients?clientId=$clientId" -Headers @{ Authorization = "Bearer $token" }
    if ($clients -is [System.Array]) { return $clients[0] }
    return $clients
}

function Rotate-Secret([string]$token, [string]$clientInternalId) {
    # Keycloak returns the current/new secret from this endpoint on POST in dev setups.
    # If the endpoint is not supported in your version, the script will fail explicitly.
    return Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realmName/clients/$clientInternalId/client-secret" -Headers @{ Authorization = "Bearer $token" }
}

try {
    $token = Get-AdminToken
    $client = Get-ClientByClientId -token $token
    if (-not $client) { throw "Client '$clientId' not found in realm '$realmName'" }

    $rotated = Rotate-Secret -token $token -clientInternalId $client.id
    $newSecret = $rotated.value
    if (-not $newSecret) {
        throw 'Rotation endpoint did not return a secret value.'
    }

    $envPath = Join-Path (Get-Location) '.env.local'
    if (-not (Test-Path $envPath)) { throw ".env.local not found at $envPath" }

    $envLines = Get-Content $envPath | Where-Object { $_ -notmatch '^KEYCLOAK_CLIENT_SECRET=' }
    $envLines += "KEYCLOAK_CLIENT_SECRET=$newSecret"
    $envLines | Out-File -FilePath $envPath -Encoding utf8

    $resultPath = Join-Path (Get-Location) 'KEYCLOAK_SECRET_ROTATION_RESULT.md'
    @"
# Keycloak Secret Rotation Result

Realm: $realmName
Client ID: $clientId
Client internal id: $($client.id)
New client secret: $newSecret

`.env.local` has been updated.
"@ | Out-File -FilePath $resultPath -Encoding utf8

    Write-Host "Secret rotated successfully for client '$clientId'."
    Write-Host "New secret written to .env.local and $resultPath"
}
catch {
    Write-Error "Secret rotation failed: $_"
    exit 1
}

