# ZIIFRA — deploy via GitHub (git pull on server, no zip upload)
# Usage:
#   .\deploy\pull-and-deploy.ps1
#   .\deploy\pull-and-deploy.ps1 -SkipMigrate
param(
    [string]$ServerIp = "187.124.163.32",
    [string]$User = "root",
    [string]$RemoteDir = "/var/www/ziifra",
    [switch]$SkipMigrate
)

$ErrorActionPreference = "Stop"
$target = "${User}@${ServerIp}"
$migrateFlag = if ($SkipMigrate) { "0" } else { "1" }
$remoteCmd = "cd $RemoteDir && RUN_MIGRATIONS=$migrateFlag bash deploy/pull-deploy.sh"

Write-Host "==> Git pull + deploy on ${target}:${RemoteDir}"
if ($SkipMigrate) {
    Write-Host "    Skipping database migrations"
} else {
    Write-Host "    Including database migrations"
}

& ssh.exe -o StrictHostKeyChecking=accept-new $target $remoteCmd

Write-Host "==> Done. Check https://ziifra.com/up"
