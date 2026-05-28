#!/usr/bin/env bash
# ZIIFRA — code-only release (safe updates: keeps .env, DB, uploads)
# Run on VPS: bash deploy/release.sh
# Optional: RUN_MIGRATIONS=1 bash deploy/release.sh
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "${APP_DIR}"

echo "==> ZIIFRA code release (${APP_DIR})"
echo "    .env, database, and storage/app uploads are NOT modified"

if [[ ! -f .env ]]; then
  echo "ERROR: Missing .env — first install: bash deploy/fix-production.sh"
  exit 1
fi

php artisan down --retry=60 --secret="${DEPLOY_SECRET:-ziifra-deploy}" || true

composer install --no-dev --optimize-autoloader --no-interaction
npm install --no-audit --no-fund
npm run build

php artisan config:clear
php artisan view:clear
php artisan route:clear

if [[ "${RUN_MIGRATIONS:-0}" == "1" ]]; then
  echo "==> Running database migrations"
  php artisan migrate --force
else
  echo "==> Skipping migrations (use RUN_MIGRATIONS=1 when schema changed)"
fi

php artisan storage:link --force 2>/dev/null || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

if command -v ziifra-fix-perms >/dev/null 2>&1; then
  ziifra-fix-perms "${APP_DIR}"
else
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R ug+rwx storage bootstrap/cache
fi

php artisan up

systemctl reload php8.3-fpm 2>/dev/null || true
supervisorctl restart ziifra-worker:* 2>/dev/null || true

echo ""
echo "==> Release complete"
curl -sfk "https://127.0.0.1/up" -H "Host: ziifra.com" >/dev/null && echo "Health OK: https://ziifra.com/up" || true
