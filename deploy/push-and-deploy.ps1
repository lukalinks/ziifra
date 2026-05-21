# ZIIFRA — upload project to VPS and install (run locally in PowerShell)
# Requires: OpenSSH client, root password from hPanel → VPS → SSH access
param(
    [string]$ServerIp = "187.124.163.32",
    [string]$User = "root",
    [string]$RemoteDir = "/var/www/ziifra",
    [string]$Domain = "srv1682923.hstgr.cloud"
)

$ErrorActionPreference = "Stop"
$ProjectRoot = Resolve-Path (Join-Path $PSScriptRoot "..")

$Zip = Join-Path $env:TEMP "ziifra-deploy.zip"
Write-Host "==> Packaging $ProjectRoot"
if (Test-Path $Zip) { Remove-Item $Zip -Force }

$exclude = @('vendor', 'node_modules', '.git', 'storage\logs', 'storage\framework\cache', 'storage\framework\sessions', 'storage\framework\views')
Push-Location $ProjectRoot
& tar.exe -a -c -f $Zip --exclude=vendor --exclude=node_modules --exclude=.git `
    --exclude=.env --exclude=.env.backup `
    --exclude=storage/logs --exclude=storage/framework/cache `
    --exclude=storage/framework/sessions --exclude=storage/framework/views .
Pop-Location

Write-Host "==> Uploading to ${User}@${ServerIp}:${RemoteDir}"
& ssh.exe -o StrictHostKeyChecking=accept-new "${User}@${ServerIp}" "mkdir -p $RemoteDir"
& scp.exe -o StrictHostKeyChecking=accept-new $Zip "${User}@${ServerIp}:/tmp/ziifra-deploy.zip"

$remote = @"
set -e
apt-get install -y unzip 2>/dev/null || true
mkdir -p $RemoteDir
unzip -o /tmp/ziifra-deploy.zip -d $RemoteDir
cd $RemoteDir
export APP_DOMAIN=$Domain APP_DIR=$RemoteDir
if [[ ! -f /etc/nginx/sites-enabled/ziifra ]]; then
  bash deploy/setup-server.sh
fi
bash deploy/fix-production.sh
"@

Write-Host "==> Running install on server (enter root password if prompted)"
& ssh.exe -o StrictHostKeyChecking=accept-new "${User}@${ServerIp}" $remote

Write-Host "==> Done. Open http://${ServerIp}/up"
