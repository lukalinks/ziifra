#!/usr/bin/env bash
# Fix HTTP 500 / DB errors — run on VPS: sudo bash deploy/fix-production.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ziifra}"
cd "${APP_DIR}"

echo "==> Fixing ZIIFRA production (${APP_DIR})"

mkdir -p storage/logs storage/framework/{cache,sessions,views} storage/app/public bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

if [[ ! -f .env ]]; then
  cp deploy/.env.production.example .env
fi

# Never use local SQLite on the VPS
if grep -q '^DB_CONNECTION=sqlite' .env 2>/dev/null; then
  echo "==> Replacing SQLite .env with PostgreSQL production settings"
fi

# Sync PostgreSQL password with .env (source of truth: /tmp/ziifra-db-pass or new random)
if [[ "${RESET_DB_PASSWORD:-}" == "1" ]] || [[ ! -f /tmp/ziifra-db-pass ]]; then
  DB_PASS="$(openssl rand -base64 24 | tr -d '/+=' | head -c 32)"
  echo "==> Generated new database password"
else
  DB_PASS="$(tr -d '\n\r' < /tmp/ziifra-db-pass)"
fi

PG_PASS_ESC="${DB_PASS//\'/\'\'}"
sudo -u postgres psql -v ON_ERROR_STOP=1 <<SQL
DO \$\$
BEGIN
  IF NOT EXISTS (SELECT FROM pg_roles WHERE rolname = 'ziifra') THEN
    CREATE USER ziifra WITH PASSWORD '${PG_PASS_ESC}';
  ELSE
    ALTER USER ziifra WITH PASSWORD '${PG_PASS_ESC}';
  END IF;
END
\$\$;
SQL
printf '%s' "${DB_PASS}" > /tmp/ziifra-db-pass
chmod 600 /tmp/ziifra-db-pass
if ! sudo -u postgres psql -tAc "SELECT 1 FROM pg_database WHERE datname='ziifra'" | grep -q 1; then
  sudo -u postgres psql -c "CREATE DATABASE ziifra OWNER ziifra;"
fi
echo "==> PostgreSQL user ziifra password synced (saved in /tmp/ziifra-db-pass)"

# Write all DB settings into .env (quoted password for special chars)
set_env() {
  local key="$1" val="$2"
  if grep -q "^${key}=" .env; then
    sed -i "s|^${key}=.*|${key}=${val}|" .env
  else
    echo "${key}=${val}" >> .env
  fi
}

set_env "APP_ENV" "production"
set_env "APP_DEBUG" "false"
set_env "APP_URL" "https://ziifra.com"
set_env "DB_CONNECTION" "pgsql"
set_env "DB_HOST" "127.0.0.1"
set_env "DB_PORT" "5432"
set_env "DB_DATABASE" "ziifra"
set_env "DB_USERNAME" "ziifra"
# Password must be quoted
if grep -q '^DB_PASSWORD=' .env; then
  awk -v pass="$DB_PASS" 'BEGIN{q=sprintf("%c",39)} /^DB_PASSWORD=/{print "DB_PASSWORD=" q pass q; next} {print}' .env > .env.tmp && mv .env.tmp .env
else
  echo "DB_PASSWORD='${DB_PASS}'" >> .env
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  php artisan key:generate --force
fi

echo "==> DB: ziifra@$(grep '^DB_HOST=' .env | cut -d= -f2) (password set)"

sudo -u postgres psql -d ziifra -v ON_ERROR_STOP=1 <<'SQL' 2>/dev/null || true
GRANT ALL ON SCHEMA public TO ziifra;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO ziifra;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO ziifra;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO ziifra;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO ziifra;
SQL

if [[ -f /etc/nginx/sites-available/ziifra ]]; then
  if ! grep -q 'server_name ziifra.com' /etc/nginx/sites-available/ziifra; then
    sed -i 's/server_name .*/server_name ziifra.com www.ziifra.com;/' /etc/nginx/sites-available/ziifra
  fi
  nginx -t && systemctl reload nginx
fi

export DEPLOY_NO_GIT=1
composer install --no-dev --optimize-autoloader --no-interaction
npm install --no-audit --no-fund
npm run build

php artisan config:clear
php artisan migrate --force
php artisan cache:clear 2>/dev/null || true
php artisan storage:link --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

ziifra-fix-perms "${APP_DIR}" 2>/dev/null || true

sed -e "s|APP_ROOT|${APP_DIR}|g" deploy/supervisor/ziifra-worker.conf > /etc/supervisor/conf.d/ziifra-worker.conf
supervisorctl reread 2>/dev/null || true
supervisorctl update 2>/dev/null || true

php artisan ziifra:grant-super-admin --create 2>/dev/null || true

echo ""
echo "==> Fix complete"
php artisan migrate:status 2>/dev/null | head -5 || true
curl -sS "http://127.0.0.1/up" && echo "" || echo "(curl /up failed — check storage/logs/laravel.log)"
