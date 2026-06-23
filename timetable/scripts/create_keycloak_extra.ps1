# PowerShell script to add roles, create a test user and enable client credentials (service account) in Keycloak
# Usage: Run from project root where Keycloak is reachable at http://localhost:8080

$ErrorActionPreference = 'Stop'
$baseUrl = 'http://localhost:8080'
$adminUser = 'admin'
$adminPass = 'admin'
$realmName = 'bib-app'
$clientId = 'bib-app-backend'

$testUsername = 'testuser'
$testPassword = 'testpass'
$testEmail = 'testuser@example.com'
$rolesToCreate = @('admin','student','teacher')

function Get-AdminToken {
    Write-Host "Requesting admin token from $baseUrl..."
    $body = @{ grant_type='password'; username=$adminUser; password=$adminPass; client_id='admin-cli' }
    $resp = Invoke-RestMethod -Method Post -Uri "$baseUrl/realms/master/protocol/openid-connect/token" -ContentType 'application/x-www-form-urlencoded' -Body $body
    return $resp.access_token
}

function Ensure-Role($token, $realm, $roleName) {
    try {
        Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/roles/$roleName" -Headers @{ Authorization = "Bearer $token" }
        Write-Host "Role '$roleName' already exists."
    } catch {
        Write-Host "Creating role '$roleName'..."
        $body = @{ name = $roleName } | ConvertTo-Json
        Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realm/roles" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $body
        Write-Host "Created role '$roleName'."
    }
}

function Ensure-User($token, $realm, $username, $email, $password) {
    # Check user exists
    $users = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/users?username=$username" -Headers @{ Authorization = "Bearer $token" }
    if ($users -and $users.Count -gt 0) {
        $user = $users[0]
        Write-Host "User '$username' already exists (id: $($user.id))."
    } else {
        Write-Host "Creating user '$username'..."
        $body = @{ username = $username; enabled = $true; email = $email } | ConvertTo-Json
        Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realm/users" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $body
        Start-Sleep -Seconds 1
        $users = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/users?username=$username" -Headers @{ Authorization = "Bearer $token" }
        $user = $users[0]
        Write-Host "Created user '$username' (id: $($user.id))."
    }

    # Set password
    Write-Host "Setting password for user '$username'..."
    $pwBody = @{ type = 'password'; temporary = $false; value = $password } | ConvertTo-Json
    Invoke-RestMethod -Method Put -Uri "$baseUrl/admin/realms/$realm/users/$($user.id)/reset-password" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $pwBody
    Write-Host "Password set for user '$username'."

    return $user.id
}

function Assign-Role-To-User($token, $realm, $userId, $roleName) {
    # Normalize userId (trim whitespace) and get role representation
    $userId = $userId.ToString().Trim()
    if ([string]::IsNullOrWhiteSpace($userId)) { throw "Empty userId provided" }
    # Get role representation
    $role = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/roles/$roleName" -Headers @{ Authorization = "Bearer $token" }
    $roleObj = @{ id = $role.id; name = $role.name }
    # Ensure JSON array is sent even for a single role (ConvertTo-Json may return object for single-element arrays)
    $singleJson = ($roleObj | ConvertTo-Json -Depth 6).Trim()
    $body = "[$singleJson]"
    Write-Host "Assigning role '$roleName' to user id $userId..."
    # Verify user exists by fetching it
    try {
        $userCheck = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/users/$userId" -Headers @{ Authorization = "Bearer $token" }
        Write-Host "Found user id: $($userCheck.id) (username: $($userCheck.username))"
    } catch {
        Write-Host "DEBUG: Could not find user via GET /users/{id}: $_"
        throw "User with id $userId not found when attempting to assign role."
    }

    Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realm/users/$userId/role-mappings/realm" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body $body
    Write-Host "Assigned role '$roleName' to user id $userId."
}

