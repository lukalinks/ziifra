# ZIIFRA — deploy code changes only (keeps server .env, DB, uploads)
# Usage:
#   .\deploy\release.ps1              # code + assets only
#   .\deploy\release.ps1 -Migrate     # also run php artisan migrate
param(
    [string]$ServerIp = "187.124.163.32",
    [string]$User = "root",
    [string]$RemoteDir = "/var/www/ziifra",
    [switch]$Migrate
)

$ErrorActionPreference = "Stop"
$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")
$Zip = Join-Path $env:TEMP "ziifra-release.zip"
$target = "${User}@${ServerIp}"

Write-Host "==> Packaging code (excludes .env, vendor, node_modules, storage uploads)"
if (Test-Path $Zip) { Remove-Item $Zip -Force }

Push-Location $ProjectRoot
& tar.exe -a -c -f $Zip `
  --exclude=vendor `
  --exclude=node_modules `
  --exclude=.git `
  --exclude=.env `
  --exclude=.env.* `
  --exclude=database/database.sqlite `
  --exclude=storage/app `
  --exclude=storage/logs `
  --exclude=storage/framework `
  --exclude=bootstrap/cache `
  .
Pop-Location

$migrateFlag = if ($Migrate) { "1" } else { "0" }
$remoteCmd = "cd $RemoteDir && unzip -o /tmp/ziifra-release.zip && RUN_MIGRATIONS=$migrateFlag bash deploy/release.sh"

Write-Host "==> Upload (password prompt 1 of 2)"
& scp.exe -o StrictHostKeyChecking=accept-new $Zip "${target}:/tmp/ziifra-release.zip"

Write-Host "==> Release on server (password prompt 2 of 2)"
if ($Migrate) {
  Write-Host "    Including database migrations"
}
& ssh.exe -o StrictHostKeyChecking=accept-new $target $remoteCmd

Write-Host "==> Done. Open http://${ServerIp}/"
