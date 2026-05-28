# ZIIFRA — Production server (Hostinger VPS)

Stack: **Ubuntu**, **Nginx**, **PHP 8.3**, **PostgreSQL**, **Supervisor** (queue), **cron** (scheduler).

Suggested domains (from [PLAN.md](../PLAN.md)):

| Host | Purpose |
|------|---------|
| `ziifra.com` | Marketing / landing (same app or static) |
| `app.ziifra.com` | Application |

## 1. VPS requirements

- Ubuntu 22.04 or 24.04
- 2 GB+ RAM recommended
- Open ports: 22 (SSH), 80, 443
- DNS A record: `app.ziifra.com` → VPS IP

### Your Hostinger VPS (API)

| Field | Value |
|-------|--------|
| **VM ID** | `1682923` |
| **IPv4** | `187.124.163.32` |
| **Hostname** | `srv1682923.hstgr.cloud` |
| **State** | running |
| **Plan** | KVM 4 (4 CPU, 16 GB RAM, 200 GB disk) |
| **OS** | Ubuntu 24.04 LTS |

SSH (from hPanel → VPS → SSH access): `ssh root@187.124.163.32`

Point your domain A record at **`187.124.163.32`** before running Certbot.

## Deploy code updates (safe — no DB / .env changes)

### GitHub pull (recommended)

If the VPS is linked to GitHub (`/var/www/ziifra` is a git clone):

**From your PC** (one SSH session, no zip upload):

```powershell
.\deploy\pull-and-deploy.ps1
```

With migrations when schema changed:

```powershell
.\deploy\pull-and-deploy.ps1
# migrations run by default; use -SkipMigrate to skip
```

**On the server directly** (hPanel browser terminal or SSH):

```bash
cd /var/www/ziifra
RUN_MIGRATIONS=1 bash deploy/pull-deploy.sh
curl -sS http://127.0.0.1/up
```

**Automatic deploy (GitHub Actions):** On every push to `main`, CI runs tests; when CI succeeds, the **Deploy** workflow SSHs to the VPS and runs `deploy/pull-deploy.sh`.

One-time setup (from your PC, in the project folder):

```powershell
gh auth login
.\deploy\setup-github-secrets.ps1
```

This stores these repository secrets:

| Secret | Value |
|--------|--------|
| `SSH_HOST` | `187.124.163.32` |
| `SSH_USER` | `root` |
| `SSH_PORT` | `22` |
| `SSH_PRIVATE_KEY` | Contents of `%USERPROFILE%\.ssh\github_deploy` |

The `github-deploy` public key must be in `/root/.ssh/authorized_keys` on the VPS (already configured). The VPS clones via `git@github.com:lukalinks/ziifra.git` using its own deploy key.

Manual deploy without pushing:

```powershell
gh workflow run Deploy
```

---

For day-to-day changes without git on the server (legacy zip upload):

```powershell
.\deploy\release.ps1
```

This uploads **code only** and does **not**:

- overwrite `.env`
- reset PostgreSQL passwords
- run migrations (unless you ask)
- delete uploaded files in `storage/app`

When you **added a migration** (new tables/columns):

```powershell
.\deploy\release.ps1 -Migrate
```

On the server directly:

```bash
cd /var/www/ziifra
RUN_MIGRATIONS=1 bash deploy/release.sh
```

| Script | When to use |
|--------|-------------|
| `release.ps1` | Normal code updates |
| `release.ps1 -Migrate` | Code + schema changes |
| `push-and-deploy.ps1` | First install only |
| `fix-remote.ps1` | Repair broken server / DB / .env |

## Quick deploy (from your PC)

Firewall ports **22 / 80 / 443** are configured on VPS `187.124.163.32`. Your SSH public key is attached (`ziifra-deploy`).

From the project folder in **PowerShell** (uses root password from hPanel → VPS → SSH access):

```powershell
.\deploy\push-and-deploy.ps1
```

Or set a custom domain:

```powershell
.\deploy\push-and-deploy.ps1 -Domain app.ziifra.com
```

**hPanel browser terminal** (if SSH from PC fails): open VPS → **Browser terminal**, then upload/extract the zip and run `bash deploy/install-remote.sh`.

---

## 2. One-time server bootstrap

SSH into the VPS as root:

```bash
git clone <your-repo-url> /var/www/ziifra
cd /var/www/ziifra

# Optional overrides
export APP_DOMAIN=app.ziifra.com
export APP_DIR=/var/www/ziifra

sudo bash deploy/setup-server.sh
```

The script installs PHP, Nginx, PostgreSQL, Supervisor, Certbot, Composer, and Node 20. It prints **database credentials** — save them.

## 3. Configure environment

```bash
cp deploy/.env.production.example .env
nano .env
php artisan key:generate
```

Required production values:

- `APP_URL` — must match your public URL (HTTPS after Certbot)
- `DB_*` — from setup script output
- `MAIL_*` — Hostinger SMTP (`smtp.hostinger.com`, port 587)
- `STRIPE_*` / `PAYPAL_*` — live keys for billing
- `SUPER_ADMIN_PASSWORD` — strong password, then run:

```bash
php artisan ziifra:grant-super-admin --create
```

## 4. First deploy

```bash
bash deploy/deploy.sh
```

## 5. HTTPS

```bash
sudo certbot --nginx -d app.ziifra.com
```

Update `.env`: `APP_URL=https://app.ziifra.com`, then:

```bash
php artisan config:cache
```

## 6. Stripe / PayPal webhooks

Point webhooks to:

- `https://app.ziifra.com/stripe/webhook`
- `https://app.ziifra.com/paypal/webhook`

## 7. Ongoing deploys

```bash
cd /var/www/ziifra
git pull
bash deploy/deploy.sh
```

## 8. Scheduled tasks

Installed automatically for `www-data`:

```cron
* * * * * cd /var/www/ziifra && php artisan schedule:run
```

Runs daily:

- `billing:send-reminders` (08:00)
- `documents:send-expiry-reminders` (08:30)

## 9. Queue worker

Supervisor runs `php artisan queue:work`. Payslips and mail use `sendNow()` by default; keep the worker if you enable queued jobs later.

```bash
sudo supervisorctl status ziifra-worker:*
```

## 10. Health check

```bash
curl -sS https://app.ziifra.com/up
```

## Hostinger API & MCP

**Auth:** `Authorization: Bearer YOUR_API_TOKEN` (create token in hPanel → Account → API).

**List VPS (CLI):**

```bash
npm install -g @hostinger/api-cli   # or use hapi from Hostinger docs
hapi vps vm list
```

**List VPS (HTTP):**

```http
GET https://developers.hostinger.com/api/vps/v1/virtual-machines
Authorization: Bearer YOUR_API_TOKEN
Accept: application/json
```

**Cursor MCP:** `hostinger-api-mcp@latest` in `~/.cursor/mcp.json` with `API_TOKEN` env — same token as Postman.

Docs: [developers.hostinger.com](https://developers.hostinger.com) · MCP: [github.com/hostinger/api-mcp-server](https://github.com/hostinger/api-mcp-server)

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 500 / permission denied | `ziifra-fix-perms /var/www/ziifra` |
| Assets 404 | `npm run build` on server during deploy |
| Mail fails | Verify SMTP user in hPanel → Emails |
| Stripe webhook 419 | CSRF is excluded; check `STRIPE_WEBHOOK_SECRET` |
| Session lost | `SESSION_DOMAIN` null or `.ziifra.com` for subdomains |