function Enable-ServiceAccount-On-Client($token, $realm, $clientId) {
    Write-Host "Enabling service account (client credentials) for client '$clientId'..."
    $clients = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/clients?clientId=$clientId" -Headers @{ Authorization = "Bearer $token" }
    if (-not $clients) { throw "Client $clientId not found in realm $realm" }
    $client = $clients[0]
    $internalId = $client.id

    # Get full client representation
    $clientFull = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realm/clients/$internalId" -Headers @{ Authorization = "Bearer $token" }
    $clientFull.serviceAccountsEnabled = $true

    # PUT updated representation
    Invoke-RestMethod -Method Put -Uri "$baseUrl/admin/realms/$realm/clients/$internalId" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body ($clientFull | ConvertTo-Json -Depth 10)
    Write-Host "Enabled service account for client (internal id: $internalId)."

    return $internalId
}

# Begin
try {
    $token = Get-AdminToken

    # Create roles
    foreach ($r in $rolesToCreate) {
        Ensure-Role $token $realmName $r
    }

    # Create test user and set password
    $userId = Ensure-User $token $realmName $testUsername $testEmail $testPassword
    # Ensure we take the last output element (function may emit multiple pipeline objects); then normalize
    $userId = ($userId | Select-Object -Last 1).ToString().Trim()

    # Assign 'student' role to test user
    Assign-Role-To-User $token $realmName $userId 'student'

    # Enable service account (client credentials) on client
    $clientInternalId = Enable-ServiceAccount-On-Client $token $realmName $clientId

    # Add service account role mapping if desired (optional) - give service account 'admin' role for tests
    # Get service account user id
    $serviceAccounts = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/clients/$clientInternalId/service-account-user" -Headers @{ Authorization = "Bearer $token" }
    if ($serviceAccounts -and $serviceAccounts.id) {
        $saUserId = $serviceAccounts.id
        Write-Host "Service account user id: $saUserId"
        # Assign 'admin' role to service account
        $role = Invoke-RestMethod -Method Get -Uri "$baseUrl/admin/realms/$realmName/roles/admin" -Headers @{ Authorization = "Bearer $token" }
        $roleRep = @{ id = $role.id; name = $role.name } | ConvertTo-Json
        Invoke-RestMethod -Method Post -Uri "$baseUrl/admin/realms/$realmName/users/$saUserId/role-mappings/realm" -Headers @{ Authorization = "Bearer $token"; 'Content-Type'='application/json' } -Body "[@($roleRep)]"
        Write-Host "Assigned 'admin' role to service account user."
    } else {
        Write-Host "Service account user not found or not yet available. You can assign roles later."
    }

    # Write result file with test user info and curl examples (use a double-quoted here-string for safe interpolation)
    $resultPath = Join-Path -Path (Get-Location) -ChildPath 'KEYCLOAK_TEST_USER.md'
    $testContent = @"
# Keycloak Test User & Roles
Generated at: $(Get-Date -Format o)

Realm: $realmName
Test user: $testUsername / $testPassword
Assigned role: student

Client ID: $clientId
Client internal id: $clientInternalId
Client secret: (see KEYCLOAK_AUTOMATION_RESULT.md)

# Token examples
# 1) Password Grant (user token)
curl -X POST "$baseUrl/realms/$realmName/protocol/openid-connect/token" -H "Content-Type: application/x-www-form-urlencoded" -d "grant_type=password&username=$testUsername&password=$testPassword&client_id=$clientId&client_secret=<CLIENT_SECRET>"

# 2) Client Credentials (service account token)
curl -X POST "$baseUrl/realms/$realmName/protocol/openid-connect/token" -H "Content-Type: application/x-www-form-urlencoded" -d "grant_type=client_credentials&client_id=$clientId&client_secret=<CLIENT_SECRET>"
"@

    $testContent | Out-File -FilePath $resultPath -Encoding utf8
    Write-Host "Wrote test user info to $resultPath"

    Write-Host "All done. Test user and roles created; client credentials enabled."
} catch {
    Write-Error "Error while creating roles/user/service-account: $_"
    exit 1
}

