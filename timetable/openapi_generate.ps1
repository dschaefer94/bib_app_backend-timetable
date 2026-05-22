param (
    [string]$SpecFile = "openapi.yaml",
    [string]$OutputDir = "src/OpenApi"
)

Write-Host "OpenAPI Generator (php-symfony, read-only contract)"

$ProjectRoot = Get-Location
$SpecPath = Join-Path $ProjectRoot $SpecFile
$OutPath  = Join-Path $ProjectRoot $OutputDir

if (!(Test-Path $SpecPath)) {
    Write-Error "OpenAPI spec file not found: $SpecPath"
    exit 1
}

if (Test-Path $OutPath) {
    Write-Host "Removing existing generated code at $OutPath"
    Remove-Item -Recurse -Force $OutPath
}

Write-Host "Running openapi-generator-cli via Docker"
Write-Host "Spec:   $SpecPath"
Write-Host "Output: $OutPath"

docker run --rm `
  -v "${ProjectRoot}:/local" `
  openapitools/openapi-generator-cli `
  generate `
  -i /local/$SpecFile `
  -g php-symfony `
  -o /local/$OutputDir `
  --additional-properties `
'invokerPackage=App\OpenApi,apiPackage=Api,modelPackage=Model,useAttributes=true'

if ($LASTEXITCODE -ne 0) {
    Write-Error "openapi-generator failed"
    exit 1
}

Write-Host "OpenAPI code generation completed successfully"
exit 0
