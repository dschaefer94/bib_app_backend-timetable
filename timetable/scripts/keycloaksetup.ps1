<#
keycloaksetup.ps1

Orchestriert die zusammengehörigen Keycloak-Setup-Skripte:
1) create_keycloak_realm.ps1
2) create_keycloak_demo_users.ps1
3) optional: get_token.ps1

Beispiel:
  Set-Location 'C:\bib\bib-App\bib_app\backends\timetable\timetable'
  .\scripts\keycloaksetup.ps1
#>

param(
    [ValidateSet('dummyuser', 'studentuser', 'teacheruser', 'adminuser')]
    [string]$User = 'studentuser',
    [string]$StudentClassName = 'pbd2h24a',
    [string]$StudentIcalLink = 'https://intranet.bib.de/ical/d819a07653892b46b6e4d2765246b7ab',
    [switch]$SkipRealm,
    [switch]$SkipDemoUsers,
    [switch]$SkipToken,
    [switch]$SkipClassSeed,
    [switch]$SkipInitialImport
)

$ErrorActionPreference = 'Stop'

$scriptDir = $PSScriptRoot
$repoRoot = [System.IO.Path]::GetFullPath((Join-Path $scriptDir '..'))

function Run-Step {
    param(
        [Parameter(Mandatory = $true)]
        [string]$Name,
        [Parameter(Mandatory = $true)]
        [scriptblock]$Action
    )

    Write-Host ""
    Write-Host "=== $Name ===" -ForegroundColor Cyan
    & $Action
    Write-Host "OK: $Name" -ForegroundColor Green
}

function Get-DockerComposeCommand {
    if (Get-Command docker-compose -ErrorAction SilentlyContinue) {
        return 'docker-compose'
    }
    if (Get-Command docker -ErrorAction SilentlyContinue) {
        return 'docker compose'
    }

    throw 'Neither docker-compose nor docker command is available.'
}

function Ensure-ClassWithIcal {
    param(
        [string]$DockerComposeCommand,
        [string]$ClassName,
        [string]$IcalLink
    )

    $escapedClass = $ClassName.Replace("'", "''")
    $escapedLink = $IcalLink.Replace("'", "''")
    $sql = @"
UPDATE klassen
SET ical_link = '$escapedLink'
WHERE klassenname = '$escapedClass';

INSERT INTO klassen (klassenname, ical_link)
SELECT '$escapedClass', '$escapedLink'
WHERE NOT EXISTS (
    SELECT 1 FROM klassen WHERE klassenname = '$escapedClass'
);
"@
    $sqlOneLine = ($sql -replace "`r", '' -replace "`n", ' ')
    iex "$DockerComposeCommand exec -T database psql -U symfony -d pbd2h24asc_stundenplan_db -c `"$sqlOneLine`"" | Out-Null
}

function Get-ClassId {
    param(
        [string]$DockerComposeCommand,
        [string]$ClassName
    )

    $escapedClass = $ClassName.Replace("'", "''")
    $idText = iex "$DockerComposeCommand exec -T database psql -U symfony -d pbd2h24asc_stundenplan_db -t -A -c `"SELECT klassen_id FROM klassen WHERE klassenname = '$escapedClass' LIMIT 1;`""
    $idText = ($idText | Out-String).Trim()
    if (-not $idText) {
        throw "Could not resolve class id for '$ClassName'"
    }

    return [int]$idText
}

Write-Host "Keycloak setup started..." -ForegroundColor Cyan
Write-Host "Repo root: $repoRoot"

Set-Location -Path $repoRoot

if (-not $SkipRealm) {
    $realmScript = Join-Path $scriptDir 'create_keycloak_realm.ps1'
    if (-not (Test-Path $realmScript)) {
        throw "Script not found: $realmScript"
    }

    Run-Step -Name 'Create/Update realm + client' -Action {
        & $realmScript
    }
}

if (-not $SkipDemoUsers) {
    $usersScript = Join-Path $scriptDir 'create_keycloak_demo_users.ps1'
    if (-not (Test-Path $usersScript)) {
        throw "Script not found: $usersScript"
    }

    Run-Step -Name 'Create/Update demo users + roles' -Action {
        & $usersScript -StudentClassClaim $StudentClassName
    }
}

$dockerComposeCommand = Get-DockerComposeCommand

if (-not $SkipClassSeed) {
    Run-Step -Name "Ensure class '$StudentClassName' with configured ICS link" -Action {
        Ensure-ClassWithIcal -DockerComposeCommand $dockerComposeCommand -ClassName $StudentClassName -IcalLink $StudentIcalLink
    }
}

if (-not $SkipInitialImport) {
    Run-Step -Name "Run initial import for class '$StudentClassName'" -Action {
        $classId = Get-ClassId -DockerComposeCommand $dockerComposeCommand -ClassName $StudentClassName
        iex "$dockerComposeCommand exec -T php php bin/console app:calendar:import $classId true"
    }
}

if (-not $SkipToken) {
    $tokenScript = Join-Path $scriptDir 'get_token.ps1'
    if (-not (Test-Path $tokenScript)) {
        throw "Script not found: $tokenScript"
    }

    Run-Step -Name "Get token for '$User'" -Action {
        & $tokenScript -User $User
    }
}

Write-Host ""
Write-Host "Keycloak setup finished successfully." -ForegroundColor Green
