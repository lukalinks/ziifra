# ZIIFRA

**HR management for companies in Kosovo.** Multi-tenant SaaS built with Laravel 13, Blade, and Tailwind CSS.

- **Default language:** English (`en`)
- **Market:** Kosovo (`country_code: XK`, timezone `Europe/Belgrade`, EUR)
- **Brand:** ZIIFRA (`APP_NAME=ZIIFRA`)

## What's included

| Module | Status |
|--------|--------|
| Landing page (marketing) | ✅ |
| Register → company workspace | ✅ |
| Login / logout / org switcher | ✅ |
| Team invitations & roles | ✅ |
| Employees, departments, positions, documents, CSV import | ✅ |
| OAuth (Google, GitHub) | ✅ |
| Leave management (incl. employee self-service, team calendar) | ✅ |
| Billing (trial, plans, limits) | ✅ |
| Super admin panel | ✅ |
| Kosovo payroll | 🔜 |
| Stripe checkout & billing portal | ✅ |
| Billing notification emails | ✅ |
| Dashboard (out today, upcoming leave, doc alerts) | ✅ |
| Employee CSV export | ✅ |
| Document expiry reminder emails | ✅ |

## Requirements

- PHP 8.3+
- Composer
- Node.js 20+

## Setup

```bash
composer install
cp .env.example .env   # if needed
php artisan key:generate
php artisan migrate
npm install
npm run build
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000).

## Tests

```bash
php artisan test
```

## Super admin (local)

After migrating, grant platform access to your user:

```bash
php artisan ziifra:grant-super-admin --create
```

Uses `SUPER_ADMIN_EMAIL` from `.env` (default `admin@ziifra.com`) and `SUPER_ADMIN_PASSWORD` (default `password` for local only).

Open [http://localhost:8000/admin](http://localhost:8000/admin) while logged in.

Platform admin includes: organization list (search, filters), plan/trial overrides, suspend/reactivate, user search, grant/revoke super admin, audited impersonation (bypasses suspend/trial limits while impersonating), and a global audit log.

## Mail (local)

Invitations use the `log` driver by default. Invite links appear in `storage/logs/laravel.log`.

## Stripe (optional)

Set in `.env`:

- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
- `STRIPE_PRICE_STARTER`, `STRIPE_PRICE_PRO` (Price IDs from the Stripe Dashboard)

Webhook endpoint: `POST /stripe/webhook` (events: `customer.subscription.*`, `invoice.payment_failed`).

## Billing reminders (production)

Trial-ending emails run daily via the scheduler:

```bash
php artisan billing:send-reminders
```

Add a cron entry: `* * * * * php /path/to/artisan schedule:run`

## Production deploy (Hostinger VPS)

**Day-to-day updates** (code only, keeps DB & `.env`):

```powershell
.\deploy\release.ps1
```

**First install** or full server repair: see [deploy/DEPLOY.md](deploy/DEPLOY.md).

One-time server bootstrap:

```powershell
.\deploy\push-and-deploy.ps1   # first install only
bash deploy/release.sh         # on server, after code is uploaded
```

Full guide: [deploy/DEPLOY.md](deploy/DEPLOY.md)

## Plan

See [PLAN.md](PLAN.md) for the full product roadmap and localization policy.
