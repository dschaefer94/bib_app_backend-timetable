# Create demo users and roles in Keycloak for local testing.
# This script is idempotent for roles, and creates fresh demo users if they do not exist.

$ErrorActionPreference = 'Stop'

$baseUrl = 'http://localhost:8080'
$realmName = 'bib-app'
$clientId = 'bib-app-backend'
$adminUser = 'admin'
$adminPass = 'admin'

$rolesToCreate = @('admin', 'student', 'teacher')
$demoUsers = @(
    @{ username='dummyuser'; password='dummypass'; email='dummyuser@example.com'; firstName='Dummy'; lastName='User'; role='student' },
    @{ username='studentuser'; password='studentpass'; email='studentuser@example.com'; firstName='Student'; lastName='User'; role='student' },
    @{ username='teacheruser'; password='teacherpass'; email='teacheruser@example.com'; firstName='Teacher'; lastName='User'; role='teacher' },
    @{ username='adminuser'; password='adminpass'; email='adminuser@example.com'; firstName='Admin'; lastName='User'; role='admin' }
)

function Get-AdminToken {
    $body = @{ grant_type='password'; username=$adminUser; password=$adminPass; client_id='admin-cli' }
    $resp = Invoke-RestMethod -Method Post -Uri "$baseUrl/realms/master/protocol/openid-connect/token" -ContentType 'application/x-www-form-urlencoded' -Body $body
    return $resp.access_token
}

function Ensure-Role([string]$token, [string]$roleName) {
    try {
        $null = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/roles/$roleName" -Headers @{ Authorization = "Bearer $token" }
        Write-Host "Role '$roleName' already exists."
    } catch {
        $body = @{ name = $roleName } | ConvertTo-Json
        $null = Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realmName/roles" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $body
        Write-Host "Created role '$roleName'."
    }
}

function Ensure-User([string]$token, [hashtable]$userSpec) {
    $username = $userSpec.username
    $query = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/users?username=$username" -Headers @{ Authorization = "Bearer $token" }
    if ($query -is [System.Array]) {
        $existing = $query | Select-Object -First 1
    } else {
        $existing = $query
    }

    if ($existing) {
        Write-Host "User '$username' already exists (id: $($existing.id))."
        return $existing.id
    }

    Write-Host "Creating user '$username'..."
    $createBody = @{
        username = $userSpec.username
        email = $userSpec.email
        firstName = $userSpec.firstName
        lastName = $userSpec.lastName
        enabled = $true
        emailVerified = $true
        requiredActions = @()
    } | ConvertTo-Json -Depth 6

    $null = Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realmName/users" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $createBody
    Start-Sleep -Seconds 1
    $query = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/users?username=$username" -Headers @{ Authorization = "Bearer $token" }
    if ($query -is [System.Array]) { $existing = $query | Select-Object -First 1 } else { $existing = $query }
    if (-not $existing) { throw "Failed to create user '$username'" }

    Write-Host "Created user '$username' (id: $($existing.id))."
    return $existing.id
}

function Set-UserPassword([string]$token, [string]$userId, [string]$password, [string]$username) {
    Write-Host "Setting password for '$username'..."
    $pwBody = @{ type='password'; temporary=$false; value=$password } | ConvertTo-Json
    $null = Invoke-RestMethod -Method Put -Uri "$baseUrl/admin/realms/$realmName/users/$userId/reset-password" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $pwBody
}

function Assign-RealmRole([string]$token, [string]$userId, [string]$roleName, [string]$username) {
    $role = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/roles/$roleName" -Headers @{ Authorization = "Bearer $token" }
    $roleJson = @"
[
  {
    "id": "$($role.id)",
    "name": "$($role.name)"
  }
]
"@
    Write-Host "Assigning role '$roleName' to '$username'..."
    $null = Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realmName/users/$userId/role-mappings/realm" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $roleJson
}

try {
    $token = Get-AdminToken

    foreach ($role in $rolesToCreate) {
        Ensure-Role -token $token -roleName $role
    }

    foreach ($u in $demoUsers) {
        $userId = Ensure-User -token $token -userSpec $u
        Set-UserPassword -token $token -userId $userId -password $u.password -username $u.username
        Assign-RealmRole -token $token -userId $userId -roleName $u.role -username $u.username
    }

    # Keep the existing confidential client enabled for service-account / client-credentials flow.
    $client = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/clients?clientId=$clientId" -Headers @{ Authorization = "Bearer $token" }
    if ($client -is [System.Array]) { $client = $client[0] }
    if ($client) {
        $fullClient = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/clients/$($client.id)" -Headers @{ Authorization = "Bearer $token" }
        $fullClient.serviceAccountsEnabled = $true
        $fullClient.directAccessGrantsEnabled = $true
        $null = Invoke-RestMethod -Method Put -Uri "$baseUrl/admin/realms/$realmName/clients/$($client.id)" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body ($fullClient | ConvertTo-Json -Depth 12)
        Write-Host "Ensured client credentials and direct access grants are enabled for '$clientId'."
    }

    $resultPath = Join-Path (Get-Location) 'KEYCLOAK_DEMO_USERS.md'
    @"
# Keycloak Demo Users

Realm: $realmName
Client ID: $clientId

Created roles:
- admin
- student
- teacher

Created users:
- dummyuser / dummypass -> student
- studentuser / studentpass -> student
- teacheruser / teacherpass -> teacher
- adminuser / adminpass -> admin

Use `studentuser` for the end-to-end password-grant smoke test.
"@ | Out-File -FilePath $resultPath -Encoding utf8

    Write-Host 'Demo users and roles created/updated successfully.'
    Write-Host "Result written to $resultPath"
}
catch {
    Write-Error "Failed to create demo users: $_"
    exit 1
}

