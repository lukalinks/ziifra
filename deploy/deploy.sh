#!/usr/bin/env bash
# ZIIFRA — deploy or update application on the VPS
# Run from app root: bash deploy/deploy.sh
set -euo pipefail

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
cd "${APP_DIR}"

echo "==> Deploying ZIIFRA in ${APP_DIR}"

if [[ ! -f .env ]]; then
  echo "Missing .env — copy deploy/.env.production.example to .env first."
  exit 1
fi

# Maintenance mode during update
php artisan down --retry=60 --secret="${DEPLOY_SECRET:-ziifra-deploy}" || true

if [[ "${DEPLOY_NO_GIT:-}" != "1" ]]; then
  git pull --ff-only
fi

composer install --no-dev --optimize-autoloader --no-interaction
npm install --no-audit --no-fund
npm run build

php artisan config:clear 2>/dev/null || true
php artisan migrate --force
php artisan storage:link --force 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache 2>/dev/null || true

ziifra-fix-perms "${APP_DIR}" 2>/dev/null || {
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R ug+rwx storage bootstrap/cache
}

php artisan up

sudo systemctl reload php8.3-fpm 2>/dev/null || true
sudo supervisorctl restart ziifra-worker:* 2>/dev/null || true

echo "==> Deploy complete. Health: $(grep APP_URL .env | cut -d= -f2)/up"
