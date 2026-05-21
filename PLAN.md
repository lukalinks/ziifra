# ZIIFRA — Product & Build Plan

## Product summary

**ZIIFRA** is a multi-tenant HR SaaS for Kosovo companies. **Laravel** backend, **SQLite** (local) / **PostgreSQL** (production), shared database with `organization_id`, Stripe billing. Phased delivery from SaaS shell → HR core → Kosovo payroll.

## Branding

| Item | Value |
|------|--------|
| **Product name** | **ZIIFRA** |
| **Display** | ZIIFRA (all caps in UI/logo; use consistently) |
| **Laravel** | `APP_NAME=ZIIFRA` |
| **Email from** | `ZIIFRA <noreply@ziifra.com>` (domain TBD) |
| **Suggested domains** | `ziifra.com` (marketing), `app.ziifra.com` (application) — confirm before deploy |
| **Landing title** | *ZIIFRA — HR management for companies in Kosovo* |

---

## Localization (language policy)

| Setting | Choice |
|---------|--------|
| **Default language** | **English (`en`)** |
| **Fallback locale** | `en` |
| **Additional locales (later)** | Albanian (`sq`), Serbian (`sr`) |
| **Laravel config** | `APP_LOCALE=en`, `APP_FALLBACK_LOCALE=en` |
| **URL strategy** | Default routes without prefix; optional `/sq/...` when Albanian is added |
| **Workspace URLs** | Per-company paths: `/o/{company-slug}/dashboard` (session + slug; subdomains later for enterprise) |

### Rules

- All **first-release** UI copy (landing page, auth, invitations, dashboard, emails) is written in **English**.
- Translation files live under `lang/en/` from day one; `lang/sq/` and `lang/sr/` are added in a later phase without changing defaults.
- User-facing locale switcher is **not** required for MVP; English only until Phase 3.
- **Legal/compliance documents** (contracts, payslips) may support Albanian templates later; app chrome stays English-first until i18n ships.

---

## Implementation status

| Item | Status |
|------|--------|
| **1a Landing page** | Done — ZIIFRA branding, features, FAQ, pricing, English |
| **1b SaaS shell** | Done — register + org, login, invites, tenant isolation, **company settings** |
| **2 Employees** | Done — CRUD, departments, positions, manager, org scope, **custom fields**, **documents** |
| **3 Leave** | Done — types, balances, request → approve/reject, **employee self-service**, email notifications, **team calendar** |
| **4 Billing + super admin** | Done — 14-day trial, plan limits, billing page, `/admin` panel (suspend, plan, impersonate + audit) |
| **OAuth sign-in** | Done (Google/GitHub) — optional convenience, not in original MVP |
| **Dashboard widgets** | Done — out today, upcoming leave, expiring documents |
| **Employee CSV export** | Done |
| **Document expiry emails** | Done — `documents:send-expiry-reminders` daily |
| **Kosovo payroll (MVP)** | Done — monthly runs, draft/lock, payslips, gross on employee (Pro/Enterprise) |

---

## Build order

### 1a — Landing page (public, English) ✅

- **ZIIFRA** branding: logo/wordmark, header, footer
- Headline, features, FAQ, pricing teaser, CTA → `/register`
- Footer: Privacy, Terms, contact
- Blade + Tailwind in same Laravel project
- **Language: English only** for MVP

### 1b — Feature 1: SaaS shell ✅

- Register → user + organization + owner role
- Login / logout
- Tenant middleware (`organization_id`, policies) + **workspace URLs** (`/o/{slug}/…`)
- Invitations (email, accept flow)
- **Company settings** — legal identity, workspace slug, HR contacts, work week & Kosovo holidays, employment defaults, payroll/bank/signatory, team invite policy, branding; Owner/Admin only
- App UI (English)
- IDOR / tenant isolation tests (`TenantIsolationTest` — Company A cannot access Company B)

### 2 — Employees ✅

- CRUD scoped to organization
- Departments, positions, manager
- **Custom fields** — per-org definitions (text, number, date, yes/no, dropdown, **file upload**); create on employee form or in Settings → Custom fields
- **Employee documents** — contracts, ID, certificates on profile (upload, download, expiry)
- **CSV employee import** — bulk onboarding with template download

### 3 — Leave ✅

- Types, balances, request → approval
- Employee self-service (linked user ↔ employee profile)
- Email notifications on submit / approve / reject
- Managers approve direct reports; HR/Admin see all
- Team leave calendar (month view, work week, Kosovo holidays)

### 4 — Billing + minimal super admin ✅

- 14-day trial on register, plan tiers (Starter / Pro / Enterprise), employee limits enforced
- Billing settings page (`/o/{slug}/settings/billing`) for owners/admins
- Platform admin at `/admin`: org list (filters), suspend/reactivate, change plan, extend trial, user search, grant/revoke super admin, impersonate any member (audited), global audit log, `php artisan ziifra:grant-super-admin`
- Stripe Checkout + Customer Portal + webhooks (Starter / Pro); Enterprise via contact
- Billing emails: trial ending (7/3/1/0 days), employee limit warning, payment failed (`billing:send-reminders` daily)

### Phase 2+ — Kosovo payroll, compliance exports, accountant multi-org, API

---

## Roadmap phases

| Phase | Duration | Focus |
|-------|----------|--------|
| **0 — Foundation** | ~4 weeks | Auth, orgs, invites, tenant security, **English UI** |
| **1 — HR MVP** | ~8 weeks | Employees, leave, documents, landing + pilots |
| **2 — Payroll** | ~10 weeks | Kosovo tax/pension, payslips, locked runs |
| **3 — Growth** | ~10 weeks | Accountant mode, API, **Albanian + Serbian UI** |
| **4 — Enterprise** | ongoing | SSO, dedicated DB, advanced security |

---

## Database & data safety (summary)

- PostgreSQL (production), `organization_id` on tenant tables
- Laravel global scopes + policies + IDOR tests
- Postgres RLS (recommended before payroll)
- Field encryption for national ID / bank details (later)
- Immutable locked payroll runs with rule snapshots (payroll phase)

---

## Super admin (platform staff)

- Manage organizations, subscriptions, feature flags
- Support: user search, impersonation (audited), suspend org
- Publish tax/holiday rule versions (Phase 2+)
- **Not** day-to-day HR for customers

---

## Default language checklist (implementation)

- [x] `config/app.php` → `'locale' => 'en'`, `'fallback_locale' => 'en'`
- [x] `lang/en/` strings (`auth.php`, `ziifra.php`)
- [x] `APP_NAME=ZIIFRA` in `.env`
- [x] Landing page copy in English (ZIIFRA branding)
- [x] Auth & invitation emails in English (signed “ZIIFRA”)
- [x] Timezone `Europe/Belgrade` for Kosovo operations
- [ ] Albanian (`sq`) — Phase 3 i18n backlog

---

## Pricing (indicative)

| Plan | Notes |
|------|--------|
| Trial | 14 days |
| Starter | HR + leave, no payroll |
| Pro | + Kosovo payroll |
| Enterprise | SSO, dedicated DB option |

---

## Run locally

```bash
composer install
php artisan migrate
npm install && npm run build
php artisan serve
```

Tests: `php artisan test`
