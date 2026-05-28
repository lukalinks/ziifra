# ZIIFRA — deploy via GitHub (git pull on server, no zip upload)
# Usage:
#   .\deploy\pull-and-deploy.ps1
#   .\deploy\pull-and-deploy.ps1 -SkipMigrate
#   .\deploy\pull-and-deploy.ps1 -IdentityFile $env:USERPROFILE\.ssh\ziifra_mcp_deploy
param(
    [string]$ServerIp = "187.124.163.32",
    [string]$User = "root",
    [string]$RemoteDir = "/var/www/ziifra",
    [string]$IdentityFile = "",
    [switch]$SkipMigrate
)

$ErrorActionPreference = "Stop"
$target = "${User}@${ServerIp}"
$migrateFlag = if ($SkipMigrate) { "0" } else { "1" }
$healthCmd = "curl -sfk https://127.0.0.1/up -H 'Host: ziifra.com' >/dev/null && echo Deploy_OK"
$remoteCmd = "cd $RemoteDir && RUN_MIGRATIONS=$migrateFlag bash deploy/pull-deploy.sh && $healthCmd"
$plinkHostKey = "SHA256:/gRPeEQUpSHeTWS8LY/7mspyozwmmoyHW9ZJuTehmdY"
$passwordFile = Join-Path $PSScriptRoot ".root-password.tmp"

if (-not $IdentityFile) {
    foreach ($candidate in @(
            (Join-Path $env:USERPROFILE ".ssh\ziifra_mcp_deploy"),
            (Join-Path $env:USERPROFILE ".ssh\ziifra_cursor_deploy"),
            (Join-Path $env:USERPROFILE ".ssh\github_deploy")
        )) {
        if (Test-Path $candidate) {
            $IdentityFile = $candidate
            break
        }
    }
}

Write-Host "==> Git pull + deploy on ${target}:${RemoteDir}"
if ($SkipMigrate) {
    Write-Host "    Skipping database migrations"
} else {
    Write-Host "    Including database migrations"
}

if (Test-Path $passwordFile) {
    $pw = Get-Content $passwordFile -Raw
    $plink = "C:\Program Files\PuTTY\plink.exe"
    if (-not (Test-Path $plink)) {
        throw "PuTTY plink.exe required when using deploy/.root-password.tmp"
    }
    & $plink -ssh -batch -hostkey $plinkHostKey -pw $pw $target $remoteCmd
} elseif ($IdentityFile) {
    & ssh.exe -i $IdentityFile -o StrictHostKeyChecking=accept-new -o BatchMode=yes $target $remoteCmd
} else {
    & ssh.exe -o StrictHostKeyChecking=accept-new $target $remoteCmd
}

Write-Host "==> Done. Check https://ziifra.com/up"
