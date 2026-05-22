# AGENTS.md

## Cursor Cloud specific instructions

### Project overview

ZIIFRA is a multi-tenant HR management SaaS built with Laravel 13 + Blade + Tailwind CSS 4. Local dev uses SQLite (file-based at `database/database.sqlite`), the `log` mail driver, and database-backed queue/cache/session.

### Environment setup (one-time, already done by VM snapshot)

PHP 8.3+ with extensions (sqlite3, mbstring, xml, curl, zip, dom, gd, intl, bcmath) and Composer are installed system-wide. The `.env` file is copied from `.env.example` with:

- `MAIL_FROM_ADDRESS=noreply@ziifra.com` (required — tests fail without it)
- `BILLING_ALLOW_MANUAL_UPGRADE=true` (allows testing plan upgrades without Stripe)

### Key commands

| Task | Command |
|------|---------|
| Install PHP deps | `composer install` |
| Install JS deps | `npm install` |
| Run migrations | `php artisan migrate` |
| Run all tests | `php artisan test` (or `./vendor/bin/phpunit`) |
| Lint (code style) | `./vendor/bin/pint --test` (check) / `./vendor/bin/pint` (fix) |
| Build frontend | `npm run build` |
| Dev server (all-in-one) | `composer dev` (starts Laravel server + queue + Vite + log tail) |
| Dev server (manual) | `php artisan serve --host=0.0.0.0 --port=8000` + `npm run dev` |
| Create super admin | `php artisan ziifra:grant-super-admin --create` |

### Gotchas

- **MAIL_FROM_ADDRESS must be set in `.env`** — even though tests use `MAIL_MAILER=array` (from `phpunit.xml`), a missing From address causes all 293 tests to fail with "An email must have a From or a Sender header."
- **Pint reports ~94 pre-existing code style issues** — these are in the existing codebase and not regressions.
- **PHPUnit outputs JSON** by default. Use `php artisan test` for human-readable output or parse the JSON from `./vendor/bin/phpunit`.
- **SQLite database** lives at `database/database.sqlite`. If tests or migrations act strangely, you can delete it and re-run `php artisan migrate`.
- **Stripe/PayPal are optional** — set `BILLING_ALLOW_MANUAL_UPGRADE=true` in `.env` to bypass payment providers during development.
- **`composer dev`** runs Laravel server, queue worker, Pail log viewer, and Vite dev server concurrently via `npx concurrently`. This is the recommended way to start the full dev environment.
