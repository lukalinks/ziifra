# Quick fix — one zip upload + one SSH command (2 password prompts max on Windows)
param([string]$ServerIp = "187.124.163.32", [string]$User = "root")

$ErrorActionPreference = "Stop"
$root = Resolve-Path (Join-Path $PSScriptRoot "..")
$Zip = Join-Path $env:TEMP "ziifra-fix.zip"

Write-Host "==> Packaging fix files"
if (Test-Path $Zip) { Remove-Item $Zip -Force }
Push-Location $root
& tar.exe -a -c -f $Zip `
  deploy/fix-production.sh `
  deploy/push-and-deploy.ps1 `
  package.json package-lock.json `
  lang/en/landing.php lang/sq/landing.php lang/de/landing.php `
  resources/views/landing.blade.php `
  resources/views/layouts/marketing.blade.php
Pop-Location

$target = "${User}@${ServerIp}"

Write-Host "==> Uploading (password prompt 1 of 2)"
& scp.exe -o StrictHostKeyChecking=accept-new $Zip "${target}:/tmp/ziifra-fix.zip"

Write-Host "==> Running fix on server (password prompt 2 of 2)"
& ssh.exe -o StrictHostKeyChecking=accept-new $target "cd /var/www/ziifra && unzip -o /tmp/ziifra-fix.zip && bash deploy/fix-production.sh"

Write-Host "==> Done. Open http://${ServerIp}/up"
