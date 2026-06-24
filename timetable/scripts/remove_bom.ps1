<#
remove_bom.ps1

Entfernt optional ein Byte-Order-Mark (BOM, EF BB BF) von textbasierten Dateien im Repo-Root.
Standardmäßig wird `./.env.local` (Projekt-Root) überprüft und ggf. bereinigt.

Benutzung (aus dem Projekt-Root):
  Set-Location 'C:\bib\bib-App\bib_app\backends\timetable\timetable'
  .\scripts\remove_bom.ps1

Optional kannst du Dateipfade übergeben:
  .\scripts\remove_bom.ps1 -Paths '.env.local','http-client.private.env.json'

#>

param(
    [string[]]$Paths = @('.env.local')
)

$ErrorActionPreference = 'Stop'
$repoRoot = (Get-Location).ProviderPath
Write-Host "Repo root: $repoRoot"

foreach ($p in $Paths) {
    $full = Join-Path -Path $repoRoot -ChildPath $p
    if (-not (Test-Path $full)) { Write-Host "SKIP: $p (nicht gefunden)"; continue }
    Write-Host "Checking: $full"
    $bytes = [System.IO.File]::ReadAllBytes($full)
    if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
        Write-Host "BOM found in $p -> removing..."
        $newBytes = $bytes[3..($bytes.Length - 1)]
        # Write text back with UTF8 WITHOUT BOM
        $enc = New-Object System.Text.UTF8Encoding $false
        $text = [System.Text.Encoding]::UTF8.GetString($newBytes)
        [System.IO.File]::WriteAllText($full, $text, $enc)
        Write-Host "WROTE: $p (BOM removed)"
    } else {
        Write-Host "OK: $p (no BOM)"
    }
}

Write-Host 'Done.'

