# End-to-end Keycloak test script for the timetable backend.
# It requests a token for the test user and calls the protected Symfony endpoint.

$ErrorActionPreference = 'Stop'

$baseUrl = 'http://localhost:8080'
$apiBase = 'http://localhost:8000'
$realmName = 'bib-app'
$clientId = 'bib-app-backend'
$testUsername = 'studentuser'
$testPassword = 'studentpass'

function Get-ClientSecretFromEnvFile {
    $envPath = Join-Path (Get-Location) '.env.local'
    if (-not (Test-Path $envPath)) {
        throw ".env.local not found at $envPath"
    }

    $secretLine = Get-Content $envPath | Where-Object { $_ -match '^KEYCLOAK_CLIENT_SECRET=' } | Select-Object -First 1
    if (-not $secretLine) { throw 'KEYCLOAK_CLIENT_SECRET not found in .env.local' }
    return ($secretLine -replace '^KEYCLOAK_CLIENT_SECRET=', '').Trim()
}

function Get-UserToken([string]$clientSecret) {
    $body = @{
        grant_type = 'password'
        username   = $testUsername
        password   = $testPassword
        client_id  = $clientId
        client_secret = $clientSecret
    }

    return Invoke-RestMethod -Method Post -Uri "$baseUrl/realms/$realmName/protocol/openid-connect/token" -ContentType 'application/x-www-form-urlencoded' -Body $body
}

function Call-ProtectedEndpoint([string]$accessToken) {
    return Invoke-RestMethod -Method Get -Uri "$apiBase/api/profile/me" -Headers @{ Authorization = "Bearer $accessToken" }
}

try {
    $clientSecret = Get-ClientSecretFromEnvFile
    Write-Host "Using client secret from .env.local (length: $($clientSecret.Length))"

    $tokenResponse = Get-UserToken -clientSecret $clientSecret
    if (-not $tokenResponse.access_token) {
        throw 'Token endpoint did not return access_token.'
    }

    Write-Host "Received access token (length: $($tokenResponse.access_token.Length))"
    $profile = Call-ProtectedEndpoint -accessToken $tokenResponse.access_token

    $summary = [ordered]@{
        ok = $true
        realm = $realmName
        clientId = $clientId
        username = $testUsername
        roles = $profile.roles
        identityId = $profile.identityId
        email = $profile.email
    }

    $resultPath = Join-Path (Get-Location) 'KEYCLOAK_E2E_RESULT.json'
    $summary | ConvertTo-Json -Depth 6 | Out-File -FilePath $resultPath -Encoding utf8

    Write-Host 'E2E test successful:'
    $summary | ConvertTo-Json -Depth 6 | Write-Host
    Write-Host "Result written to $resultPath"
}
catch {
    Write-Error "E2E test failed: $_"
    exit 1
}

