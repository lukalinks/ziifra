# One-time: store VPS SSH credentials in GitHub Actions secrets.
# Requires: GitHub CLI logged in (gh auth login) with repo admin access.
#
# Usage:
#   .\deploy\setup-github-secrets.ps1
#   .\deploy\setup-github-secrets.ps1 -Repo lukalinks/ziifra
param(
    [string]$Repo = "",
    [string]$ServerIp = "187.124.163.32",
    [string]$User = "root",
    [string]$Port = "22",
    [string]$KeyFile = ""
)

$ErrorActionPreference = "Stop"

if (-not $KeyFile) {
    $KeyFile = Join-Path $env:USERPROFILE ".ssh\github_deploy"
}
if (-not (Test-Path $KeyFile)) {
    throw "SSH private key not found: $KeyFile`nGenerate with: ssh-keygen -t ed25519 -f `"$KeyFile`" -C github-deploy"
}

$gh = Get-Command gh -ErrorAction SilentlyContinue
if (-not $gh) {
    throw "GitHub CLI (gh) is required. Install from https://cli.github.com/ then run: gh auth login"
}

gh auth status 2>&1 | Out-Null
if ($LASTEXITCODE -ne 0) {
    throw "Not logged in to GitHub. Run: gh auth login"
}

if (-not $Repo) {
    $origin = git remote get-url origin 2>$null
    if ($origin -match 'github\.com[:/](.+?)(?:\.git)?$') {
        $Repo = $Matches[1]
    } else {
        throw "Could not detect repo from git remote. Pass -Repo owner/name"
    }
}

Write-Host "==> Setting Actions secrets on $Repo"
Write-Host "    SSH_HOST=$ServerIp"
Write-Host "    SSH_USER=$User"
Write-Host "    SSH_PORT=$Port"
Write-Host "    SSH_PRIVATE_KEY=<from $KeyFile>"

gh secret set SSH_HOST --body $ServerIp --repo $Repo
gh secret set SSH_USER --body $User --repo $Repo
gh secret set SSH_PORT --body $Port --repo $Repo
Get-Content $KeyFile -Raw | gh secret set SSH_PRIVATE_KEY --repo $Repo

Write-Host ""
Write-Host "==> Done. Ensure the matching public key is on the VPS:"
Write-Host "    $(Join-Path (Split-Path $KeyFile) (Split-Path $KeyFile -LeafBaseName)).pub"
Write-Host ""
Write-Host "Deploy flow: push to main -> CI tests -> Deploy workflow (automatic)"
Write-Host "Manual deploy: gh workflow run Deploy --repo $Repo"
